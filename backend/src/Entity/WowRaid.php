<?php

namespace App\Entity;

use App\Repository\WowRaidRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WowRaidRepository::class)]
#[ORM\Table(name: 'wow_raids')]
#[ORM\UniqueConstraint(name: 'raid_slug_unique', columns: ['slug'])]
class WowRaid
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255, unique: true)]
    private string $slug;

    #[ORM\Column(length: 50)]
    private string $expansion;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $minLevel = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $maxPlayers = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\OneToMany(targetEntity: WowBoss::class, mappedBy: 'raid', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $bosses;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
        $this->bosses = new ArrayCollection();
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;
        return $this;
    }

    public function getExpansion(): string
    {
        return $this->expansion;
    }

    public function setExpansion(string $expansion): self
    {
        $this->expansion = $expansion;
        return $this;
    }

    public function getMinLevel(): ?int
    {
        return $this->minLevel;
    }

    public function setMinLevel(?int $minLevel): self
    {
        $this->minLevel = $minLevel;
        return $this;
    }

    public function getMaxPlayers(): ?int
    {
        return $this->maxPlayers;
    }

    public function setMaxPlayers(?int $maxPlayers): self
    {
        $this->maxPlayers = $maxPlayers;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    
    public function getBosses(): Collection
    {
        return $this->bosses;
    }

    public function addBoss(WowBoss $boss): self
    {
        if (!$this->bosses->contains($boss)) {
            $this->bosses->add($boss);
            $boss->setRaid($this);
        }
        return $this;
    }

    public function removeBoss(WowBoss $boss): self
    {
        if ($this->bosses->removeElement($boss)) {
            if ($boss->getRaid() === $this) {
                $boss->setRaid(null);
            }
        }
        return $this;
    }

    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTime();
    }
}
