<?php

namespace App\Repository;

use App\Entity\WowRaid;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class WowRaidRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WowRaid::class);
    }

    public function findByExpansion(string $expansion): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.expansion = :expansion')
            ->setParameter('expansion', $expansion)
            ->orderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAllWithBosses(): array
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.bosses', 'b')
            ->addSelect('b')
            ->orderBy('r.expansion', 'ASC')
            ->addOrderBy('r.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
