<?php

namespace App\DataFixtures;

use DateTimeImmutable;
use App\Entity\Product;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;

class ProductFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Creation of 5 product
        for ($i = 0; $i < 5; $i++) {
            $product = new Product;
            $product->setName('Product ' . $i);
            $product->setDescription('Description ' . $i);
            $product->setCreatedAt(new DateTimeImmutable());
            $product->setScreenSize(6.2);
            $product->setCamera(true);
            $product->setBluetooth(true);
            $product->setWifi(true);
            $product->setLength(5);
            $product->setWidth(5);
            $product->setHeight(5);
            $product->setWeight(2);
            $product->setDas(1);
            $manager->persist($product);
        }

        $manager->flush();
    }
}
