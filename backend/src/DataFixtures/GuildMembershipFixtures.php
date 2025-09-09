<?php

namespace App\DataFixtures;

use App\Entity\GuildMembership;
use App\Entity\GameGuild;
use App\Entity\User;
use App\Enum\GuildRole;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class GuildMembershipFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $guildsCount        = (int)($_ENV['FIXTURES_GUILDS'] ?? 20);
        $usersCount         = (int)($_ENV['FIXTURES_USERS'] ?? 800);
        $officersPerGuild   = max(0, (int)($_ENV['FIXTURES_OFFICERS_PER_GUILD'] ?? 3));
        $seed               = (int)($_ENV['FIXTURES_SEED'] ?? 0);

        if ($usersCount < $guildsCount * (1 + $officersPerGuild)) {
            throw new \RuntimeException('Not enough users for the requested GM+Officer plan.');
        }

        $userRefs = range(1, $usersCount);
        if ($seed) { mt_srand($seed + 2); }
        shuffle($userRefs);

        $basePerGuild = intdiv($usersCount, $guildsCount);
        $remainder    = $usersCount % $guildsCount;

        $popIndex = 0;

        for ($g = 1; $g <= $guildsCount; $g++) {
            /** @var GameGuild $guild */
            $guild = $this->getReference('guild_'.$g, GameGuild::class);

            $targetThisGuild = $basePerGuild + ($g <= $remainder ? 1 : 0);

            $gmUserId = $userRefs[$popIndex++];
            $gmUser   = $this->getReference('user_'.$gmUserId, User::class);
            $manager->persist(new GuildMembership($gmUser, $guild, GuildRole::GM));

            for ($k = 0; $k < $officersPerGuild; $k++) {
                $offUserId = $userRefs[$popIndex++];
                $offUser   = $this->getReference('user_'.$offUserId, User::class);
                $manager->persist(new GuildMembership($offUser, $guild, GuildRole::OFFICER));
            }

            $already = 1 + $officersPerGuild;
            $toFill  = max(0, $targetThisGuild - $already);

            for ($m = 0; $m < $toFill; $m++) {
                $memUserId = $userRefs[$popIndex++];
                $memUser   = $this->getReference('user_'.$memUserId, User::class);
                $manager->persist(new GuildMembership($memUser, $guild, GuildRole::MEMBER));
            }

            if ($g % 10 === 0) {
                $manager->flush();
            }
        }

        $g = 1;
        while ($popIndex < count($userRefs)) {
            /** @var GameGuild $guild */
            $guild = $this->getReference('guild_'.$g, GameGuild::class);
            $uid   = $userRefs[$popIndex++];
            $user  = $this->getReference('user_'.$uid, User::class);
            $manager->persist(new GuildMembership($user, $guild, GuildRole::MEMBER));

            $g++;
            if ($g > $guildsCount) { $g = 1; }
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
