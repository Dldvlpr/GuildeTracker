<?php

namespace App\Repository;

use App\Entity\BlizzardGameRealm;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class BlizzardGameRealmRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BlizzardGameRealm::class);
    }





















}
