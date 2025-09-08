<?php

namespace App\DataFixtures;

use App\Entity\GameGuild;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker;

class GameGuildFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create('fr_FR');

        for ($i = 1; $i <= 2; $i++) {
            $guild = new GameGuild();
            $guild->setName($faker->unique()->company());
            $guild->setFaction($faker->randomElement(['HORDE', 'ALLIANCE']));

            $manager->persist($guild);

            $this->addReference('gm_guild_'.$i, $guild);
        }

        $manager->flush();
    }
}
