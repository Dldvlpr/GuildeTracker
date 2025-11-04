<?php

namespace App\Entity;

use App\Enum\RecruitingStatus;
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

    #[ORM\OneToMany(targetEntity: GameCharacter::class, mappedBy: 'guild')]
    private Collection $gameCharacters;

    #[ORM\OneToMany(targetEntity: GuildMembership::class, mappedBy: 'guild')]
    private Collection $guildMemberships;

    #[ORM\Column]
    private ?bool $isPublic = false;

    #[ORM\Column]
    private ?bool $showDkpPublic = false;

    #[ORM\Column(type: 'string', enumType: RecruitingStatus::class)]
    private ?RecruitingStatus $recruitingStatus = RecruitingStatus::CLOSED;

    #[ORM\ManyToOne(targetEntity: BlizzardGameRealm::class, inversedBy: 'guilds')]
    #[ORM\JoinColumn(nullable: true)]
    private ?BlizzardGameRealm $blizzardRealm = null;

    public function __construct()
    {
        $this->gameCharacters = new ArrayCollection();
        $this->guildMemberships = new ArrayCollection();
    }

    public function getId(): ?Uuid
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
            $guildMembership->setGuild($this);
        }

        return $this;
    }

    public function removeGuildMembership(GuildMembership $guildMembership): static
    {
        if ($this->guildMemberships->removeElement($guildMembership)) {
            if ($guildMembership->getGuild() === $this) {
                // Relationship is non-nullable on owning side; rely on orphanRemoval if enabled
                // Do not set to null to avoid violating non-nullable constraint
            }
        }

        return $this;
    }

    public function isPublic(): ?bool
    {
        return $this->isPublic;
    }

    public function setIsPublic(bool $isPublic): static
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    public function isShowDkpPublic(): ?bool
    {
        return $this->showDkpPublic;
    }

    public function setShowDkpPublic(bool $showDkpPublic): static
    {
        $this->showDkpPublic = $showDkpPublic;

        return $this;
    }

    public function getRecruitingStatus(): ?RecruitingStatus
    {
        return $this->recruitingStatus;
    }

    public function setRecruitingStatus(RecruitingStatus $recruitingStatus): static
    {
        $this->recruitingStatus = $recruitingStatus;

        return $this;
    }

    public function hasCharacters(): bool
    {
        return $this->gameCharacters->count() > 0;
    }

    public function isValid(): bool
    {
        return $this->hasCharacters();
    }

    public function getBlizzardRealm(): ?BlizzardGameRealm
    {
        return $this->blizzardRealm;
    }

    public function setBlizzardRealm(?BlizzardGameRealm $blizzardRealm): static
    {
        $this->blizzardRealm = $blizzardRealm;

        return $this;
    }
}
