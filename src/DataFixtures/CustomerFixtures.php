<?php

namespace CaRMen\DataFixtures;

use CaRMen\Entity\Customer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class CustomerFixtures extends Fixture
{
    private const COUNT = 250;

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create();

        for ($i = 0; $i < self::COUNT; $i++) {
            $customer = new Customer();
            $customer->setFirstName($faker->firstName());
            $customer->setLastName($faker->lastName());
            $customer->setPhone($faker->numerify('+45 ## ## ## ##'));
            $customer->setMail($faker->safeEmail());

            $manager->persist($customer);
        }

        $manager->flush();
    }
}
