<?php

namespace App\DTO;

use App\Entity\GameGuild;

readonly class GameGuildDTO
{
    private function __construct(
        public string $id,
        public string $name,
        public string $faction,
        public bool $isPublic,
        public bool $showDkpPublic,
        public string $recruitingStatus,
        public int    $nbrGuildMembers,
        public int    $nbrCharacters,
        public bool   $isValid,
        public array  $userIds = [],
        public ?string $myRole = null,
    ) {}

    public static function fromEntity(GameGuild $guild, ?string $myRole = null): self
    {
        $nbrGuildMembers = count($guild->getGuildMemberships() ?? 0);
        $nbrCharacters = count($guild->getGameCharacters() ?? 0);

        return new self(
            $guild->getId()->toRfc4122(),
            $guild->getName(),
            $guild->getFaction() ?? '',
            $guild->isPublic(),
            $guild->isShowDkpPublic(),
            $guild->getRecruitingStatus()->value,
            $nbrGuildMembers,
            $nbrCharacters,
            $guild->isValid(),
            $guild?->getGuildMemberships()?->map(
                static fn($m) => $m?->getUser()?->getId()?->toRfc4122()
            )->toArray(),
            $myRole
            );
    }

    
    public static function fromEntities(iterable $guilds): array
    {
        $out = [];
        foreach ($guilds as $g) {
            $out[] = self::fromEntity($g, null);
        }
        return $out;
    }
}
