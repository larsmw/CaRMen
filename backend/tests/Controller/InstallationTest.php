<?php

namespace App\Tests\Controller;

use App\Entity\Account;
use App\Entity\Activity;
use App\Entity\Contact;
use App\Entity\Deal;
use App\Entity\User;
use App\Tests\ApiTestCase;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;

class InstallationTest extends ApiTestCase
{
    private const ADMIN = [
        'email'     => 'first@example.com',
        'firstName' => 'First',
        'lastName'  => 'Admin',
        'password'  => 'firstadmin123',
    ];

    protected function setUp(): void
    {
        static::resetFixtures();
    }

    protected function tearDown(): void
    {
        // Other tests rely on the fixture users existing
        static::resetFixtures();
        parent::tearDown();
    }

    private function emptyDatabase(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        (new ORMPurger($em))->purge();
        $em->clear();
    }

    public function testStatusIsPublicAndReportsInstalled(): void
    {
        $response = static::createClient()->request('GET', '/api/install/status');

        $this->assertResponseIsSuccessful();
        $this->assertTrue($response->toArray()['installed']);
    }

    public function testEndpointsAreLockedOnceUsersExist(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/install/system-check');
        $this->assertResponseStatusCodeSame(403);

        $client->request('POST', '/api/install/migrate');
        $this->assertResponseStatusCodeSame(403);

        $client->request('POST', '/api/install/create-admin', ['json' => self::ADMIN]);
        $this->assertResponseStatusCodeSame(403);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $this->assertNull($em->getRepository(User::class)->findOneBy(['email' => self::ADMIN['email']]));
    }

    public function testSystemCheckOnFreshInstall(): void
    {
        $this->emptyDatabase();

        $response = static::createClient()->request('GET', '/api/install/system-check');

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertTrue($data['dbConnectionOk']);
        $this->assertTrue($data['systemOk']);
        $this->assertSame(0, $data['pendingMigrations']);
    }

    public function testCreateAdminValidatesInput(): void
    {
        $this->emptyDatabase();

        $response = static::createClient()->request('POST', '/api/install/create-admin', [
            'json' => ['email' => 'nope', 'firstName' => '', 'lastName' => 'X', 'password' => 'short'],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $errors = $response->toArray(false)['errors'];
        $this->assertArrayHasKey('email', $errors);
        $this->assertArrayHasKey('firstName', $errors);
        $this->assertArrayHasKey('password', $errors);
    }

    public function testCreateFirstAdminThenLocks(): void
    {
        $this->emptyDatabase();
        $client = static::createClient();

        $client->request('POST', '/api/install/create-admin', ['json' => self::ADMIN]);
        $this->assertResponseStatusCodeSame(201);

        $client->request('POST', '/api/login', [
            'json' => ['email' => self::ADMIN['email'], 'password' => self::ADMIN['password']],
        ]);
        $this->assertResponseIsSuccessful();

        $em   = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(User::class)->findOneBy(['email' => self::ADMIN['email']]);
        $this->assertContains('ROLE_ADMIN', $user->getRoles());

        // A second attempt must be refused
        $client->request('POST', '/api/install/create-admin', [
            'json' => ['email' => 'second@example.com'] + self::ADMIN,
        ]);
        $this->assertResponseStatusCodeSame(403);

        $response = $client->request('GET', '/api/install/status');
        $this->assertTrue($response->toArray()['installed']);
    }

    public function testCreateAdminWithoutDemoDataLeavesDatabaseEmpty(): void
    {
        $this->emptyDatabase();

        static::createClient()->request('POST', '/api/install/create-admin', ['json' => self::ADMIN]);
        $this->assertResponseStatusCodeSame(201);

        $em = static::getContainer()->get(EntityManagerInterface::class);
        $this->assertSame(1, $em->getRepository(User::class)->count([]));
        $this->assertSame(0, $em->getRepository(Contact::class)->count([]));
        $this->assertSame(0, $em->getRepository(Deal::class)->count([]));
    }

    public function testCreateAdminWithDemoData(): void
    {
        $this->emptyDatabase();

        static::createClient()->request('POST', '/api/install/create-admin', [
            'json' => self::ADMIN + ['demoData' => true],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $em    = static::getContainer()->get(EntityManagerInterface::class);
        $admin = $em->getRepository(User::class)->findOneBy(['email' => self::ADMIN['email']]);

        // No extra (well-known-password) users, just the admin
        $this->assertSame(1, $em->getRepository(User::class)->count([]));
        $this->assertGreaterThan(0, $em->getRepository(Account::class)->count([]));
        $this->assertGreaterThan(0, $em->getRepository(Contact::class)->count([]));
        $this->assertGreaterThan(0, $em->getRepository(Activity::class)->count([]));

        $deals = $em->getRepository(Deal::class)->findAll();
        $this->assertNotEmpty($deals);
        foreach ($deals as $deal) {
            $this->assertSame($admin->getId(), $deal->getOwner()->getId());
        }
    }
}
