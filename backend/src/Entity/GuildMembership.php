<?php
declare(strict_types=1);

namespace App\Entity;

use App\Enum\GuildRole;
use App\Repository\GuildMembershipRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: GuildMembershipRepository::class)]
#[ORM\Table(name: 'guild_membership')]
#[ORM\UniqueConstraint(name: 'uniq_user_guild', columns: ['user_id', 'guild_id'])]
class GuildMembership
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?Uuid $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'memberships')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: GameGuild::class, inversedBy: 'guildMemberships')]
    #[ORM\JoinColumn(name: 'guild_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private GameGuild $guild;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: GuildRole::class)]
    private GuildRole $role;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $joinedAt;

    public function __construct(User $user, GameGuild $guild, GuildRole $role)
    {
        $this->user = $user;
        $this->guild = $guild;
        $this->role = $role;
        $this->joinedAt = new \DateTimeImmutable();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUuidToString(): string
    {
        return $this->id->toString();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;
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

    public function getRole(): GuildRole
    {
        return $this->role;
    }

    public function setRole(GuildRole $role): self
    {
        $this->role = $role;
        return $this;
    }

    public function getJoinedAt(): \DateTimeImmutable
    {
        return $this->joinedAt;
    }
}
