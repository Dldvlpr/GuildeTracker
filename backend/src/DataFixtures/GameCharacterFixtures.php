<?php

namespace App\DataFixtures;

use App\Entity\GameCharacter;
use App\Entity\GameGuild;
use App\Entity\GuildMembership;
use App\Entity\User;
use App\Repository\GameGuildRepository;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker;

class GameCharacterFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly UserRepository      $userRepository,
        private readonly GameGuildRepository $gameGuildRepository,
    )
    {
    }

    private const BATCH_SIZE = 100;
    private const GAME_CLASSES = [
        [
            'name' => 'Warrior',
            'specs' => [
                ['name' => 'Arms', 'role' => 'MELEE'],
                ['name' => 'Fury', 'role' => 'MELEE'],
                ['name' => 'Protection', 'role' => 'TANKS'],
            ],
        ],
        [
            'name' => 'Druid',
            'specs' => [
                ['name' => 'Balance', 'role' => 'RANGED'],
                ['name' => 'Dreamstate', 'role' => 'HEALERS'],
                ['name' => 'Feral', 'role' => 'MELEE'],
                ['name' => 'Restoration', 'role' => 'HEALERS'],
                ['name' => 'Guardian', 'role' => 'TANKS'],
            ],
        ],
        [
            'name' => 'Paladin',
            'specs' => [
                ['name' => 'Holy', 'role' => 'HEALERS'],
                ['name' => 'Protection', 'role' => 'TANKS'],
                ['name' => 'Retribution', 'role' => 'MELEE'],
            ],
        ],
        [
            'name' => 'Rogue',
            'specs' => [
                ['name' => 'Assassination', 'role' => 'MELEE'],
                ['name' => 'Combat', 'role' => 'MELEE'],
                ['name' => 'Subtlety', 'role' => 'MELEE'],
            ],
        ],
        [
            'name' => 'Hunter',
            'specs' => [
                ['name' => 'Beastmastery', 'role' => 'RANGED'],
                ['name' => 'Marksmanship', 'role' => 'RANGED'],
                ['name' => 'Survival', 'role' => 'RANGED'],
            ],
        ],
        [
            'name' => 'Priest',
            'specs' => [
                ['name' => 'Discipline', 'role' => 'HEALERS'],
                ['name' => 'Holy', 'role' => 'HEALERS'],
                ['name' => 'Shadow', 'role' => 'RANGED'],
                ['name' => 'Smite', 'role' => 'RANGED'],
            ],
        ],
        [
            'name' => 'Mage',
            'specs' => [
                ['name' => 'Arcane', 'role' => 'RANGED'],
                ['name' => 'Fire', 'role' => 'RANGED'],
                ['name' => 'Frost', 'role' => 'RANGED'],
            ],
        ],
        [
            'name' => 'Warlock',
            'specs' => [
                ['name' => 'Affliction', 'role' => 'RANGED'],
                ['name' => 'Demonology', 'role' => 'RANGED'],
                ['name' => 'Destruction', 'role' => 'RANGED'],
            ],
        ],
        [
            'name' => 'Shaman',
            'specs' => [
                ['name' => 'Elemental', 'role' => 'RANGED'],
                ['name' => 'Enhancement', 'role' => 'MELEE'],
                ['name' => 'Restoration', 'role' => 'HEALERS'],
            ],
        ],
    ];

    public function load(ObjectManager $manager): void
    {
        $faker = \Faker\Factory::create('fr_FR');
        $gameClasses = self::GAME_CLASSES;
        $guildsCount = (int)($_ENV['FIXTURES_GUILDS']);
        $characters = (int)($_ENV['FIXTURES_CHARACTERS']);

        for ($i = 1; $i <= $guildsCount; $i++) {
            $guild = $this->getReference('guild_' . $i, GameGuild::class);

            $guildMemberships = $guild->getGuildMemberships();

            foreach ($guildMemberships as $guildMembership) {
                if (!($guildMembership instanceof GuildMembership)) {
                    continue;
                }

                $user = $guildMembership->getUser();
                if (!($user instanceof User)) {
                    continue;
                }

                $nbrOfCharacters = mt_rand(1, $characters);

                for ($j = 0; $j < $nbrOfCharacters; $j++) {
                    $class = $gameClasses[array_rand($gameClasses)];
                    $spec = $class['specs'][array_rand($class['specs'])];

                    $gameCharacter = new GameCharacter();
                    $gameCharacter
                        ->setGuild($guild)
                        ->setName($faker->userName)
                        ->setClass($class['name'])
                        ->setRole($spec['role'])
                        ->setClassSpec($spec['name'])
                        ->setUserPlayer($user);

                    $manager->persist($gameCharacter);
                }
            }

            if ($i % self::BATCH_SIZE === 0) {
                $manager->flush();
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            GameGuildFixtures::class,
            GuildMembershipFixtures::class,
        ];
    }
}
