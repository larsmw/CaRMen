<?php

namespace App\Tests\Controller;

use App\Tests\ApiTestCase;
use App\Tests\DataFixtures\TestFixtures;
use Faker\Factory;

class ContactTest extends ApiTestCase
{
    protected function setUp(): void
    {
        static::loadFixtures();
    }

    public function testListContactsRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/contacts');
        $this->assertResponseStatusCodeSame(401);
    }

    public function testListContactsReturnsCollection(): void
    {
        $client   = $this->adminClient();
        $response = $client->request('GET', '/api/contacts');

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('hydra:member', $data);
        $this->assertArrayHasKey('hydra:totalItems', $data);
        $this->assertGreaterThanOrEqual(TestFixtures::CONTACT_COUNT, $data['hydra:totalItems']);
    }

    public function testCreateContactAsSales(): void
    {
        $faker    = Factory::create();
        $client   = $this->salesClient();
        $response = $client->request('POST', '/api/contacts', [
            'json' => [
                'firstName' => $faker->firstName(),
                'lastName'  => $faker->lastName(),
                'email'     => $faker->unique()->safeEmail(),
                'phone'     => $faker->phoneNumber(),
                'status'    => 'lead',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $this->assertArrayHasKey('@id', $data);
        $this->assertSame('lead', $data['status']);
    }

    public function testCreateContactAsUserForbidden(): void
    {
        $faker  = Factory::create();
        $client = $this->userClient();
        $client->request('POST', '/api/contacts', [
            'json' => [
                'firstName' => $faker->firstName(),
                'lastName'  => $faker->lastName(),
                'status'    => 'lead',
            ],
        ]);

        $this->assertResponseStatusCodeSame(403);
    }

    public function testGetContact(): void
    {
        $client   = $this->adminClient();
        $list     = $client->request('GET', '/api/contacts?itemsPerPage=1')->toArray();
        $iri      = $list['hydra:member'][0]['@id'];

        $response = $client->request('GET', $iri);
        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('firstName', $data);
        $this->assertArrayHasKey('email', $data);
        $this->assertArrayHasKey('status', $data);
    }

    public function testPatchContactAsSales(): void
    {
        $client = $this->salesClient();
        $list   = $client->request('GET', '/api/contacts?itemsPerPage=1')->toArray();
        $iri    = $list['hydra:member'][0]['@id'];

        $response = $client->request('PATCH', $iri, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json'    => ['status' => 'customer', 'jobTitle' => 'CTO'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame('customer', $response->toArray()['status']);
    }

    public function testUserCannotDeleteContact(): void
    {
        $adminList = $this->adminClient()->request('GET', '/api/contacts?itemsPerPage=1')->toArray();
        $iri       = $adminList['hydra:member'][0]['@id'];

        $response = $this->userClient()->request('DELETE', $iri);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testAdminCanDeleteContact(): void
    {
        $faker  = Factory::create();
        $admin  = $this->adminClient();

        $created = $admin->request('POST', '/api/contacts', [
            'json' => [
                'firstName' => $faker->firstName(),
                'lastName'  => $faker->lastName(),
                'status'    => 'lead',
            ],
        ])->toArray();

        $deleteResponse = $admin->request('DELETE', $created['@id']);
        $this->assertSame(204, $deleteResponse->getStatusCode());
    }

    public function testAddressFieldsPersist(): void
    {
        $faker  = Factory::create();
        $client = $this->salesClient();

        $response = $client->request('POST', '/api/contacts', [
            'json' => [
                'firstName'    => $faker->firstName(),
                'lastName'     => $faker->lastName(),
                'status'       => 'prospect',
                'addressLine1' => '123 Main St',
                'city'         => 'Springfield',
                'postalCode'   => '12345',
                'country'      => 'US',
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $this->assertSame('123 Main St', $data['addressLine1']);
        $this->assertSame('Springfield', $data['city']);
    }

    public function testPaginationStructure(): void
    {
        $client   = $this->adminClient();
        $response = $client->request('GET', '/api/contacts');

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('hydra:member', $data);
        $this->assertArrayHasKey('hydra:totalItems', $data);
        $this->assertGreaterThan(0, $data['hydra:totalItems']);
        $this->assertIsArray($data['hydra:member']);
    }
}
