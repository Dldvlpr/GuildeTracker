<?php

namespace App\DTO;

use App\Entity\GameCharacter;
use App\Service\WowClassMapper;

readonly class CharacterDTO
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?string $class = null,
        public readonly ?string $classColor = null,
        public readonly ?array $classRoles = null,
        public readonly ?string $spec = null,
        public readonly ?string $role = null,
        public readonly ?string $guildName = null,
        public readonly string $status = 'active',
        public readonly string $createdAt = '',
        public readonly ?string $updatedAt = null
    ) {}

    public static function fromEntity(GameCharacter $gameCharacter, ?WowClassMapper $classMapper = null): self
    {
        $className = $gameCharacter->getClass();
        $classColor = null;
        $classRoles = null;

        if ($classMapper && $className && $className !== 'Unknown') {
            $classId = $classMapper->getClassIdByName($className);
            if ($classId) {
                $classColor = $classMapper->getClassColor($classId);
                $classRoles = $classMapper->getClassRoles($classId);
            }
        }

        return new self(
            $gameCharacter->getId()->toString(),
            $gameCharacter->getName(),
            $className,
            $classColor,
            $classRoles,
            $gameCharacter->getClassSpec(),
            $gameCharacter->getRole(),
            $gameCharacter->getGuild()?->getName(),
            'active',
            (new \DateTime())->format('Y-m-d\TH:i:s.v\Z'),
            null
        );
    }

    public static function fromEntities(iterable $gameCharacters, ?WowClassMapper $classMapper = null): array
    {
        $out = [];
        foreach ($gameCharacters as $ge) {
            $out[] = self::fromEntity($ge, $classMapper);
        }
        return $out;
    }
}
