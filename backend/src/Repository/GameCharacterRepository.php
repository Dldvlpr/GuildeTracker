<?php

namespace App\Repository;

use App\Entity\GameCharacter;
use App\Entity\GameGuild;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class GameCharacterRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GameCharacter::class);
    }






















}
