<?php

namespace App\DTO;

use App\Entity\GuildMembership;
use App\Enum\GuildRole;

readonly class GuildMembershipDTO
{
    private function __construct(
        public string $id,
        public string $name,
        public GuildRole $role,
    ) {}

    public static function fromEntity(GuildMembership $membership): self
    {
        return new self(
            $membership->getId()->toRfc4122(),
            $membership->getUserName(),
            $membership->getRole(),
        );
    }

    /**
     * @param iterable $guildMembership
     * @return self[]
     */
    public static function fromEntities(iterable $guildMembership): array
    {
        $out = [];
        foreach ($guildMembership as $m) {
            $out[] = self::fromEntity($m);
        }
        return $out;
    }
}
