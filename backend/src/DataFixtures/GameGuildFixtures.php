<?php

namespace App\DataFixtures;

use App\Entity\GameGuild;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory as Faker;

class GameGuildFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $guildsCount = (int)($_ENV['FIXTURES_GUILDS']);
        $seed        = (int)($_ENV['FIXTURES_SEED']);

        $faker = Faker::create('fr_FR');
        if ($seed) { $faker->seed($seed + 1); mt_srand($seed + 1); }

        for ($i = 1; $i <= $guildsCount; $i++) {
            $guild = new GameGuild();
            $guild->setName($faker->unique()->company())
                ->setFaction($faker->randomElement(['HORDE','ALLIANCE']));

            $manager->persist($guild);
            $this->addReference('guild_'.$i, $guild);
        }

        $manager->flush();
    }
}
