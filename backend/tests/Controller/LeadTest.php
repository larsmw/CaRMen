<?php

namespace App\Tests\Controller;

use App\Entity\Account;
use App\Entity\Activity;
use App\Entity\ApiKey;
use App\Entity\Contact;
use App\Tests\ApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use Doctrine\ORM\EntityManagerInterface;

class LeadTest extends ApiTestCase
{
    private const LEAD = [
        'firstName' => 'Lena',
        'lastName'  => 'Lead',
        'email'     => 'Lena.Lead@Example.com',
        'phone'     => '+45 12 34 56 78',
        'company'   => 'Brand New ApS',
        'jobTitle'  => 'Buyer',
        'message'   => 'Please call me about pricing.',
        'source'    => 'Website contact form',
    ];

    protected function setUp(): void
    {
        static::resetFixtures();
    }

    private function em(): EntityManagerInterface
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        return $em;
    }

    /** @return array{0: ApiKey, 1: string} */
    private function createKey(string $name = 'Website form'): array
    {
        $em = $this->em();
        [$key, $plain] = ApiKey::generate($name, [ApiKey::SCOPE_LEADS_CREATE], null);
        $em->persist($key);
        $em->flush();
        return [$key, $plain];
    }

    private function postLead(?string $plainKey, array $lead = self::LEAD): Client
    {
        $client = static::createClient();
        $client->request('POST', '/api/leads', [
            'headers' => $plainKey !== null ? ['X-Api-Key' => $plainKey] : [],
            'json'    => $lead,
        ]);
        return $client;
    }

    public function testCreatesLeadContactWithAccountAndNote(): void
    {
        [, $plain] = $this->createKey();
        $response = $this->postLead($plain)->getResponse();
        $this->assertResponseStatusCodeSame(201);
        $this->assertTrue($response->toArray()['created']);

        $contact = $this->em()->getRepository(Contact::class)->findOneBy(['email' => 'lena.lead@example.com']);
        $this->assertNotNull($contact);
        $this->assertSame('lead', $contact->getStatus());
        $this->assertSame('Buyer', $contact->getJobTitle());
        $this->assertSame('Brand New ApS', $contact->getAccount()->getName());

        $notes = $this->em()->getRepository(Activity::class)->findBy(['contact' => $contact]);
        $this->assertCount(1, $notes);
        $this->assertSame(Activity::TYPE_NOTE, $notes[0]->getType());
        $this->assertStringContainsString('Website contact form', $notes[0]->getSubject());
        $this->assertStringContainsString('Website form', $notes[0]->getSubject());
        $this->assertSame('Please call me about pricing.', $notes[0]->getDescription());
    }

    public function testKnownEmailIsNotOverwritten(): void
    {
        [, $plain] = $this->createKey();
        $this->postLead($plain);
        $this->assertResponseStatusCodeSame(201);

        $again = $this->postLead($plain, ['firstName' => 'Somebody', 'lastName' => 'Else', 'email' => 'LENA.lead@example.com',
            'phone' => '999', 'message' => 'Second message'])->getResponse();
        $this->assertResponseStatusCodeSame(200);
        $this->assertFalse($again->toArray()['created']);

        $em       = $this->em();
        $contacts = $em->getRepository(Contact::class)->findBy(['email' => 'lena.lead@example.com']);
        $this->assertCount(1, $contacts);
        $this->assertSame('Lena', $contacts[0]->getFirstName());
        $this->assertSame('+45 12 34 56 78', $contacts[0]->getPhone());

        $notes = $em->getRepository(Activity::class)->findBy(['contact' => $contacts[0]], ['subject' => 'DESC']);
        $this->assertCount(2, $notes);
        $second = array_values(array_filter($notes, fn ($n) => str_contains($n->getSubject(), 'again')))[0];
        $this->assertStringContainsString('Second message', $second->getDescription());
        $this->assertStringContainsString('Name: Somebody Else', $second->getDescription());
        $this->assertStringContainsString('Phone: 999', $second->getDescription());
    }

    public function testCompanyMatchesExistingAccountCaseInsensitively(): void
    {
        $em = $this->em();
        $em->persist((new Account())->setName('Existing Corp'));
        $em->flush();

        [, $plain] = $this->createKey();
        $this->postLead($plain, ['name' => 'Ann Marie Jensen', 'email' => 'ann@existing.example', 'company' => 'existing corp']);
        $this->assertResponseStatusCodeSame(201);

        $em = $this->em();
        $this->assertSame(1, $em->getRepository(Account::class)->count(['name' => 'Existing Corp']));
        $contact = $em->getRepository(Contact::class)->findOneBy(['email' => 'ann@existing.example']);
        $this->assertSame('Existing Corp', $contact->getAccount()->getName());
        // "name" is split on the last space
        $this->assertSame('Ann Marie', $contact->getFirstName());
        $this->assertSame('Jensen', $contact->getLastName());
    }

    public function testValidation(): void
    {
        [, $plain] = $this->createKey();
        $response = $this->postLead($plain, ['name' => 'Cher', 'email' => 'not-an-email', 'phone' => str_repeat('1', 30)])->getResponse();
        $this->assertResponseStatusCodeSame(422);

        $errors = $response->toArray(false)['errors'];
        $this->assertArrayHasKey('lastName', $errors);
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('phone', $errors);

        $this->postLead($plain, ['email' => 'a@b.example', 'unknownField' => 'x', 'firstName' => 'A', 'lastName' => 'B']);
        $this->assertResponseStatusCodeSame(201);
    }

    public function testRejectsMissingWrongAndRevokedKeys(): void
    {
        $this->postLead(null);
        $this->assertResponseStatusCodeSame(401);

        $this->postLead('crm_00000000_' . str_repeat('a', 32));
        $this->assertResponseStatusCodeSame(401);

        // Right prefix, wrong secret
        [$key, $plain] = $this->createKey();
        $this->postLead('crm_' . $key->getPrefix() . '_' . str_repeat('b', 32));
        $this->assertResponseStatusCodeSame(401);

        $em  = $this->em();
        $key = $em->getRepository(ApiKey::class)->find($key->getId());
        $key->revoke();
        $em->flush();
        $this->postLead($plain);
        $this->assertResponseStatusCodeSame(401);

        $this->assertSame(0, $this->em()->getRepository(Contact::class)->count(['email' => 'lena.lead@example.com']));
    }

    public function testUserTokenCannotPostLeads(): void
    {
        $this->adminClient()->request('POST', '/api/leads', ['json' => self::LEAD]);
        $this->assertResponseStatusCodeSame(401);
    }

    public function testKeyCannotReachTheRestOfTheApi(): void
    {
        [, $plain] = $this->createKey();
        $client = static::createClient();
        foreach (['/api/contacts', '/api/users', '/api/me', '/api/api-keys'] as $path) {
            $client->request('GET', $path, ['headers' => ['X-Api-Key' => $plain]]);
            $this->assertResponseStatusCodeSame(401, $path);
        }
    }

    public function testRecordsLastUsed(): void
    {
        [$key, $plain] = $this->createKey();
        $this->assertNull($key->getLastUsedAt());
        $this->postLead($plain);

        $this->assertNotNull($this->em()->getRepository(ApiKey::class)->find($key->getId())->getLastUsedAt());
    }

    public function testRateLimitPerKey(): void
    {
        [, $plain]      = $this->createKey('Busy form');
        [, $otherPlain] = $this->createKey('Quiet form');
        $client = static::createClient();
        for ($i = 1; $i <= 60; $i++) {
            $client->request('POST', '/api/leads', ['headers' => ['X-Api-Key' => $plain], 'json' => ['email' => 'bad']]);
            $this->assertResponseStatusCodeSame(422);
        }

        $client->request('POST', '/api/leads', ['headers' => ['X-Api-Key' => $plain], 'json' => self::LEAD]);
        $this->assertResponseStatusCodeSame(429);
        $this->assertResponseHasHeader('Retry-After');

        $client->request('POST', '/api/leads', ['headers' => ['X-Api-Key' => $otherPlain], 'json' => self::LEAD]);
        $this->assertResponseStatusCodeSame(201);
    }
}
