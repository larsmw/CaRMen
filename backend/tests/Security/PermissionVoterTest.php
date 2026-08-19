<?php

namespace App\Tests\Security;

use App\Entity\RolePermissions;
use App\Entity\User;
use App\Tests\ApiTestCase;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PermissionVoterTest extends ApiTestCase
{
    protected function setUp(): void
    {
        static::loadFixtures();
    }

    private function seedPermission(string $role, array $permissions): void
    {
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $em->clear();
        $rp = $em->getRepository(RolePermissions::class)->find($role);
        if (!$rp) {
            $rp = new RolePermissions($role);
            $em->persist($rp);
        }
        $rp->setPermissions($permissions);
        $em->flush();
    }

    public function testAdminCanCreateAndDeleteWithoutExplicitPermissions(): void
    {
        $admin   = $this->adminClient();
        $contact = $admin->request('POST', '/api/contacts', [
            'json' => ['firstName' => 'Test', 'lastName' => 'Admin', 'status' => 'lead'],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $deleteResponse = $admin->request('DELETE', $contact->toArray()['@id']);
        $this->assertSame(204, $deleteResponse->getStatusCode());
    }

    public function testUserWithGrantedPermissionCanCreateContact(): void
    {
        $this->seedPermission('ROLE_USER', ['CONTACT_CREATE']);

        $response = $this->userClient()->request('POST', '/api/contacts', [
            'json' => ['firstName' => 'Granted', 'lastName' => 'User', 'status' => 'lead'],
        ]);
        $this->assertResponseStatusCodeSame(201);

        $this->seedPermission('ROLE_USER', []);
    }

    public function testUserWithoutPermissionCannotCreateContact(): void
    {
        $this->seedPermission('ROLE_USER', []);

        $this->userClient()->request('POST', '/api/contacts', [
            'json' => ['firstName' => 'Blocked', 'lastName' => 'User', 'status' => 'lead'],
        ]);
        $this->assertResponseStatusCodeSame(403);
    }

    public function testSalesCanDeleteContactWhenGranted(): void
    {
        $admin   = $this->adminClient();
        $created = $admin->request('POST', '/api/contacts', [
            'json' => ['firstName' => 'ToDelete', 'lastName' => 'BySales', 'status' => 'lead'],
        ])->toArray();

        $noPermResponse = $this->salesClient()->request('DELETE', $created['@id']);
        $this->assertSame(403, $noPermResponse->getStatusCode());

        $this->seedPermission('ROLE_SALES', ['CONTACT_DELETE']);

        $deleteResponse = $this->salesClient()->request('DELETE', $created['@id']);
        $this->assertSame(204, $deleteResponse->getStatusCode());

        $this->seedPermission('ROLE_SALES', []);
    }

    public function testManagerInheritsSalesPermissionsViaHierarchy(): void
    {
        $this->seedPermission('ROLE_SALES',   ['CONTACT_CREATE']);
        $this->seedPermission('ROLE_MANAGER', []);

        $em     = static::getContainer()->get(EntityManagerInterface::class);
        $hasher = static::getContainer()->get(UserPasswordHasherInterface::class);

        $manager = new User();
        $manager->setEmail('manager.voter.test@test.local')
            ->setFirstName('Test')
            ->setLastName('Manager')
            ->setRoles(['ROLE_MANAGER'])
            ->setPassword($hasher->hashPassword($manager, 'Mgr1234!'))
            ->setIsActive(true);
        $em->persist($manager);
        $em->flush();
        $managerId = (string) $manager->getId();

        $client = static::createClient([], [
            'auth_bearer' => $this->getToken('manager.voter.test@test.local', 'Mgr1234!'),
        ]);

        // ROLE_MANAGER inherits CONTACT_CREATE from ROLE_SALES via hierarchy
        $response = $client->request('POST', '/api/contacts', [
            'json' => ['firstName' => 'Manager', 'lastName' => 'Test', 'status' => 'prospect'],
        ]);
        $this->assertResponseStatusCodeSame(201);

        // Cleanup: get a fresh EM reference after HTTP requests may have cleared it
        $freshEm = static::getContainer()->get(EntityManagerInterface::class);
        $freshEm->clear();
        $managerToRemove = $freshEm->getRepository(User::class)->find($managerId);
        if ($managerToRemove) {
            $freshEm->remove($managerToRemove);
            $freshEm->flush();
        }
        $this->seedPermission('ROLE_SALES', []);
    }
}
