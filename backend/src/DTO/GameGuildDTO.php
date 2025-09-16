<?php

namespace App\DTO;

use App\Entity\GameGuild;

class GameGuildDTO
{
    public string $id;
    public string $name;
    public string $faction;
    /** @var string[] */
    public array $userIds;
    public int $nbrGuildMembers;
    public int $nbrCharacters;

    private function __construct(
        string $id,
        string $name,
        string $faction,
        int $nbrGuildMembers,
        int $nbrCharacters,
        array $userIds = []
    ) {
        $this->id = $id;
        $this->name = $name;
        $this->faction = $faction;
        $this->nbrGuildMembers = $nbrGuildMembers;
        $this->nbrCharacters = $nbrCharacters;
        $this->userIds = $userIds;
    }

    public static function fromEntity(GameGuild $guild): self
    {
        $nbrGuildMembers = count($guild->getGuildMemberships() ?? 0);
        $nbrCharacters = count($guild->getGameCharacters() ?? 0);

        return new self(
            $guild->getId()->toRfc4122(),
            $guild->getName(),
            $guild->getFaction() ?? '',
            $nbrGuildMembers,
            $nbrCharacters,
            $guild?->getGuildMemberships()?->map(
                static fn($m) => $m?->getUser()?->getId()?->toRfc4122()
            )->toArray()
            );
    }

    /**
     * @param iterable<GameGuild> $guilds
     * @return self[]
     */
    public static function fromEntities(iterable $guilds): array
    {
        $out = [];
        foreach ($guilds as $g) {
            $out[] = self::fromEntity($g);
        }
        return $out;
    }
}
