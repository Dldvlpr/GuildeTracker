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
        $users = $this->userRepository->createQueryBuilder('u')->getQuery()->getResult();

        if (count($users) < 8) {
            throw new \RuntimeException('Need at least 8 users to assign 2 GMs and 6 Officers.');
        }

        shuffle($users);

        /** @var GameGuild $guild1 */
        $guild1 = $this->getReference('gm_guild_1', GameGuild::class);
        /** @var GameGuild $guild2 */
        $guild2 = $this->getReference('gm_guild_2', GameGuild::class);

        $gms = array_splice($users, 0, 2);
        $manager->persist(new GuildMembership($gms[0], $guild1, GuildRole::GM));
        $manager->persist(new GuildMembership($gms[1], $guild2, GuildRole::GM));

        $officers = array_splice($users, 0, 6);
        $officerChunks = array_chunk($officers, 3);
        foreach ($officerChunks[0] as $user) {
            $manager->persist(new GuildMembership($user, $guild1, GuildRole::OFFICER));
        }
        foreach ($officerChunks[1] as $user) {
            $manager->persist(new GuildMembership($user, $guild2, GuildRole::OFFICER));
        }

        $members = $users;
        if (!empty($members)) {
            $memberChunks = array_chunk($members, (int) ceil(count($members) / 2));
            $g1Members = $memberChunks[0] ?? [];
            $g2Members = $memberChunks[1] ?? [];

            foreach ($g1Members as $user) {
                $manager->persist(new GuildMembership($user, $guild1, GuildRole::MEMBER));
            }
            foreach ($g2Members as $user) {
                $manager->persist(new GuildMembership($user, $guild2, GuildRole::MEMBER));
            }
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
