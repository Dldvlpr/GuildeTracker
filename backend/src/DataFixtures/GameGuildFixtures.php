<?php

namespace App\DataFixtures;

use App\Entity\GameGuild;
use App\Repository\GameGuildRepository;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker;

class GameGuildFixtures extends Fixture
{
    public function __construct(
        private UserRepository $userRepository,
    )
    {}

    public function load(ObjectManager $manager): void
    {
        $faker = Faker\Factory::create('fr_FR');
        $users = $this->userRepository->findAll();
        $user = $users[0];

        for ($i = 0; $i < 10; $i++) {
            $gameGuild = new GameGuild();
            $gameGuild->setName($faker->word);
            $gameGuild->setFaction($faker->randomElement(['ALLIANCE', 'HORDE']));
            $gameGuild->addUser($users[0]);
            $manager->persist($gameGuild);
            $manager->persist($user);
            $this->addReference('game_guild_'.$i, $gameGuild);
        }

        $manager->flush();
    }
}
