<?php

namespace CaRMen\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CustomerControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', 'http://localhost/customer');

        self::assertResponseIsSuccessful();
    }
}
