<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\IdGenerator\UuidGenerator;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_DISCORDID', fields: ['discordId'])]
#[UniqueEntity(fields: ['email'], message: 'Cette adresse est déjà utilisée !')]
#[UniqueEntity(fields: ['discordId'], message: 'Ce compte discord est déjà utilisé !')]
class User implements UserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?Uuid $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column(length: 255)]
    private ?string $discordId = null;

    #[ORM\Column(length: 255)]
    private ?string $username = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $blizzardId = null;

    /**
     * @var Collection<int, GameCharacter>
     */
    #[ORM\OneToMany(targetEntity: GameCharacter::class, mappedBy: 'userPlayer')]
    #[ORM\JoinColumn(nullable: true)]
    private Collection $gameCharacters;

    public function __construct()
    {
        $this->gameCharacters = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getUuidToString(): string
    {
        return $this->id->toString();
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->discordId;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getDiscordId(): ?string
    {
        return $this->discordId;
    }

    public function setDiscordId(string $discordId): static
    {
        $this->discordId = $discordId;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getBlizzardId(): ?string
    {
        return $this->blizzardId;
    }

    public function setBlizzardId(?string $blizzardId): static
    {
        $this->blizzardId = $blizzardId;

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
            $gameCharacter->setUserPlayer($this);
        }

        return $this;
    }

    public function removeGameCharacter(GameCharacter $gameCharacter): static
    {
        if ($this->gameCharacters->removeElement($gameCharacter)) {
            // set the owning side to null (unless already changed)
            if ($gameCharacter->getUserPlayer() === $this) {
                $gameCharacter->setUserPlayer(null);
            }
        }

        return $this;
    }
}
