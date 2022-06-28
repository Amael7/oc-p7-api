<?php

namespace App\DataFixtures;

use App\Entity\Client;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class ClientFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {

        $faker = \Faker\Factory::create('fr_FR'); // Set Faker

        // Creation of admin client  
        $admin = new Client();
        $admin->setEmail('admin@bilemo.com')
                ->setCreatedAt(new \DateTime())
                ->setCompany('BileMo')
                ->setPassword('admin')
                // ->setPassword($this->encoder->encodePassword($admin, 'admin'))
                ->setRoles(['ROLE_ADMIN']);

        $manager->persist($admin);
        
        // Creation of user client
        $user = new Client();
        $user->setEmail('user@bilemo.com')
                ->setCreatedAt(new \DateTime())
                ->setCompany('BileMo')
                ->setPassword('user')
                // ->setPassword($this->encoder->encodePassword($user, 'user'))
                ->setRoles(['ROLE_USER']);

        $manager->persist($user);
        $this->addReference('user', $user);

        // Creation of some clients
        for ($i = 0; $i < 30; ++$i) {
          $client = new Client();
          $client->setEmail($faker->companyEmail())
              ->setCreatedAt($faker->dateTime())
              ->setCompany($faker->company())
              ->setPassword('password')
              // ->setPassword($this->encoder->encodePassword($client, 'password'))
              ->setRoles(['ROLE_USER']);
          $manager->persist($client);
          $this->addReference('client'.$i, $client);
        }

      $manager->flush();
    }
}
