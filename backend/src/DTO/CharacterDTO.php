<?php

namespace App\DTO;

use App\Entity\GameCharacter;

class CharacterDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $class = null,
        public readonly ?string $spec = null,
        public readonly ?string $role = null,
        public readonly ?string $guildName = null,
        public readonly string $status = 'active',
        public readonly string $createdAt = '',
        public readonly ?string $updatedAt = null
    ) {}

    public static function fromEntity(GameCharacter $gameCharacter): self
    {
        return new self(
            $gameCharacter->getId()->toString(),
            $gameCharacter->getName(),
            $gameCharacter->getClass(),
            $gameCharacter->getClassSpec(),
            $gameCharacter->getRole(),
            $gameCharacter->getGuild()?->getName(),
            'active',
            (new \DateTime())->format('Y-m-d\TH:i:s.v\Z'),
            null
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
