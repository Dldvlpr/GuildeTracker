<?php

namespace App\DataFixtures;

use App\Entity\GameCharacter;
use App\Entity\GameGuild;
use App\Entity\GuildMembership;
use App\Enum\GuildRole;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory as Faker;

class PersonalFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Faker::create('fr_FR');
        $userRepository = $manager->getRepository(UserRepository::class);
        $user = $userRepository->findOneBy(['username' => (string)($_ENV['FIXTURES_USERNAME'])]);
        $gameGuild = new GameGuild();
        $gameGuild->setName('Tracker')
            ->setFaction('HORDE');

        $manager->persist($gameGuild);
        $manager->flush();

        $guildMembership = new GuildMembership($user, $gameGuild, GuildRole::GM);
        $manager->persist($guildMembership);
        $manager->flush();

        $gameCharacter = new GameCharacter();
        $gameCharacter->setName('Bäxter')
            ->setClass('Warrior')
            ->setClassSpec('Protection')
            ->setRole('Tank')
            ->setUserPlayer($user)
            ->setGuild($gameGuild);

        $manager->persist($gameCharacter);
        $manager->flush();

    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            GameGuildFixtures::class,
            GameCharacterFixtures::class,
            GuildMembershipFixtures::class,
        ];
    }
}

