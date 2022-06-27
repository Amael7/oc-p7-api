<?php

namespace App\DataFixtures;

use App\Entity\Image;
use App\Entity\Product;
use App\Entity\Configuration;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class ProductFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {

        $faker = \Faker\Factory::create('fr_FR'); // Set Faker
        $date = $faker->dateTime(); // Set Date

        // Product Settings Start
            $manufacturers = [
                'Alcatel',
                'Apple',
                'Asus',
                'BlackBerry',
                'HTC',
                'Huawei',
                'Honor',
                'LG',
                'Motorola',
                'Nokia',
                'Samsung',
                'Sony',
                'Google',
                'Wiko',
                'Xiaomi',
            ];
        // Product Settings End

        // Configuration Settings Start
            $memory = [
                '32',
                '64',
                '128',
                '256',
            ];
        // Configuration Settings End

        // Creation of some products
        for ($i = 0; $i < 70; $i++) {
            $product = new Product;
            $product->setName($faker->word())
                    ->setDescription($faker->paragraph())
                    ->setCreatedAt($date)
                    ->setScreenSize($faker->randomFloat(1, 4, 7))
                    ->setCamera(random_int(0, 1))
                    ->setBluetooth(random_int(0, 1))
                    ->setWifi(random_int(0, 1))
                    ->setLength($faker->randomFloat(2, 12, 15))
                    ->setWidth($faker->randomFloat(2, 6, 10))
                    ->setHeight($faker->randomFloat(2, 0.7, 1.5))
                    ->setWeight($faker->randomFloat(1, 150, 250))
                    ->setDas($faker->randomFloat(3, 0.1, 1))
                    ->setManufacturer($manufacturers[mt_rand(0, count($manufacturers) - 1)]);
            
            // Creation of some configurations for the product
            for ($k = 0; $k < mt_rand(1, 3); ++$k) {
                $config = new Configuration();
                $config->setCapacity($memory[mt_rand(0, 3)])
                    ->setColor($faker->safeColorName())
                    ->setPrice($faker->randomFloat(2, 800, 1500));

                // Creation of some images for the configuration
                for ($j = 0; $j < mt_rand(0, 4); ++$j) {
                    $image = new Image();
                    $image->setUrl($faker->imageUrl(640, 480, 'phone'));
                    $config->addImage($image);
                }

                $product->addConfiguration($config);
            }
            
            $manager->persist($product);
            $manager->flush();
        }

    }
}
