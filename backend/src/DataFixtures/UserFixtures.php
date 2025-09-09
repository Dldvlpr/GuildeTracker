<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory as Faker;

class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $usersCount = (int)($_ENV['FIXTURES_USERS'] ?? 800);
        $seed       = (int)($_ENV['FIXTURES_SEED'] ?? 0);

        $faker = Faker::create('fr_FR');
        if ($seed) { $faker->seed($seed); mt_srand($seed); }

        for ($i = 1; $i <= $usersCount; $i++) {
            $user = new User();
            $user->setEmail($faker->unique()->safeEmail());
            $user->setUsername($faker->unique()->userName());
            $user->setDiscordId($faker->uuid());
            $user->setRoles(['ROLE_USER']);

            $manager->persist($user);
            $this->addReference('user_'.$i, $user);

            if ($i % 500 === 0) {
                $manager->flush();
            }
        }

        $manager->flush();
    }
}
