<?php

namespace CaRMen\Tests;

use CaRMen\Repository\UserRepository;
use Doctrine\ORM\EntityManager;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class RegistrationControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private UserRepository $userRepository;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = static::createClient();

        // Ensure we have a clean database
        $container = static::getContainer();
        
        $em = $container->get('doctrine')->getManager();
        $this->userRepository = $container->get(UserRepository::class);

        foreach ($this->userRepository->findAll() as $user) {
            if ($user->getUsername() == 'test_me') {
              $em->remove($user);
            }
        }

        $em->flush();
    }

    protected function tearDown() : void {
      // Ensure we have a clean database
      $container = static::getContainer();
        
      $em = $container->get('doctrine')->getManager();
      $this->userRepository = $container->get(UserRepository::class);

      foreach ($this->userRepository->findAll() as $user) {
          if ($user->getUsername() == 'test_me') {
              $em->remove($user);
          }
      }

      $em->flush();
    }

    public function testRegister(): void
    {
        // Register a new user
        $this->client->request('GET', 'https://carmen.ddev.site/register');
        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('Register');

        $this->client->submitForm('Register', [
            'registration_form[username]' => 'test_me',
            'registration_form[email]' => 'me@example.com',
            'registration_form[plainPassword]' => 'password',
            'registration_form[agreeTerms]' => true,
        ]);

        // Ensure the response is succesful after submitting the form and user exists
        //self::assertResponseIsSuccessful();
        self::assertResponseRedirects('/'); // @TODO: set the appropriate path that the user is redirected to.
        $users = $this->userRepository->findAll();
        $user = array_pop($users);
        // Ensure the verification email was sent
        // Use either assertQueuedEmailCount() || assertEmailCount() depending on your mailer setup
        // self::assertQueuedEmailCount(1);
        // Login the new user
        //$this->client->followRedirect();
        //$this->client->loginUser($user);
        $this->client->request('GET', 'https://carmen.ddev.site/login');
        self::assertResponseIsSuccessful();

        $this->client->submitForm('Sign in', [
            '_username' => 'test_me',
            '_password' => 'password',
        ]);

        // Ensure the response is succesful after submitting the form and user exists
        self::assertResponseRedirects('/');

    }
}
