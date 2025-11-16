<?php

namespace App\Repository;

use App\Entity\GameGuild;
use App\Entity\User;
use App\Enum\GuildRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class GameGuildRepository extends ServiceEntityRepository
{

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameGuild::class);
    }






















    
    public function findByUserId(string $userId): array
    {
        return $this->createQueryBuilder('g')
            ->innerJoin('g.guildMemberships', 'gm')
            ->andWhere('IDENTITY(gm.user) = :uid')
            ->setParameter('uid', $userId)
            ->getQuery()
            ->getResult();
    }

    public function findRoleForUserInGuild(User $user, GameGuild $guild): ?GuildRole
    {
        $result = $this->createQueryBuilder('m')
            ->select('m.role')
            ->andWhere('m.user = :u')
            ->andWhere('m.guild = :g')
            ->setParameter('u', $user)
            ->setParameter('g', $guild)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($result === null) {
            return null;
        }
        return $result instanceof GuildRole ? $result : GuildRole::from((string) $result);
    }

    public function findOneByRealmAndNameInsensitive(?string $realm, string $name): ?GameGuild
    {
        $qb = $this->createQueryBuilder('g')
            ->andWhere('LOWER(g.name) = LOWER(:name)')
            ->setParameter('name', $name);
        if ($realm !== null) {
            $qb->andWhere('LOWER(g.realm) = LOWER(:realm)')->setParameter('realm', $realm);
        }
        return $qb->setMaxResults(1)->getQuery()->getOneOrNullResult();
    }

    public function findOneByRealmAndBlizzardId(?string $realm, string $blizzardId): ?GameGuild
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.blizzardId = :bid')
            ->andWhere('g.realm = :realm')
            ->setParameter('bid', $blizzardId)
            ->setParameter('realm', $realm)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
