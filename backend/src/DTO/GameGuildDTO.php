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

    private function __construct(string $id, string $name, string $faction, array $userIds)
    {
        $this->id = $id;
        $this->name = $name;
        $this->faction = $faction;
        $this->userIds = $userIds;
    }

    public static function fromEntity(GameGuild $guild): self
    {
        $userIds = [];
        foreach ($guild->getGuildMemberships() as $membership) {
            $userIds[] = (string) $membership->getUser()->getId();
        }

        return new self(
            $guild->getUuidToString(),
            $guild->getName() ?? '',
            $guild->getFaction() ?? '',
            $userIds
        );
    }

    /**
     * @param iterable<GameGuild> $guilds
     * @return list<self>
     */
    public static function fromEntities(iterable $guilds): array
    {
        $dtos = [];
        foreach ($guilds as $guild) {
            $dtos[] = self::fromEntity($guild);
        }
        return $dtos;
    }
}
