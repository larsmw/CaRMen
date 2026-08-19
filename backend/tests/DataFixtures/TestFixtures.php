<?php

namespace App\Tests\DataFixtures;

use App\Entity\Account;
use App\Entity\Activity;
use App\Entity\Contact;
use App\Entity\Deal;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class TestFixtures extends Fixture
{
    public const ADMIN_EMAIL    = 'admin@test.local';
    public const ADMIN_PASSWORD = 'Admin1234!';
    public const SALES_EMAIL    = 'sales@test.local';
    public const SALES_PASSWORD = 'Sales1234!';
    public const USER_EMAIL     = 'user@test.local';
    public const USER_PASSWORD  = 'User1234!';

    public const ACCOUNT_COUNT  = 10;
    public const CONTACT_COUNT  = 25;
    public const DEAL_COUNT     = 15;
    public const ACTIVITY_COUNT = 20;

    private Generator $faker;

    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
        $this->faker = Factory::create();
    }

    public function load(ObjectManager $manager): void
    {
        $admin = $this->createUser($manager, self::ADMIN_EMAIL,  self::ADMIN_PASSWORD, ['ROLE_ADMIN']);
        $sales = $this->createUser($manager, self::SALES_EMAIL,  self::SALES_PASSWORD, ['ROLE_SALES']);
        $this->createUser($manager, self::USER_EMAIL, self::USER_PASSWORD, ['ROLE_USER']);
        $manager->flush();

        $accounts = $this->createAccounts($manager);
        $manager->flush();

        $contacts = $this->createContacts($manager, $accounts);
        $manager->flush();

        $this->createDeals($manager, $accounts, $contacts, $admin, $sales);
        $this->createActivities($manager, $contacts, $sales);
        $manager->flush();
    }

    private function createUser(ObjectManager $m, string $email, string $password, array $roles): User
    {
        $user = new User();
        $user->setEmail($email)
            ->setFirstName($this->faker->firstName())
            ->setLastName($this->faker->lastName())
            ->setRoles($roles)
            ->setPassword($this->hasher->hashPassword($user, $password))
            ->setIsActive(true);
        $m->persist($user);
        return $user;
    }

    /** @return Account[] */
    private function createAccounts(ObjectManager $m): array
    {
        $industries = ['Technology', 'Finance', 'Healthcare', 'Retail', 'Manufacturing'];
        $accounts = [];
        for ($i = 0; $i < self::ACCOUNT_COUNT; $i++) {
            $a = new Account();
            $a->setName($this->faker->company())
                ->setIndustry($this->faker->randomElement($industries))
                ->setWebsite('https://'.strtolower($this->faker->domainName()))
                ->setPhone($this->faker->phoneNumber())
                ->setCity($this->faker->city())
                ->setCountry($this->faker->country())
                ->setEmployeeCount($this->faker->numberBetween(5, 5000))
                ->setAnnualRevenue((string) $this->faker->numberBetween(100000, 50000000));
            $m->persist($a);
            $accounts[] = $a;
        }
        return $accounts;
    }

    /** @return Contact[] */
    private function createContacts(ObjectManager $m, array $accounts): array
    {
        $statuses = ['lead', 'prospect', 'customer', 'inactive'];
        $contacts = [];
        for ($i = 0; $i < self::CONTACT_COUNT; $i++) {
            $c = new Contact();
            $c->setFirstName($this->faker->firstName())
                ->setLastName($this->faker->lastName())
                ->setEmail($this->faker->unique()->safeEmail())
                ->setPhone($this->faker->phoneNumber())
                ->setJobTitle($this->faker->jobTitle())
                ->setStatus($this->faker->randomElement($statuses))
                ->setAddressLine1($this->faker->streetAddress())
                ->setCity($this->faker->city())
                ->setPostalCode($this->faker->postcode())
                ->setCountry($this->faker->country());
            if ($i % 3 !== 0) {
                $c->setAccount($this->faker->randomElement($accounts));
            }
            $m->persist($c);
            $contacts[] = $c;
        }
        return $contacts;
    }

    private function createDeals(ObjectManager $m, array $accounts, array $contacts, User $o1, User $o2): void
    {
        $stages = ['prospecting', 'qualification', 'proposal', 'negotiation', 'closed_won', 'closed_lost'];
        for ($i = 0; $i < self::DEAL_COUNT; $i++) {
            $d = new Deal();
            $d->setTitle($this->faker->bs())
                ->setValue((string) $this->faker->randomFloat(2, 1000, 500000))
                ->setCurrency('EUR')
                ->setStage($this->faker->randomElement($stages))
                ->setProbability($this->faker->numberBetween(0, 100))
                ->setCloseDate(new \DateTime($this->faker->dateTimeBetween('now', '+1 year')->format('Y-m-d')))
                ->setOwner($i % 2 === 0 ? $o1 : $o2)
                ->setPrimaryContact($this->faker->randomElement($contacts));
            if ($i % 2 === 0) {
                $d->setAccount($this->faker->randomElement($accounts));
            }
            $m->persist($d);
        }
    }

    private function createActivities(ObjectManager $m, array $contacts, User $assignee): void
    {
        $types    = ['call', 'email', 'meeting', 'task', 'note'];
        $statuses = ['planned', 'completed', 'cancelled'];
        for ($i = 0; $i < self::ACTIVITY_COUNT; $i++) {
            $a = new Activity();
            $a->setType($this->faker->randomElement($types))
                ->setSubject($this->faker->sentence(4))
                ->setStatus($this->faker->randomElement($statuses))
                ->setScheduledAt(new \DateTime($this->faker->dateTimeBetween('-1 month', '+1 month')->format('Y-m-d H:i:s')))
                ->setContact($this->faker->randomElement($contacts))
                ->setAssignedTo($assignee);
            $m->persist($a);
        }
    }
}
