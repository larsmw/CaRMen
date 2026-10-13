<?php

namespace App\Tests\Controller;

use App\Entity\ApiKey;
use App\Tests\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;

class ApiKeyTest extends ApiTestCase
{
    protected function setUp(): void
    {
        static::resetFixtures();
    }

    public function testAdminCreatesKeyAndSeesItOnlyOnce(): void
    {
        $client   = $this->adminClient();
        $response = $client->request('POST', '/api/api-keys', ['json' => ['name' => 'Website form']]);
        $this->assertResponseStatusCodeSame(201);

        $created = $response->toArray();
        $this->assertMatchesRegularExpression('/^crm_[0-9a-f]{8}_[A-Za-z0-9_-]{32}$/', $created['key']);
        $this->assertSame('Website form', $created['name']);
        $this->assertSame(['leads:create'], $created['scopes']);

        $list = $client->request('GET', '/api/api-keys')->toArray();
        $this->assertCount(1, $list);
        $this->assertArrayNotHasKey('key', $list[0]);
        $this->assertStringNotContainsString(substr($created['key'], 14), json_encode($list));

        // Only the hash is stored
        $em  = static::getContainer()->get(EntityManagerInterface::class);
        $row = $em->getConnection()->fetchAssociative('SELECT * FROM api_key');
        $this->assertSame(hash('sha256', $created['key']), $row['key_hash']);
        $this->assertStringNotContainsString(substr($created['key'], 14), json_encode($row));
    }

    public function testNameIsRequired(): void
    {
        $this->adminClient()->request('POST', '/api/api-keys', ['json' => ['name' => '  ']]);
        $this->assertResponseStatusCodeSame(422);
    }

    public function testOnlyAdminsManageKeys(): void
    {
        // Create each client just before use: response assertions check the latest client
        foreach (['salesClient', 'userClient'] as $makeClient) {
            $client = $this->$makeClient();
            $client->request('GET', '/api/api-keys');
            $this->assertResponseStatusCodeSame(403);
            $client->request('POST', '/api/api-keys', ['json' => ['name' => 'x']]);
            $this->assertResponseStatusCodeSame(403);
        }

        static::createClient()->request('GET', '/api/api-keys');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testRevokeKeepsTheKeyListed(): void
    {
        $client = $this->adminClient();
        $id     = $client->request('POST', '/api/api-keys', ['json' => ['name' => 'Old form']])->toArray()['id'];

        $revoked = $client->request('DELETE', "/api/api-keys/$id")->toArray();
        $this->assertNotNull($revoked['revokedAt']);

        $list = $client->request('GET', '/api/api-keys')->toArray();
        $this->assertNotNull($list[0]['revokedAt']);
    }

    public function testKeyFormat(): void
    {
        [, $plain] = ApiKey::generate('x', [ApiKey::SCOPE_LEADS_CREATE], null);
        $this->assertNotNull(ApiKey::prefixOf($plain));
        $this->assertNull(ApiKey::prefixOf('crm_nothex!!_' . str_repeat('a', 32)));
        $this->assertNull(ApiKey::prefixOf('Bearer something'));
    }
}
