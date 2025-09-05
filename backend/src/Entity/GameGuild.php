<?php

namespace App\Entity;

use App\Repository\GuildRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameGuildRepository::class)]
class GameGuild
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $faction = null;

    /**
     * @var Collection<int, GameCharacter>
     */
    #[ORM\OneToMany(targetEntity: GameCharacter::class, mappedBy: 'guild', orphanRemoval: true)]
    private Collection $gameCharacters;

    public function __construct()
    {
        $this->gameCharacters = new ArrayCollection();
    }

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
}
