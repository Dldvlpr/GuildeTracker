<?php

namespace App\Entity;

use App\Repository\GameCharacterRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameCharacterRepository::class)]
class GameCharacter
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $class = null;

    #[ORM\Column(length: 255)]
    private ?string $classSpec = null;

    #[ORM\Column(length: 255)]
    private ?string $role = null;

    #[ORM\ManyToOne(inversedBy: 'gameCharacters')]
    #[ORM\JoinColumn(nullable: true)]
    private ?GameGuild $guild = null;

    #[ORM\ManyToOne(inversedBy: 'gameCharacters')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $userPlayer = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function setClass(string $class): static
    {
        $this->class = $class;

        return $this;
    }

    public function getClassSpec(): ?string
    {
        return $this->classSpec;
    }

    public function setClassSpec(string $classSpec): static
    {
        $this->classSpec = $classSpec;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getGuild(): ?Guild
    {
        return $this->guild;
    }

    public function setGuild(?Guild $guild): static
    {
        $this->guild = $guild;

        return $this;
    }

    public function getUserPlayer(): ?User
    {
        return $this->userPlayer;
    }

    public function setUserPlayer(?User $userPlayer): static
    {
        $this->userPlayer = $userPlayer;

        return $this;
    }
}
