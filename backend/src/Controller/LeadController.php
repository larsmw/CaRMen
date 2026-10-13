<?php

namespace App\Controller;

use App\Entity\Account;
use App\Entity\Activity;
use App\Entity\ApiKey;
use App\Entity\Contact;
use App\Security\ApiKeyUser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Lead intake for integrations (website forms, other systems), authenticated by an API key
 * in the X-Api-Key header.
 *
 * - New email address: creates a contact with status "lead"; "company" is linked to the
 *   account with that name, or a new account.
 * - Known email address: the contact is left as it is (a form never overwrites CRM data);
 *   the submission is added as a note on it.
 * Either way the message and its origin are stored as a note on the contact.
 */
class LeadController extends AbstractController
{
    #[Route('/api/leads', name: 'api_leads_create', methods: ['POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        RateLimiterFactoryInterface $apiKeyLimiter,
    ): JsonResponse {
        /** @var ApiKeyUser $caller */
        $caller = $this->getUser();
        $apiKey = $caller->getApiKey();

        $limit = $apiKeyLimiter->create($apiKey->getPrefix())->consume();
        if (!$limit->isAccepted()) {
            return $this->json(['error' => 'Rate limit exceeded for this API key.'], 429, [
                'Retry-After' => max(1, $limit->getRetryAfter()->getTimestamp() - time()),
            ]);
        }

        $data = json_decode($request->getContent(), true);
        if (!\is_array($data)) {
            return $this->json(['error' => 'Send a JSON object.'], 400);
        }
        $data = $this->normalize($data);

        $errors = [];
        foreach ($validator->validate($data, $this->constraints()) as $violation) {
            $errors[trim($violation->getPropertyPath(), '[]')] = $violation->getMessage();
        }
        if ($errors) {
            return $this->json(['errors' => $errors], 422);
        }

        $contact = $em->getRepository(Contact::class)->createQueryBuilder('c')
            ->where('LOWER(c.email) = :email')->setParameter('email', $data['email'])
            ->getQuery()->getOneOrNullResult();
        $created = $contact === null;

        if ($created) {
            $contact = (new Contact())
                ->setFirstName($data['firstName'])
                ->setLastName($data['lastName'])
                ->setEmail($data['email'])
                ->setPhone($data['phone'])
                ->setJobTitle($data['jobTitle'])
                ->setStatus('lead');
            if ($data['company'] !== null) {
                $contact->setAccount($this->findOrCreateAccount($em, $data['company']));
            }
            $em->persist($contact);
        }

        $em->persist($this->note($contact, $apiKey, $data, $created));
        $apiKey->markUsed();
        $em->flush();

        return $this->json([
            'contactId' => (string) $contact->getId(),
            'created'   => $created,
        ], $created ? 201 : 200);
    }

    /** Trim strings, turn "" into null, lower-case the email, split "name" if given instead of first/last. */
    private function normalize(array $data): array
    {
        $out = [];
        foreach (['firstName', 'lastName', 'name', 'email', 'phone', 'company', 'jobTitle', 'message', 'source'] as $field) {
            $value = $data[$field] ?? null;
            $value = \is_scalar($value) ? trim((string) $value) : null;
            $out[$field] = $value === '' ? null : $value;
        }

        if ($out['firstName'] === null && $out['lastName'] === null && $out['name'] !== null) {
            $parts = preg_split('/\s+/', $out['name']);
            $out['lastName']  = \count($parts) > 1 ? array_pop($parts) : null;
            $out['firstName'] = implode(' ', $parts);
        }
        unset($out['name']);

        $out['email'] = $out['email'] !== null ? mb_strtolower($out['email']) : null;

        return $out;
    }

    private function constraints(): Assert\Collection
    {
        $optional = fn (int $max) => [new Assert\Length(max: $max)];

        return new Assert\Collection([
            'firstName' => [new Assert\NotBlank(message: 'firstName (or "name") is required.'), new Assert\Length(max: 100)],
            'lastName'  => [new Assert\NotBlank(message: 'lastName is required (or a "name" with first and last name).'), new Assert\Length(max: 100)],
            'email'     => [new Assert\NotBlank(message: 'email is required.'), new Assert\Email(), new Assert\Length(max: 255)],
            'phone'     => $optional(20),
            'company'   => $optional(255),
            'jobTitle'  => $optional(255),
            'message'   => $optional(5000),
            'source'    => $optional(200),
        ]);
    }

    private function findOrCreateAccount(EntityManagerInterface $em, string $name): Account
    {
        $account = $em->getRepository(Account::class)->createQueryBuilder('a')
            ->where('LOWER(a.name) = :name')->setParameter('name', mb_strtolower($name))
            ->setMaxResults(1)->getQuery()->getOneOrNullResult();

        if ($account === null) {
            $account = (new Account())->setName($name);
            $em->persist($account);
        }

        return $account;
    }

    private function note(Contact $contact, ApiKey $key, array $data, bool $created): Activity
    {
        $origin = $data['source'] !== null ? "{$data['source']} (API key \"{$key->getName()}\")" : "API key \"{$key->getName()}\"";

        $details = [];
        if (!$created) {
            // The contact was left untouched; keep what the form said so nothing is lost
            foreach (['firstName' => 'Name', 'phone' => 'Phone', 'company' => 'Company', 'jobTitle' => 'Job title'] as $field => $label) {
                if ($data[$field] !== null) {
                    $value = $field === 'firstName' ? trim($data['firstName'] . ' ' . $data['lastName']) : $data[$field];
                    $details[] = "$label: $value";
                }
            }
        }
        $description = implode("\n\n", array_filter([$data['message'], $details ? implode("\n", $details) : null]));

        return (new Activity())
            ->setType(Activity::TYPE_NOTE)
            ->setSubject(mb_substr(($created ? 'New lead via ' : 'Lead submitted again via ') . $origin, 0, 255))
            ->setDescription($description !== '' ? $description : null)
            ->setStatus(Activity::STATUS_COMPLETED)
            ->setContact($contact)
            ->setCompletedAt(new \DateTime());
    }
}
