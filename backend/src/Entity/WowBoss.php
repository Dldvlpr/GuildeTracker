<?php

namespace App\Entity;

use App\Repository\WowBossRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WowBossRepository::class)]
#[ORM\Table(name: 'wow_bosses')]
#[ORM\UniqueConstraint(name: 'boss_slug_unique', columns: ['slug'])]
#[ORM\HasLifecycleCallbacks]
class WowBoss
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255, unique: true)]
    private string $slug;

    #[ORM\Column(type: 'integer')]
    private int $npcId;

    #[ORM\Column(length: 500)]
    private string $wowheadUrl;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $orderIndex = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: WowRaid::class, inversedBy: 'bosses')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?WowRaid $raid = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $updatedAt;

    public function __construct()
    {
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

    public function getNpcId(): int
    {
        return $this->npcId;
    }

    public function setNpcId(int $npcId): self
    {
        $this->npcId = $npcId;
        return $this;
    }

    public function getWowheadUrl(): string
    {
        return $this->wowheadUrl;
    }

    public function setWowheadUrl(string $wowheadUrl): self
    {
        $this->wowheadUrl = $wowheadUrl;
        return $this;
    }

    public function getOrderIndex(): ?int
    {
        return $this->orderIndex;
    }

    public function setOrderIndex(?int $orderIndex): self
    {
        $this->orderIndex = $orderIndex;
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

    public function getRaid(): ?WowRaid
    {
        return $this->raid;
    }

    public function setRaid(?WowRaid $raid): self
    {
        $this->raid = $raid;
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
