<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker;

class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create('fr_FR');

        for ($i = 0; $i < 10; $i++) {
            $user = new User();
            $user->setDiscordId($faker->unique()->numerify('##############'));
            $user->setUsername($faker->userName);
            $user->setEmail($faker->unique()->safeEmail);
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
