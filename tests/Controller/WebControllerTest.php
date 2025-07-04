<?php

namespace CaRMen\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class WebControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
    }
}
