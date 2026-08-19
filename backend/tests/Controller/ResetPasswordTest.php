<?php

namespace App\Tests\Controller;

use App\Tests\ApiTestCase;
use App\Tests\DataFixtures\TestFixtures;
use Doctrine\ORM\EntityManagerInterface;

class ResetPasswordTest extends ApiTestCase
{
    protected function setUp(): void
    {
        static::resetFixtures();
    }

    public function testForgotPasswordAlwaysReturns200(): void
    {
        $client = static::createClient();

        // Known email
        $client->request('POST', '/api/forgot-password', [
            'json' => ['email' => TestFixtures::SALES_EMAIL],
        ]);
        $this->assertResponseIsSuccessful();

        // Unknown email — still 200 (no enumeration)
        $client->request('POST', '/api/forgot-password', [
            'json' => ['email' => 'nobody@example.com'],
        ]);
        $this->assertResponseIsSuccessful();
    }

    public function testForgotPasswordSetsTokenOnUser(): void
    {
        $client = static::createClient();
        $client->request('POST', '/api/forgot-password', [
            'json' => ['email' => TestFixtures::SALES_EMAIL],
        ]);

        $em   = static::getContainer()->get(EntityManagerInterface::class);
        $user = $em->getRepository(\App\Entity\User::class)->findOneBy(['email' => TestFixtures::SALES_EMAIL]);

        $this->assertNotNull($user->getResetToken());
        $this->assertGreaterThan(new \DateTimeImmutable(), $user->getResetTokenExpiresAt());
    }

    public function testResetPasswordWithValidToken(): void
    {
        // Use a dedicated user so we don't break admin/sales logins for other tests
        static::createClient()->request('POST', '/api/forgot-password', [
            'json' => ['email' => TestFixtures::USER_EMAIL],
        ]);

        $em   = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $user  = $em->getRepository(\App\Entity\User::class)->findOneBy(['email' => TestFixtures::USER_EMAIL]);
        $token = $user->getResetToken();

        $this->assertNotNull($token);

        $client = static::createClient();
        $client->request('POST', '/api/reset-password', [
            'json' => ['token' => $token, 'password' => 'NewPassword99!'],
        ]);
        $this->assertResponseIsSuccessful();

        // Token is cleared
        $em->clear();
        $user = $em->getRepository(\App\Entity\User::class)->findOneBy(['email' => TestFixtures::USER_EMAIL]);
        $this->assertNull($user->getResetToken());

        // Can log in with new password
        $response = $client->request('POST', '/api/login', [
            'json' => ['email' => TestFixtures::USER_EMAIL, 'password' => 'NewPassword99!'],
        ]);
        $this->assertResponseIsSuccessful();
        $this->assertArrayHasKey('token', $response->toArray());
    }

    public function testResetPasswordWithInvalidTokenFails(): void
    {
        static::createClient()->request('POST', '/api/reset-password', [
            'json' => ['token' => 'invalidtoken', 'password' => 'NewPassword99!'],
        ]);
        $this->assertResponseStatusCodeSame(422);
    }

    public function testResetPasswordTooShortFails(): void
    {
        static::createClient()->request('POST', '/api/reset-password', [
            'json' => ['token' => 'anytoken', 'password' => 'short'],
        ]);
        $this->assertResponseStatusCodeSame(422);
    }
}
