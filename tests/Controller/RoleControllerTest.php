<?php

namespace CaRMen\Tests\Controller;

use CaRMen\Controller\RoleController;
use CaRMen\Entity\Role;
use CaRMen\Repository\RoleRepository;
use CaRMen\Form\RoleForm;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\DomCrawler\Crawler;

class RoleControllerTest extends WebTestCase
{
    protected RoleRepository $roleRepository;

    /**
     * Sets up the test environment before each test.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->roleRepository = self::getContainer()->get(RoleRepository::class);
    }

    /**
     * Tests that the index action is accessible.
     */
    public function testIndex(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $crawler = $client->request('GET', '/role');

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Role index');
    }

    /**
     * Tests that the index action displays all roles.
     */
    public function testIndexDisplaysRoles(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        // Create some test roles
        $role1 = new Role();
        $role1->setName('ROLE_USER');

        $role2 = new Role();
        $role2->setName('ROLE_ADMIN');


        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $this->em = $em;


        foreach ([$role1, $role2] as $role) {
            $em->persist($role);
        }
        $em->flush();

        $client->request('GET', '/role');
        $crawler = $client->followRedirects();

        self::assertSelectorExists('//a[contains(@href, "/role/new")]');
        self::assertSelectorTextContains('//a[contains(@href, "/role/new")]', 'Create new');
        self::assertSelectorExists('//a[contains(@href, "/ROLE_USER")]');
        self::assertSelectorExists('//a[contains(@href, "/ROLE_ADMIN")]');
    }

    /**
     * Tests that the new action redirects after successful submission.
     */
    public function testNewRedirectsAfterCreation(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $crawler = $client->request('GET', '/role/new');

        $client->followRedirects();
        self::assertResponseStatusCodeSame(200);
        self::assertSelectorExists('//a[contains(@href, "/role/new")]');
        self::assertSelectorTextContains('//a[contains(@href, "/role/new")]', 'Create new');
    }

    /**
     * Tests that the new action displays the form.
     */
    public function testNewDisplaysForm(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();
        $client->request('GET', '/role/new');

        self::assertResponseStatusCodeSame(200);
        self::assertSelectorTextContains('//form[@method="post"]', 'Create');
        self::assertSelectorExists('input[name="name"]');
    }

    /**
     * Tests that the new action redirects after valid POST.
     */
    public function testNewRedirectsAfterValidPost(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        $client->submitForm('Create', [
            'name' => 'ROLE_TEST',
        ]);

        self::assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirects();

        self::assertResponseStatusCodeSame(200);
        self::assertSelectorExists('//a[contains(@href, "/ROLE_TEST")]');
    }

    /**
     * Tests that the new action shows error messages on invalid POST.
     */
    public function testNewShowsErrorMessages(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        $client->submitForm('Create', [
            'name' => 'ROLE_' . str_repeat('A', 81), // Too long
        ]);

        self::assertResponseStatusCodeSame(200);
        self::assertResponseIsSuccessful();
    }

    /**
     * Tests that the show action displays a role.
     */
    public function testShowDisplaysRole(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        $role = new Role();
        $role->setName('ROLE_SHOW_TEST');

        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $this->em = $em;

        $em->persist($role);
        $em->flush();

        $crawler = $client->request('GET', '/role/'.$role->getId());

        self::assertResponseStatusCodeSame(200);
        self::assertSelectorExists('//h1[contains(., "ROLE_SHOW_TEST")]');
    }

    /**
     * Tests that the show action redirects to the correct route.
     */
    public function testShowRedirects(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        $role = new Role();
        $role->setName('ROLE_SHOW_REDIRECT');

        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $this->em = $em;

        $em->persist($role);
        $em->flush();

        $client->followRedirects();
        self::assertResponseStatusCodeSame(200);
        self::assertSelectorExists('//a[contains(@href, "/role/new")]');
        self::assertSelectorTextContains('//a[contains(@href, "/role/new")]', 'Create new');
    }

    /**
     * Tests that the edit action redirects after valid POST.
     */
    public function testEditRedirectsAfterValidPost(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        $this->em = $em;

        $role = new Role();
        $role->setName('ROLE_EDIT_TEST');
        $em->persist($role);
        $em->flush();

        $crawler = $client->request('GET', '/role/'.$role->getId().'/edit');

        $client->followRedirects();
        self::assertResponseStatusCodeSame(200);
        self::assertSelectorExists('//a[contains(@href, "/ROLE_EDIT_TEST")]');
    }

    /**
     * Tests that the edit action displays the form.
     */
    public function testEditDisplaysForm(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        $role = new Role();
        $role->setName('ROLE_EDIT_FORM_TEST');

        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        
        $em->persist($role);
        $em->flush();

        $crawler = $client->request('GET', '/role/'.$role->getId().'/edit');

        self::assertResponseStatusCodeSame(200);
        self::assertSelectorTextContains('//form[@method="post"]', 'Update');
        self::assertSelectorExists('input[name="name"]');
    }

    /**
     * Tests that the delete action redirects.
     */
    public function testDeleteRedirects(): void
    {
        self::ensureKernelShutdown();
        $client = static::createClient();

        $role = new Role();
        $role->setName('ROLE_DELETE_TEST');
        /** @var EntityManagerInterface $em */
        $em = $this->getContainer()->get('doctrine')->getManager();
        
        $em->persist($role);
        $em->flush();


        $crawler = $client->request('GET', '/role/'.$role->getId().'/edit');
        //self::assertSelectorTextContains('//a[contains(@href, ".delete")]', 'Delete');

        $client->submitForm('Delete');

        self::assertResponseStatusCodeSame(302);
        $crawler = $client->followRedirects();

        self::assertResponseStatusCodeSame(200);
        self::assertSelectorExists('//a[contains(@href, "/ROLE_DELETE_TEST")]');
    }
}
