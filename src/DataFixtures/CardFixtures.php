<?php

namespace App\DataFixtures;

use App\Entity\Card;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CardFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $colors = ['red', 'blue', 'green', 'yellow'];
        $values = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', 'X', '+2', 'S'];

        foreach ($colors as $color) {
            foreach ($values as $value) {
                for ($i = 0; $i < 2; $i++) {
                    $card = new Card();
                    $card->setColor($color);
                    $card->setValue($value);
                    $manager->persist($card);
                }
            }
        }

        $manager->flush();
    }
}
