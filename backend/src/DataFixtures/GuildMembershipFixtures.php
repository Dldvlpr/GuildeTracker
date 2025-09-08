<?php

namespace App\DataFixtures;

use App\Entity\GuildMembership;
use App\Entity\GameGuild;
use App\Enum\GuildRole;
use App\Repository\UserRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class GuildMembershipFixtures extends Fixture implements DependentFixtureInterface
{
    public function __construct(
        private readonly UserRepository $userRepository,
    ) {}

    public function load(ObjectManager $manager): void
    {
        $users = $this->userRepository->createQueryBuilder('u')
            ->getQuery()
            ->getResult();

        if (count($users) < 2) {
            throw new \RuntimeException('Il faut au moins 2 utilisateurs en base pour assigner 2 GM.');
        }

        shuffle($users);
        $picked = array_slice($users, 0, 2);

        /** @var GameGuild $guild1 */
        $guild1 = $this->getReference('gm_guild_1', GameGuild::class);

        /** @var GameGuild $guild2 */
        $guild2 = $this->getReference('gm_guild_2', GameGuild::class);

        foreach ([[$picked[0], $guild1], [$picked[1], $guild2]] as [$user, $guild]) {
            $membership = new GuildMembership($user, $guild, GuildRole::GM);
            $manager->persist($membership);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            GameGuildFixtures::class,
        ];
    }
}
