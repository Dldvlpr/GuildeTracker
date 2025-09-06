<?php

namespace App\Entity;

use App\Repository\GameGuildRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: GameGuildRepository::class)]
class GameGuild
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?Uuid $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $faction = null;

    /**
     * @var Collection<int, GameCharacter>
     */
    #[ORM\OneToMany(targetEntity: GameCharacter::class, mappedBy: 'gameGuild')]
    private Collection $gameCharacters;

    /**
     * @var Collection<int, GuildMembership>
     */
    #[ORM\OneToMany(targetEntity: GuildMembership::class, mappedBy: 'gameGuild')]
    private Collection $guildMemberships;

    public function __construct()
    {
        $this->gameCharacters = new ArrayCollection();
        $this->guildMemberships = new ArrayCollection();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getUuidToString(): string
    {
        return $this->id->toString();
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

    public function getFaction(): ?string
    {
        return $this->faction;
    }

    public function setFaction(string $faction): static
    {
        $this->faction = $faction;

        return $this;
    }

    /**
     * @return Collection<int, GameCharacter>
     */
    public function getGameCharacters(): Collection
    {
        return $this->gameCharacters;
    }

    public function addGameCharacter(GameCharacter $gameCharacter): static
    {
        if (!$this->gameCharacters->contains($gameCharacter)) {
            $this->gameCharacters->add($gameCharacter);
            $gameCharacter->setGuild($this);
        }

        return $this;
    }

    public function removeGameCharacter(GameCharacter $gameCharacter): static
    {
        if ($this->gameCharacters->removeElement($gameCharacter)) {
            // set the owning side to null (unless already changed)
            if ($gameCharacter->getGuild() === $this) {
                $gameCharacter->setGuild(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, GuildMembership>
     */
    public function getGuildMemberships(): Collection
    {
        return $this->guildMemberships;
    }

    public function addGuildMembership(GuildMembership $guildMembership): static
    {
        if (!$this->guildMemberships->contains($guildMembership)) {
            $this->guildMemberships->add($guildMembership);
            $guildMembership->setGameGuild($this);
        }

        return $this;
    }

    public function removeGuildMembership(GuildMembership $guildMembership): static
    {
        if ($this->guildMemberships->removeElement($guildMembership)) {
            // set the owning side to null (unless already changed)
            if ($guildMembership->getGameGuild() === $this) {
                $guildMembership->setGameGuild(null);
            }
        }

        return $this;
    }
}
