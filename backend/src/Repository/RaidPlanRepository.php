<?php

namespace App\Repository;

use App\Entity\RaidPlan;
use App\Entity\GameGuild;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class RaidPlanRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RaidPlan::class);
    }

    
    public function findByGuild(GameGuild $guild): array
    {
        return $this->createQueryBuilder('rp')
            ->andWhere('rp.guild = :guild')
            ->setParameter('guild', $guild)
            ->orderBy('rp.updatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    
    public function findTemplates(): array
    {
        return $this->createQueryBuilder('rp')
            ->andWhere('rp.isTemplate = :true')
            ->setParameter('true', true)
            ->orderBy('rp.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    
    public function findByBossId(string $bossId, ?GameGuild $guild = null): array
    {
        $qb = $this->createQueryBuilder('rp')
            ->andWhere('rp.bossId = :bossId')
            ->setParameter('bossId', $bossId)
            ->orderBy('rp.updatedAt', 'DESC');

        if ($guild) {
            $qb->andWhere('rp.guild = :guild')
               ->setParameter('guild', $guild);
        }

        return $qb->getQuery()->getResult();
    }
}
