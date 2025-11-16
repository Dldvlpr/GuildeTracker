<?php

namespace App\Entity;

use App\Enum\WowGameType;
use App\Repository\BlizzardGameRealmRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BlizzardGameRealmRepository::class)]
#[ORM\UniqueConstraint(name: 'unique_realm_type_region', columns: ['slug', 'game_type', 'region'])]
class BlizzardGameRealm
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?int $blizzardRealmId = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\Column(type: 'string', enumType: WowGameType::class)]
    private ?WowGameType $gameType = null;

    #[ORM\Column(length: 10)]
    private ?string $region = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $locale = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $timezone = null;

    #[ORM\Column(nullable: true)]
    private ?int $connectedRealmId = null;

    #[ORM\Column]
    private bool $isTournament = false;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $type = null;

    #[ORM\OneToMany(targetEntity: GameGuild::class, mappedBy: 'blizzardRealm')]
    private Collection $guilds;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastSyncAt = null;

    public function __construct()
    {
        $this->guilds = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBlizzardRealmId(): ?int
    {
        return $this->blizzardRealmId;
    }

    public function setBlizzardRealmId(?int $blizzardRealmId): static
    {
        $this->blizzardRealmId = $blizzardRealmId;
        return $this;
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

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;
        return $this;
    }

    public function getGameType(): ?WowGameType
    {
        return $this->gameType;
    }

    public function setGameType(WowGameType $gameType): static
    {
        $this->gameType = $gameType;
        return $this;
    }

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(string $region): static
    {
        $this->region = $region;
        return $this;
    }

    public function getLocale(): ?string
    {
        return $this->locale;
    }

    public function setLocale(?string $locale): static
    {
        $this->locale = $locale;
        return $this;
    }

    public function getTimezone(): ?string
    {
        return $this->timezone;
    }

    public function setTimezone(?string $timezone): static
    {
        $this->timezone = $timezone;
        return $this;
    }

    public function getConnectedRealmId(): ?int
    {
        return $this->connectedRealmId;
    }

    public function setConnectedRealmId(?int $connectedRealmId): static
    {
        $this->connectedRealmId = $connectedRealmId;
        return $this;
    }

    public function isTournament(): bool
    {
        return $this->isTournament;
    }

    public function setIsTournament(bool $isTournament): static
    {
        $this->isTournament = $isTournament;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;
        return $this;
    }

    
    public function getGuilds(): Collection
    {
        return $this->guilds;
    }

    public function addGuild(GameGuild $guild): static
    {
        if (!$this->guilds->contains($guild)) {
            $this->guilds->add($guild);
            $guild->setBlizzardRealm($this);
        }
        return $this;
    }

    public function removeGuild(GameGuild $guild): static
    {
        if ($this->guilds->removeElement($guild)) {
            if ($guild->getBlizzardRealm() === $this) {
                $guild->setBlizzardRealm(null);
            }
        }
        return $this;
    }

    public function getLastSyncAt(): ?\DateTimeImmutable
    {
        return $this->lastSyncAt;
    }

    public function setLastSyncAt(?\DateTimeImmutable $lastSyncAt): static
    {
        $this->lastSyncAt = $lastSyncAt;
        return $this;
    }

    
    public function getProfileNamespace(): string
    {
        return $this->gameType->getProfileNamespace($this->region);
    }

    
    public function getDynamicNamespace(): string
    {
        return $this->gameType->getDynamicNamespace($this->region);
    }

    
    public function getStaticNamespace(): string
    {
        return $this->gameType->getStaticNamespace($this->region);
    }

    public function __toString(): string
    {
        return sprintf('%s (%s - %s)', $this->name, $this->gameType?->getLabel(), strtoupper($this->region));
    }
}
