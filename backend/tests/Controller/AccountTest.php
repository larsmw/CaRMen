<?php

namespace App\Tests\Controller;

use App\Tests\ApiTestCase;
use App\Tests\DataFixtures\TestFixtures;
use Faker\Factory;

class AccountTest extends ApiTestCase
{
    protected function setUp(): void
    {
        static::loadFixtures();
    }

    public function testListAccountsRequiresAuth(): void
    {
        static::createClient()->request('GET', '/api/accounts');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testListAccountsReturnsSeededData(): void
    {
        $response = $this->adminClient()->request('GET', '/api/accounts');
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertGreaterThanOrEqual(TestFixtures::ACCOUNT_COUNT, $data['hydra:totalItems']);
    }

    public function testCreateAccountAsSales(): void
    {
        $faker    = Factory::create();
        $response = $this->salesClient()->request('POST', '/api/accounts', [
            'json' => [
                'name'     => $faker->company(),
                'industry' => 'Technology',
                'country'  => $faker->country(),
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertSame('Technology', $response->toArray()['industry']);
    }

    public function testCreateAccountAsUserForbidden(): void
    {
        $this->userClient()->request('POST', '/api/accounts', [
            'json' => ['name' => 'Forbidden Co'],
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testPatchAccountAsSales(): void
    {
        $client = $this->salesClient();
        $iri    = $client->request('GET', '/api/accounts?itemsPerPage=1')->toArray()['hydra:member'][0]['@id'];

        $response = $client->request('PATCH', $iri, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json'    => ['industry' => 'Finance'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame('Finance', $response->toArray()['industry']);
    }

    public function testSalesCannotDeleteAccount(): void
    {
        $faker   = Factory::create();
        $admin   = $this->adminClient();
        $created = $admin->request('POST', '/api/accounts', [
            'json' => ['name' => $faker->company()],
        ])->toArray();

        $salesResponse = $this->salesClient()->request('DELETE', $created['@id']);
        $this->assertSame(403, $salesResponse->getStatusCode());
    }

    public function testAdminCanDeleteAccount(): void
    {
        $faker   = Factory::create();
        $admin   = $this->adminClient();
        $created = $admin->request('POST', '/api/accounts', [
            'json' => ['name' => $faker->company()],
        ])->toArray();

        $deleteResponse = $admin->request('DELETE', $created['@id']);
        $this->assertSame(204, $deleteResponse->getStatusCode());
    }
}
