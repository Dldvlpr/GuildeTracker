<?php

namespace App\Entity;

use App\Enum\WowGameType;
use App\Repository\KnownRealmMetadataRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: KnownRealmMetadataRepository::class)]
#[ORM\Table(name: 'known_realm_metadata')]
#[ORM\UniqueConstraint(name: 'known_realm_metadata_unique', columns: ['slug', 'region'])]
class KnownRealmMetadata
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\Column(length: 10)]
    private ?string $region = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $launchDate = null;

    #[ORM\Column(enumType: WowGameType::class)]
    private ?WowGameType $expectedGameType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $source = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $confidenceScore = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getRegion(): ?string
    {
        return $this->region;
    }

    public function setRegion(string $region): static
    {
        $this->region = $region;
        return $this;
    }

    public function getLaunchDate(): ?\DateTimeImmutable
    {
        return $this->launchDate;
    }

    public function setLaunchDate(?\DateTimeImmutable $launchDate): static
    {
        $this->launchDate = $launchDate;
        return $this;
    }

    public function getExpectedGameType(): ?WowGameType
    {
        return $this->expectedGameType;
    }

    public function setExpectedGameType(WowGameType $expectedGameType): static
    {
        $this->expectedGameType = $expectedGameType;
        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): static
    {
        $this->source = $source;
        return $this;
    }

    public function getConfidenceScore(): ?int
    {
        return $this->confidenceScore;
    }

    public function setConfidenceScore(?int $confidenceScore): static
    {
        $this->confidenceScore = $confidenceScore;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }
}
