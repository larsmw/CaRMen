<?php

namespace App\Tests\Controller;

use App\Entity\Contact;
use App\Entity\Deal;
use App\Entity\User;
use App\Tests\ApiTestCase;
use App\Tests\DataFixtures\TestFixtures;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class DemoTest extends ApiTestCase
{
    protected function setUp(): void
    {
        static::resetFixtures();
    }

    protected function tearDown(): void
    {
        unset($_SERVER['DEMO_ACCOUNT_EMAIL'], $_ENV['DEMO_ACCOUNT_EMAIL']);
        static::resetFixtures();
        parent::tearDown();
    }

    private function enableDemoMode(string $email): void
    {
        $_SERVER['DEMO_ACCOUNT_EMAIL'] = $_ENV['DEMO_ACCOUNT_EMAIL'] = $email;
    }

    private function userId(string $email): string
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        return (string) $em->getRepository(User::class)->findOneBy(['email' => $email])->getId();
    }

    private function patchUser(string $id, array $data): void
    {
        $this->adminClient()->request('PATCH', "/api/users/$id", [
            'headers' => ['Content-Type' => 'application/merge-patch+json'],
            'json'    => $data,
        ]);
    }

    public function testDemoAccountCannotBeChanged(): void
    {
        $this->enableDemoMode(TestFixtures::SALES_EMAIL);

        $this->patchUser($this->userId(TestFixtures::SALES_EMAIL), ['plainPassword' => 'Hijacked123!']);
        $this->assertResponseStatusCodeSame(403);

        // Old password still works
        $this->getToken(TestFixtures::SALES_EMAIL, TestFixtures::SALES_PASSWORD);
    }

    public function testOtherUsersCanStillBeChangedInDemoMode(): void
    {
        $this->enableDemoMode(TestFixtures::SALES_EMAIL);

        $this->patchUser($this->userId(TestFixtures::USER_EMAIL), ['firstName' => 'Renamed']);
        $this->assertResponseIsSuccessful();
    }

    public function testPasswordResetDisabledInDemoMode(): void
    {
        $this->enableDemoMode(TestFixtures::SALES_EMAIL);

        static::createClient()->request('POST', '/api/forgot-password', ['json' => ['email' => TestFixtures::SALES_EMAIL]]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testDemoModeOffByDefault(): void
    {
        $this->patchUser($this->userId(TestFixtures::USER_EMAIL), ['firstName' => 'Renamed']);
        $this->assertResponseIsSuccessful();

        static::createClient()->request('POST', '/api/forgot-password', ['json' => ['email' => TestFixtures::SALES_EMAIL]]);
        $this->assertResponseIsSuccessful();
    }

    public function testDemoLoadCommand(): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        (new ORMPurger($em))->purge();
        $em->clear();

        $tester = new CommandTester((new Application(static::$kernel))->find('app:demo:load'));
        $tester->execute(['--email' => 'demo@example.com', '--password' => 'demo1234']);
        $tester->assertCommandIsSuccessful();

        $this->assertSame(1, $em->getRepository(User::class)->count([]));
        $this->assertGreaterThan(0, $em->getRepository(Contact::class)->count([]));
        $this->assertGreaterThan(0, $em->getRepository(Deal::class)->count([]));
        $this->getToken('demo@example.com', 'demo1234');

        // Refuses to run on a database that already has users
        $tester->execute(['--email' => 'other@example.com', '--password' => 'demo1234']);
        $this->assertSame(1, $tester->getStatusCode());
    }
}
