<?php

namespace CaRMen\Tests\Controller;

use CaRMen\Entity\Project;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ProjectControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $projectRepository;
    private string $path = '/project/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->projectRepository = $this->manager->getRepository(Project::class);

        foreach ($this->projectRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Project index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'project[WinnerFile]' => 'Testing',
            'project[customer]' => 'Testing',
        ]);

        self::assertResponseRedirects($this->path);

        self::assertSame(1, $this->projectRepository->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Project();
        $fixture->setWinnerFile('My Title');
        $fixture->setCustomer('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Project');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Project();
        $fixture->setWinnerFile('Value');
        $fixture->setCustomer('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'project[WinnerFile]' => 'Something New',
            'project[customer]' => 'Something New',
        ]);

        self::assertResponseRedirects('/project/');

        $fixture = $this->projectRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getWinnerFile());
        self::assertSame('Something New', $fixture[0]->getCustomer());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new Project();
        $fixture->setWinnerFile('Value');
        $fixture->setCustomer('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/project/');
        self::assertSame(0, $this->projectRepository->count([]));
    }
}
