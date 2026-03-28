<?php

namespace App\Tests\Controller;

use App\Tests\ApiTestCase;
use App\Tests\DataFixtures\TestFixtures;
use Faker\Factory;

class DealTest extends ApiTestCase
{
    protected function setUp(): void
    {
        static::loadFixtures();
    }

    public function testListDeals(): void
    {
        $response = $this->adminClient()->request('GET', '/api/deals');
        $this->assertResponseIsSuccessful();
        $this->assertGreaterThanOrEqual(TestFixtures::DEAL_COUNT, $response->toArray()['hydra:totalItems']);
    }

    public function testCreateDealB2C(): void
    {
        $faker   = Factory::create();
        $client  = $this->salesClient();

        // Pick a contact IRI
        $contactIri = $client->request('GET', '/api/contacts?itemsPerPage=1')->toArray()['hydra:member'][0]['@id'];

        $response = $client->request('POST', '/api/deals', [
            'json' => [
                'title'          => $faker->bs(),
                'primaryContact' => $contactIri,
                'value'          => '9999.00',
                'currency'       => 'EUR',
                'stage'          => 'prospecting',
                'probability'    => 20,
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $this->assertSame('prospecting', $data['stage']);
        $this->assertNull($data['account']);
    }

    public function testCreateDealB2B(): void
    {
        $faker   = Factory::create();
        $client  = $this->salesClient();

        $accountIri = $client->request('GET', '/api/accounts?itemsPerPage=1')->toArray()['hydra:member'][0]['@id'];
        $contactIri = $client->request('GET', '/api/contacts?itemsPerPage=1')->toArray()['hydra:member'][0]['@id'];

        $response = $client->request('POST', '/api/deals', [
            'json' => [
                'title'          => $faker->bs(),
                'account'        => $accountIri,
                'primaryContact' => $contactIri,
                'value'          => '50000.00',
                'currency'       => 'EUR',
                'stage'          => 'proposal',
                'probability'    => 60,
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $this->assertNotNull($response->toArray()['account']);
    }

    public function testCreateDealWithoutContactOrAccountFails(): void
    {
        $this->salesClient()->request('POST', '/api/deals', [
            'json' => [
                'title'    => 'No contact or account',
                'value'    => '1000.00',
                'currency' => 'EUR',
                'stage'    => 'prospecting',
            ],
        ]);
        $this->assertResponseStatusCodeSame(422);
    }

    public function testPatchDealStage(): void
    {
        $client = $this->salesClient();
        $iri    = $client->request('GET', '/api/deals?itemsPerPage=1')->toArray()['hydra:member'][0]['@id'];

        $response = $client->request('PATCH', $iri, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json'    => ['stage' => 'closed_won', 'probability' => 100],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame('closed_won', $response->toArray()['stage']);
    }

    public function testDeleteDealAsUserForbidden(): void
    {
        $iri = $this->adminClient()->request('GET', '/api/deals?itemsPerPage=1')->toArray()['hydra:member'][0]['@id'];
        $this->userClient()->request('DELETE', $iri);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeleteDealAsAdmin(): void
    {
        $faker  = Factory::create();
        $admin  = $this->adminClient();
        $contact = $admin->request('GET', '/api/contacts?itemsPerPage=1')->toArray()['hydra:member'][0]['@id'];

        $created = $admin->request('POST', '/api/deals', [
            'json' => [
                'title'          => $faker->bs(),
                'primaryContact' => $contact,
                'value'          => '1.00',
                'currency'       => 'EUR',
                'stage'          => 'prospecting',
                'probability'    => 5,
            ],
        ])->toArray();

        $admin->request('DELETE', $created['@id']);
        $this->assertResponseStatusCodeSame(204);
    }
}
