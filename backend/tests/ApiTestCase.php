<?php

namespace App\Tests;

use ApiPlatform\Symfony\Bundle\Test\ApiTestCase as BaseApiTestCase;
use ApiPlatform\Symfony\Bundle\Test\Client;
use App\Tests\DataFixtures\TestFixtures;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;

abstract class ApiTestCase extends BaseApiTestCase
{
    private static bool $fixturesLoaded = false;

    protected static function loadFixtures(): void
    {
        if (self::$fixturesLoaded) {
            return;
        }

        $container = static::getContainer();
        $em        = $container->get('doctrine')->getManager();
        $fixture   = $container->get(TestFixtures::class);

        $loader = new Loader();
        $loader->addFixture($fixture);

        $purger   = new ORMPurger($em);
        $executor = new ORMExecutor($em, $purger);
        $executor->execute($loader->getFixtures());

        self::$fixturesLoaded = true;
    }

    protected static function resetFixtures(): void
    {
        self::$fixturesLoaded = false;
        static::loadFixtures();
    }

    protected function getToken(string $email, string $password): string
    {
        $client   = static::createClient();
        $response = $client->request('POST', '/api/login', [
            'json' => ['email' => $email, 'password' => $password],
        ]);
        return $response->toArray()['token'];
    }

    protected function adminClient(): Client
    {
        return static::createClient([], [
            'auth_bearer' => $this->getToken(TestFixtures::ADMIN_EMAIL, TestFixtures::ADMIN_PASSWORD),
        ]);
    }

    protected function salesClient(): Client
    {
        return static::createClient([], [
            'auth_bearer' => $this->getToken(TestFixtures::SALES_EMAIL, TestFixtures::SALES_PASSWORD),
        ]);
    }

    protected function userClient(): Client
    {
        return static::createClient([], [
            'auth_bearer' => $this->getToken(TestFixtures::USER_EMAIL, TestFixtures::USER_PASSWORD),
        ]);
    }
}
