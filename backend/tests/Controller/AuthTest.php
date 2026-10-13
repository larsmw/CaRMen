<?php

namespace App\Tests\Controller;

use App\Tests\ApiTestCase;
use App\Tests\DataFixtures\TestFixtures;

class AuthTest extends ApiTestCase
{
    protected function setUp(): void
    {
        static::loadFixtures();
    }

    public function testLoginSuccess(): void
    {
        $client   = static::createClient();
        $response = $client->request('POST', '/api/login', [
            'json' => [
                'email'    => TestFixtures::ADMIN_EMAIL,
                'password' => TestFixtures::ADMIN_PASSWORD,
            ],
        ]);

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertArrayHasKey('token', $data);
        $this->assertNotEmpty($data['token']);
    }

    public function testLoginWrongPassword(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/login', [
            'json' => [
                'email'    => TestFixtures::ADMIN_EMAIL,
                'password' => 'wrongpassword',
            ],
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testLoginUnknownEmail(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/login', [
            'json' => [
                'email'    => 'nobody@nowhere.com',
                'password' => 'whatever',
            ],
        ]);

        $this->assertResponseStatusCodeSame(401);
    }

    public function testMeReturnsCurrentUser(): void
    {
        $client   = $this->adminClient();
        $response = $client->request('GET', '/api/me');

        $this->assertResponseIsSuccessful();
        $data = $response->toArray();
        $this->assertSame(TestFixtures::ADMIN_EMAIL, $data['email']);
        $this->assertContains('ROLE_ADMIN', $data['roles']);
    }

    public function testMeRequiresAuth(): void
    {
        $client = static::createClient();
        $client->request('GET', '/api/me');

        $this->assertResponseStatusCodeSame(401);
    }

    public function testLoginIsThrottledAfterFiveFailures(): void
    {
        $client = static::createClient();
        $email  = 'brute-' . bin2hex(random_bytes(4)) . '@example.com';

        for ($i = 1; $i <= 5; $i++) {
            $response = $client->request('POST', '/api/login', ['json' => ['email' => $email, 'password' => "guess$i"]]);
            $this->assertResponseStatusCodeSame(401);
            $this->assertStringNotContainsString('Too many', $response->getContent(false));
        }

        $response = $client->request('POST', '/api/login', ['json' => ['email' => $email, 'password' => 'guess6']]);
        $this->assertResponseStatusCodeSame(401);
        $this->assertStringContainsString('Too many failed login attempts', $response->getContent(false));
    }

    public function testThrottlingIsPerAccount(): void
    {
        $client = static::createClient();
        for ($i = 1; $i <= 6; $i++) {
            $client->request('POST', '/api/login', ['json' => ['email' => 'someone-else@example.com', 'password' => 'x']]);
        }

        // A different account from the same IP can still log in
        $this->assertNotEmpty($this->getToken(TestFixtures::ADMIN_EMAIL, TestFixtures::ADMIN_PASSWORD));
    }

    public function testPublicRegistrationIsDisabled(): void
    {
        // Users are created by admins (POST /api/users) or the installer, never anonymously
        static::createClient()->request('POST', '/api/register', [
            'json' => ['email' => 'stranger@example.com', 'password' => 'NewPass123!', 'firstName' => 'A', 'lastName' => 'B'],
        ]);
        $this->assertResponseStatusCodeSame(404);

        $em = static::getContainer()->get(\Doctrine\ORM\EntityManagerInterface::class);
        $this->assertNull($em->getRepository(\App\Entity\User::class)->findOneBy(['email' => 'stranger@example.com']));
    }
}
