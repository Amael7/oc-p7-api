<?php

namespace App\DataFixtures;

use App\Entity\Customer;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class CustomerFixtures extends Fixture
{
  public function load(ObjectManager $manager): void
  {
    $faker = \Faker\Factory::create('fr_FR');

    // Create Customer related to some Clients
    for ($i = 0; $i < 70; ++$i) {
        $customer = new Customer();
        $customer->setEmail($faker->email())
            ->setCreatedAt($faker->dateTime())
            ->setFirstname($faker->firstName())
            ->setLastname($faker->lastName());
        for ($j = 1; $j < mt_rand(1, 3); ++$j) {
            $customer->addClient($this->getReference('client'.mt_rand(0, 19)));
        }

        $manager->persist($customer);
    }

    for ($i = 0; $i < 5; ++$i) {
        $customer = new Customer();
        $customer->setEmail($faker->email())
            ->setCreatedAt($faker->dateTime())
            ->setFirstname($faker->firstName())
            ->setLastname($faker->lastName());
        for ($j = 1; $j < mt_rand(1, 3); ++$j) {
            $customer->addClient($this->getReference('user'));
        }

        $manager->persist($customer);
    }
    $manager->flush();
  }

  public function getDependencies()
  {
      return [
          ClientFixtures::class,
      ];
  }
}
