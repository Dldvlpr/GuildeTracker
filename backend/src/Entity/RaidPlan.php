<?php

namespace App\Entity;

use App\Repository\RaidPlanRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RaidPlanRepository::class)]
#[ORM\Table(name: 'raid_plans')]
#[ORM\HasLifecycleCallbacks]
class RaidPlan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\ManyToOne(targetEntity: GameGuild::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private GameGuild $guild;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $createdBy;

    #[ORM\Column(type: Types::JSON)]
    private array $blocks = [];

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private \DateTimeInterface $updatedAt;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isTemplate = false;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $bossId = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $raidName = null;

    #[ORM\Column(type: Types::STRING, length: 64, unique: true, nullable: true)]
    private ?string $shareToken = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $isPublic = false;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
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

    public function getGuild(): GameGuild
    {
        return $this->guild;
    }

    public function setGuild(GameGuild $guild): self
    {
        $this->guild = $guild;
        return $this;
    }

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User $createdBy): self
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getBlocks(): array
    {
        return $this->blocks;
    }

    public function setBlocks(array $blocks): self
    {
        $this->blocks = $blocks;
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): self
    {
        $this->metadata = $metadata;
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

    public function isTemplate(): bool
    {
        return $this->isTemplate;
    }

    public function setIsTemplate(bool $isTemplate): self
    {
        $this->isTemplate = $isTemplate;
        return $this;
    }

    public function getBossId(): ?string
    {
        return $this->bossId;
    }

    public function setBossId(?string $bossId): self
    {
        $this->bossId = $bossId;
        return $this;
    }

    public function getRaidName(): ?string
    {
        return $this->raidName;
    }

    public function setRaidName(?string $raidName): self
    {
        $this->raidName = $raidName;
        return $this;
    }

    public function getShareToken(): ?string
    {
        return $this->shareToken;
    }

    public function setShareToken(?string $shareToken): self
    {
        $this->shareToken = $shareToken;
        return $this;
    }

    public function isPublic(): bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): self
    {
        $this->isPublic = $isPublic;
        return $this;
    }

    
    public function generateShareToken(): self
    {
        $this->shareToken = bin2hex(random_bytes(32));
        return $this;
    }
}
