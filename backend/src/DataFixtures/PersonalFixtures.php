<?php

namespace App\DataFixtures;

use App\Entity\User;
use App\Entity\GameCharacter;
use App\Entity\GameGuild;
use App\Entity\GuildMembership;
use App\Enum\GuildRole;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Faker\Factory as Faker;

class PersonalFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function load(ObjectManager $manager): void
    {
        $faker = Faker::create('fr_FR');

        $gmUsername = (string)($_ENV['FIXTURES_USERNAME'] ?? '');
        $gmUser = $gmUsername
            ? $this->userRepository->findOneBy(['username' => $gmUsername])
            : null;

        if (!$gmUser) {
            $gmUser = $this->userRepository->findOneBy([], ['id' => 'ASC']);
            if (!$gmUser) {
                throw new \RuntimeException('Aucun utilisateur trouvé pour le GM ; assure un jeu de données UserFixtures.');
            }
        }

        $guild = $manager->getRepository(GameGuild::class)->findOneBy(['name' => 'Tracker']);
        if (!$guild) {
            $guild = (new GameGuild())
                ->setName('Tracker')
                ->setFaction('HORDE');
            $manager->persist($guild);
            $manager->flush();
        }

        $existingGmMembership = $manager->getRepository(GuildMembership::class)
            ->findOneBy(['user' => $gmUser, 'guild' => $guild]);

        if (!$existingGmMembership) {
            $gmMembership = new GuildMembership($gmUser, $guild, GuildRole::GM);
            $manager->persist($gmMembership);
        }

        $existingGmCharacter = $manager->getRepository(GameCharacter::class)
            ->findOneBy(['userPlayer' => $gmUser, 'guild' => $guild]);

        if (!$existingGmCharacter) {
            $gmCharacter = (new GameCharacter())
                ->setName('Bäxter')
                ->setClass('Warrior')
                ->setClassSpec('Protection')
                ->setRole('TANK')
                ->setUserPlayer($gmUser)
                ->setGuild($guild);
            $manager->persist($gmCharacter);
        }

        $needed = 39;
        $usersPool = $this->userRepository->createQueryBuilder('u')
            ->andWhere('u != :gm')
            ->setParameter('gm', $gmUser)
            ->setMaxResults($needed)
            ->getQuery()->getResult();

        if (count($usersPool) < $needed) {
            throw new \RuntimeException(sprintf(
                'Il faut au moins %d utilisateurs (hors GM). Trouvés: %d.',
                $needed,
                count($usersPool)
            ));
        }

        shuffle($usersPool);
        $officers = array_slice($usersPool, 0, 3);
        $members  = array_slice($usersPool, 3, 36);

        $classes = [
            'Warrior' => [
                ['spec' => 'Arms', 'role' => 'MELEE'],
                ['spec' => 'Fury', 'role' => 'MELEE'],
                ['spec' => 'Protection', 'role' => 'TANK'],
            ],
            'Mage' => [
                ['spec' => 'Frost', 'role' => 'RANGED'],
                ['spec' => 'Fire', 'role' => 'RANGED'],
                ['spec' => 'Arcane', 'role' => 'RANGED'],
            ],
            'Priest' => [
                ['spec' => 'Holy', 'role' => 'HEALER'],
                ['spec' => 'Discipline', 'role' => 'HEALER'],
                ['spec' => 'Shadow', 'role' => 'RANGED'],
            ],
            'Rogue' => [
                ['spec' => 'Assassination', 'role' => 'MELEE'],
                ['spec' => 'Outlaw', 'role' => 'MELEE'],
                ['spec' => 'Subtlety', 'role' => 'MELEE'],
            ],
            'Druid' => [
                ['spec' => 'Balance', 'role' => 'RANGED'],
                ['spec' => 'Feral', 'role' => 'MELEE'],
                ['spec' => 'Guardian', 'role' => 'TANK'],
                ['spec' => 'Restoration', 'role' => 'HEALER'],
            ],
            'Paladin' => [
                ['spec' => 'Holy', 'role' => 'HEALER'],
                ['spec' => 'Protection', 'role' => 'TANK'],
                ['spec' => 'Retribution', 'role' => 'MELEE'],
            ],
            'Shaman' => [
                ['spec' => 'Elemental', 'role' => 'RANGED'],
                ['spec' => 'Enhancement', 'role' => 'MELEE'],
                ['spec' => 'Restoration', 'role' => 'HEALER'],
            ],
            'Hunter' => [
                ['spec' => 'Beast Mastery', 'role' => 'RANGED'],
                ['spec' => 'Marksmanship', 'role' => 'RANGED'],
                ['spec' => 'Survival', 'role' => 'MELEE'],
            ],
            'Warlock' => [
                ['spec' => 'Affliction', 'role' => 'RANGED'],
                ['spec' => 'Demonology', 'role' => 'RANGED'],
                ['spec' => 'Destruction', 'role' => 'RANGED'],
            ],
            'Monk' => [
                ['spec' => 'Brewmaster', 'role' => 'TANK'],
                ['spec' => 'Mistweaver', 'role' => 'HEALER'],
                ['spec' => 'Windwalker', 'role' => 'MELEE'],
            ],
            'Death Knight' => [
                ['spec' => 'Blood', 'role' => 'TANK'],
                ['spec' => 'Frost', 'role' => 'MELEE'],
                ['spec' => 'Unholy', 'role' => 'MELEE'],
            ],
            'Demon Hunter' => [
                ['spec' => 'Havoc', 'role' => 'MELEE'],
                ['spec' => 'Vengeance', 'role' => 'TANK'],
            ],
            'Evoker' => [
                ['spec' => 'Devastation', 'role' => 'RANGED'],
                ['spec' => 'Preservation', 'role' => 'HEALER'],
                ['spec' => 'Augmentation', 'role' => 'RANGED'],
            ],
        ];

        $batchSize = 50;
        $i = 0;

        $createMembershipAndCharacter = function (User $user, GuildRole $role) use ($manager, $guild, $faker, $classes, &$i, $batchSize) {
            $membership = new GuildMembership($user, $guild, $role);
            $manager->persist($membership);

            [$className, $classData] = $this->randomClass($classes);
            $specTuple = $classData[array_rand($classData)];

            $characterName = $this->wowStyleName($faker);
            $character = (new GameCharacter())
                ->setName($characterName)
                ->setClass($className)
                ->setClassSpec($specTuple['spec'])
                ->setRole($specTuple['role'])
                ->setUserPlayer($user)
                ->setGuild($guild);

            $manager->persist($character);

            if ((++$i % $batchSize) === 0) {
                $manager->flush();
                $manager->clear();
            }
        };

        foreach ($officers as $officerUser) {
            $createMembershipAndCharacter($officerUser, GuildRole::OFFICER);
        }

        foreach ($members as $memberUser) {
            $createMembershipAndCharacter($memberUser, GuildRole::MEMBER);
        }

        $manager->flush();
    }

    private function randomClass(array $classes): array
    {
        $classNames = array_keys($classes);
        $className = $classNames[array_rand($classNames)];
        return [$className, $classes[$className]];
    }

    private function wowStyleName(\Faker\Generator $faker): string
    {
        $base = $faker->unique()->firstName();
        $variations = ['ä','â','ê','ë','ï','î','ö','ô','ü','û','ÿ'];
        if (mt_rand(0, 1)) {
            $pos = mt_rand(1, max(1, strlen($base) - 1));
            $base = mb_substr($base, 0, $pos) . $variations[array_rand($variations)] . mb_substr($base, $pos);
        }
        return $base;
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
        ];
    }
}
