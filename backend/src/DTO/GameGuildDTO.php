<?php

namespace App\DTO;

use App\Entity\GameGuild;

final class GameGuildDTO
{
    public string $id;
    public string $name;
    public string $faction;

    /** @var list<string> */
    public array $membershipIds;

    private function __construct(string $id, string $name, string $faction, array $membershipIds)
    {
        $this->id = $id;
        $this->name = $name;
        $this->faction = $faction;
        $this->membershipIds = $membershipIds;
    }

    public static function fromEntity(GameGuild $guild): self
    {
        $membershipIds = [];
        foreach ($guild->getGuildMemberships() as $membership) {
            $membershipIds[] = $membership->getUuidToString();
        }

        // Si faction est un enum BackedEnum, passer la valeur string :
        $faction = $guild->getFaction();
        $factionString = \is_object($faction) && method_exists($faction, 'value')
            ? $faction->value
            : (string) $faction;

        return new self(
            $guild->getUuidToString(),
            $guild->getName(),
            $factionString,
            $membershipIds
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
