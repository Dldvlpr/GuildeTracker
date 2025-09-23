<?php

namespace App\DTO;

use App\Entity\GameCharacter;

class CharacterDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $guildName = null
    ) {}

    public static function fromEntity(GameCharacter $gameCharacter): self
    {
        return new self(
            $gameCharacter->getId()->toString(),
            $gameCharacter->getName(),
            $gameCharacter->getGuild()?->getName()
        );
    }

    public static function fromEntities(iterable $gameCharacters): array
    {
        $out = [];
        foreach ($gameCharacters as $ge) {
            $out[] = self::fromEntity($ge);
        }
        return $out;
    }
}
