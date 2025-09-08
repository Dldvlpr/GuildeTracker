<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker;
use Symfony\Component\Uid\Uuid;

class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create('fr_FR');

        for ($i = 0; $i < 80; $i++) {
            $user = new User();
            $user->setDiscordId($faker->uuid());

            $user->setUsername($faker->userName);
            $user->setEmail($faker->email);
            $user->setRoles(['ROLE_USER']);

            if ($faker->boolean(70)) {
                $user->setBlizzardId($faker->userName . '#' . $faker->numberBetween(1000, 9999));
            }

            $manager->persist($user);
            $this->addReference('user_'.$i, $user);
        }

        $manager->flush();
    }
}
