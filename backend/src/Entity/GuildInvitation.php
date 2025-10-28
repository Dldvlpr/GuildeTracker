<?php
declare(strict_types=1);

namespace App\Entity;

use App\Enum\GuildRole;
use App\Repository\GuildInvitationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GuildInvitationRepository::class)]
#[ORM\Table(name: 'guild_invitation')]
#[ORM\Index(name: 'token_idx', columns: ['token'])]
#[ORM\Index(name: 'active_idx', columns: ['is_active'])]
#[ORM\HasLifecycleCallbacks]
class GuildInvitation
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?Uuid $id = null;

    #[ORM\Column(length: 64, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 32, max: 64)]
    private string $token;

    #[ORM\ManyToOne(targetEntity: GameGuild::class)]
    #[ORM\JoinColumn(name: 'guild_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private GameGuild $guild;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $createdBy;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: GuildRole::class)]
    private GuildRole $role;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\Positive]
    #[Assert\LessThanOrEqual(1)]
    private int $maxUses = 1;

    #[ORM\Column(type: Types::INTEGER)]
    #[Assert\PositiveOrZero]
    private int $usedCount = 0;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isActive = true;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'used_by_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $usedBy = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $usedAt = null;

    public function __construct(GameGuild $guild, User $createdBy, GuildRole $role = GuildRole::MEMBER, ?\DateTimeImmutable $expiresAt = null)
    {
        $this->token = bin2hex(random_bytes(16));
        $this->guild = $guild;
        $this->createdBy = $createdBy;
        $this->role = $role;
        $this->createdAt = new \DateTimeImmutable();
        $this->expiresAt = $expiresAt;
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUuidToString(): string
    {
        return $this->id->toString();
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): static
    {
        $this->token = $token;
        return $this;
    }

    public function getGuild(): GameGuild
    {
        return $this->guild;
    }

    public function setGuild(GameGuild $guild): static
    {
        $this->guild = $guild;
        return $this;
    }

    public function getCreatedBy(): User
    {
        return $this->createdBy;
    }

    public function setCreatedBy(User $createdBy): static
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(?\DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;
        return $this;
    }

    public function getRole(): GuildRole
    {
        return $this->role;
    }

    public function setRole(GuildRole $role): static
    {
        $this->role = $role;
        return $this;
    }

    public function getMaxUses(): int
    {
        return $this->maxUses;
    }

    public function getUsedCount(): int
    {
        return $this->usedCount;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;
        return $this;
    }

    public function getUsedBy(): ?User
    {
        return $this->usedBy;
    }

    public function getUsedAt(): ?\DateTimeImmutable
    {
        return $this->usedAt;
    }

    public function isExpired(): bool
    {
        if ($this->expiresAt === null) {
            return false;
        }

        return $this->expiresAt < new \DateTimeImmutable();
    }

    public function canBeUsed(): bool
    {
        if (!$this->isActive) {
            return false;
        }

        if ($this->isExpired()) {
            return false;
        }

        if ($this->usedCount >= $this->maxUses) {
            return false;
        }

        return true;
    }

    public function markAsUsed(User $user): void
    {
        $this->usedCount++;
        $this->usedBy = $user;
        $this->usedAt = new \DateTimeImmutable();

        if ($this->usedCount >= $this->maxUses) {
            $this->isActive = false;
        }
    }

    public function revoke(): void
    {
        $this->isActive = false;
    }

    public function isUsed(): bool
    {
        return $this->usedCount > 0;
    }

    public function getRemainingUses(): int
    {
        return max(0, $this->maxUses - $this->usedCount);
    }
}
