<?php

namespace App\Tests\Controller;

use App\Tests\ApiTestCase;
use App\Tests\DataFixtures\TestFixtures;
use Faker\Factory;

class ActivityTest extends ApiTestCase
{
    protected function setUp(): void
    {
        static::loadFixtures();
    }

    public function testListActivities(): void
    {
        $response = $this->salesClient()->request('GET', '/api/activities');
        $this->assertResponseIsSuccessful();
        $this->assertGreaterThanOrEqual(TestFixtures::ACTIVITY_COUNT, $response->toArray()['hydra:totalItems']);
    }

    public function testCreateActivity(): void
    {
        $faker      = Factory::create();
        $client     = $this->salesClient();
        $contactIri = $client->request('GET', '/api/contacts?itemsPerPage=1')->toArray()['hydra:member'][0]['@id'];

        $response = $client->request('POST', '/api/activities', [
            'json' => [
                'type'        => 'call',
                'subject'     => $faker->sentence(5),
                'status'      => 'planned',
                'contact'     => $contactIri,
                'scheduledAt' => (new \DateTimeImmutable('+1 day'))->format(\DateTimeInterface::ATOM),
            ],
        ]);

        $this->assertResponseStatusCodeSame(201);
        $data = $response->toArray();
        $this->assertSame('call', $data['type']);
        $this->assertSame('planned', $data['status']);
    }

    public function testPatchActivityStatus(): void
    {
        $client = $this->salesClient();
        $iri    = $client->request('GET', '/api/activities?itemsPerPage=1')->toArray()['hydra:member'][0]['@id'];

        $response = $client->request('PATCH', $iri, [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json'    => ['status' => 'completed'],
        ]);

        $this->assertResponseIsSuccessful();
        $this->assertSame('completed', $response->toArray()['status']);
    }

    public function testDeleteActivityAsSalesForbidden(): void
    {
        $iri = $this->adminClient()->request('GET', '/api/activities?itemsPerPage=1')->toArray()['hydra:member'][0]['@id'];
        $this->salesClient()->request('DELETE', $iri);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testDeleteActivityAsAdmin(): void
    {
        $faker   = Factory::create();
        $admin   = $this->adminClient();
        $contact = $admin->request('GET', '/api/contacts?itemsPerPage=1')->toArray()['hydra:member'][0]['@id'];

        $created = $admin->request('POST', '/api/activities', [
            'json' => [
                'type'    => 'note',
                'subject' => $faker->sentence(3),
                'status'  => 'completed',
                'contact' => $contact,
            ],
        ])->toArray();

        $admin->request('DELETE', $created['@id']);
        $this->assertResponseStatusCodeSame(204);
    }
}
