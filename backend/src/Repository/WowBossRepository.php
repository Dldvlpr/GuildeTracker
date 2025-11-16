<?php

namespace App\Repository;

use App\Entity\WowBoss;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class WowBossRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WowBoss::class);
    }

    public function findByRaid(int $raidId): array
    {
        return $this->createQueryBuilder('b')
            ->where('b.raid = :raidId')
            ->setParameter('raidId', $raidId)
            ->orderBy('b.orderIndex', 'ASC')
            ->addOrderBy('b.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
