<?php

namespace App\Controller;

use App\Entity\ApiKey;
use App\Entity\User;
use App\Repository\ApiKeyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/** Admins manage integration API keys. The plain key is only ever returned by create(). */
#[Route('/api/api-keys')]
#[IsGranted('ROLE_ADMIN')]
class ApiKeyController extends AbstractController
{
    public function __construct(
        private ApiKeyRepository $keys,
        private EntityManagerInterface $em,
    ) {}

    #[Route('', name: 'api_api_keys_list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        $keys = $this->keys->findBy([], ['createdAt' => 'DESC']);

        return $this->json(array_map($this->describe(...), $keys));
    }

    #[Route('', name: 'api_api_keys_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $name = trim((string) (json_decode($request->getContent(), true)['name'] ?? ''));
        if ($name === '' || mb_strlen($name) > 100) {
            return $this->json(['errors' => ['name' => 'Give the key a name of at most 100 characters.']], 422);
        }

        /** @var User $user */
        $user = $this->getUser();
        [$key, $plain] = ApiKey::generate($name, [ApiKey::SCOPE_LEADS_CREATE], $user);
        $this->em->persist($key);
        $this->em->flush();

        // The only time the full key is ever shown
        return $this->json($this->describe($key) + ['key' => $plain], 201);
    }

    #[Route('/{id}', name: 'api_api_keys_revoke', methods: ['DELETE'], requirements: ['id' => '[0-9a-fA-F-]{36}'])]
    public function revoke(string $id): JsonResponse
    {
        $key = $this->keys->find($id);
        if ($key === null) {
            return $this->json(['error' => 'API key not found.'], 404);
        }

        // Kept (revoked) rather than deleted, so leads it created stay explained
        $key->revoke();
        $this->em->flush();

        return $this->json($this->describe($key));
    }

    private function describe(ApiKey $key): array
    {
        return [
            'id'         => (string) $key->getId(),
            'name'       => $key->getName(),
            'prefix'     => 'crm_' . $key->getPrefix() . '_…',
            'scopes'     => $key->getScopes(),
            'createdBy'  => $key->getCreatedBy()?->getFullName(),
            'createdAt'  => $key->getCreatedAt()->format(\DateTimeInterface::ATOM),
            'lastUsedAt' => $key->getLastUsedAt()?->format(\DateTimeInterface::ATOM),
            'revokedAt'  => $key->getRevokedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }
}
