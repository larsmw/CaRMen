<?php

namespace CaRMen\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SearchControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/search');

        self::assertResponseIsSuccessful();
    }
}
