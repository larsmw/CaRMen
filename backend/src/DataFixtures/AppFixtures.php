<?php

namespace App\DataFixtures;

use App\Entity\Account;
use App\Entity\Activity;
use App\Entity\Contact;
use App\Entity\Deal;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        // Admin user
        $admin = new User();
        $admin->setEmail('admin@crm.local');
        $admin->setFirstName('Admin');
        $admin->setLastName('User');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->hasher->hashPassword($admin, 'admin123'));
        $manager->persist($admin);

        // Sales user
        $sales = new User();
        $sales->setEmail('sales@crm.local');
        $sales->setFirstName('Sales');
        $sales->setLastName('Rep');
        $sales->setRoles(['ROLE_SALES']);
        $sales->setPassword($this->hasher->hashPassword($sales, 'sales123'));
        $manager->persist($sales);

        // Accounts
        $acme = new Account();
        $acme->setName('Acme Corp');
        $acme->setIndustry('Technology');
        $acme->setWebsite('https://acme.example.com');
        $acme->setCountry('US');
        $acme->setEmployeeCount(500);
        $manager->persist($acme);

        $globex = new Account();
        $globex->setName('Globex Inc');
        $globex->setIndustry('Manufacturing');
        $globex->setCountry('DE');
        $manager->persist($globex);

        // Contacts
        $alice = new Contact();
        $alice->setFirstName('Alice');
        $alice->setLastName('Smith');
        $alice->setEmail('alice@acme.example.com');
        $alice->setJobTitle('CTO');
        $alice->setAccount($acme);
        $alice->setStatus('customer');
        $manager->persist($alice);

        $bob = new Contact();
        $bob->setFirstName('Bob');
        $bob->setLastName('Jones');
        $bob->setEmail('bob@globex.example.com');
        $bob->setJobTitle('Procurement Manager');
        $bob->setAccount($globex);
        $bob->setStatus('prospect');
        $manager->persist($bob);

        // Deals
        $deal1 = new Deal();
        $deal1->setTitle('Acme Enterprise License');
        $deal1->setAccount($acme);
        $deal1->setPrimaryContact($alice);
        $deal1->setOwner($sales);
        $deal1->setValue('85000.00');
        $deal1->setStage(Deal::STAGE_PROPOSAL);
        $deal1->setProbability(60);
        $deal1->setCloseDate(new \DateTime('+30 days'));
        $manager->persist($deal1);

        $deal2 = new Deal();
        $deal2->setTitle('Globex Pilot Project');
        $deal2->setAccount($globex);
        $deal2->setPrimaryContact($bob);
        $deal2->setOwner($sales);
        $deal2->setValue('12000.00');
        $deal2->setStage(Deal::STAGE_QUALIFICATION);
        $deal2->setProbability(30);
        $manager->persist($deal2);

        // Activities
        $activity = new Activity();
        $activity->setType(Activity::TYPE_MEETING);
        $activity->setSubject('Demo call with Alice');
        $activity->setStatus(Activity::STATUS_PLANNED);
        $activity->setContact($alice);
        $activity->setDeal($deal1);
        $activity->setAssignedTo($sales);
        $activity->setScheduledAt(new \DateTime('+2 days'));
        $manager->persist($activity);

        $manager->flush();
    }
}
