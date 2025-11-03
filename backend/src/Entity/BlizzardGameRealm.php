<?php

namespace App\Entity;

use App\Repository\BlizzardGameRealmRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BlizzardGameRealmRepository::class)]
class BlizzardGameRealm
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
