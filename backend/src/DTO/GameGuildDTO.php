<?php

namespace App\DTO;

use App\Entity\GameGuild;

readonly class GameGuildDTO
{
    private function __construct(
        public string $id,
        public string $name,
        public string $faction,
        public int    $nbrGuildMembers,
        public int    $nbrCharacters,
        public array  $userIds = []
    ) {}

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
