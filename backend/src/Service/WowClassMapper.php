<?php

namespace App\Service;


class WowClassMapper
{
    
    private const CLASS_DATA = [
        1 => [
            'name' => 'Warrior',
            'color' => '#C79C6E',
            'roles' => ['Tank', 'DPS'],
            'armor' => 'Plate',
            'resource' => 'Rage',
        ],
        2 => [
            'name' => 'Paladin',
            'color' => '#F58CBA',
            'roles' => ['Tank', 'Healer', 'DPS'],
            'armor' => 'Plate',
            'resource' => 'Mana',
        ],
        3 => [
            'name' => 'Hunter',
            'color' => '#ABD473',
            'roles' => ['DPS'],
            'armor' => 'Mail',
            'resource' => 'Focus',
        ],
        4 => [
            'name' => 'Rogue',
            'color' => '#FFF569',
            'roles' => ['DPS'],
            'armor' => 'Leather',
            'resource' => 'Energy',
        ],
        5 => [
            'name' => 'Priest',
            'color' => '#FFFFFF',
            'roles' => ['Healer', 'DPS'],
            'armor' => 'Cloth',
            'resource' => 'Mana',
        ],
        6 => [
            'name' => 'Death Knight',
            'color' => '#C41F3B',
            'roles' => ['Tank', 'DPS'],
            'armor' => 'Plate',
            'resource' => 'Runic Power',
        ],
        7 => [
            'name' => 'Shaman',
            'color' => '#0070DE',
            'roles' => ['Healer', 'DPS'],
            'armor' => 'Mail',
            'resource' => 'Mana',
        ],
        8 => [
            'name' => 'Mage',
            'color' => '#69CCF0',
            'roles' => ['DPS'],
            'armor' => 'Cloth',
            'resource' => 'Mana',
        ],
        9 => [
            'name' => 'Warlock',
            'color' => '#9482C9',
            'roles' => ['DPS'],
            'armor' => 'Cloth',
            'resource' => 'Mana',
        ],
        10 => [
            'name' => 'Monk',
            'color' => '#00FF96',
            'roles' => ['Tank', 'Healer', 'DPS'],
            'armor' => 'Leather',
            'resource' => 'Energy',
        ],
        11 => [
            'name' => 'Druid',
            'color' => '#FF7D0A',
            'roles' => ['Tank', 'Healer', 'DPS'],
            'armor' => 'Leather',
            'resource' => 'Mana',
        ],
        12 => [
            'name' => 'Demon Hunter',
            'color' => '#A330C9',
            'roles' => ['Tank', 'DPS'],
            'armor' => 'Leather',
            'resource' => 'Fury',
        ],
        13 => [
            'name' => 'Evoker',
            'color' => '#33937F',
            'roles' => ['Healer', 'DPS'],
            'armor' => 'Mail',
            'resource' => 'Essence',
        ],
    ];

    
    private const CLASS_MAP = [
        1 => 'Warrior',
        2 => 'Paladin',
        3 => 'Hunter',
        4 => 'Rogue',
        5 => 'Priest',
        6 => 'Death Knight',
        7 => 'Shaman',
        8 => 'Mage',
        9 => 'Warlock',
        10 => 'Monk',
        11 => 'Druid',
        12 => 'Demon Hunter',
        13 => 'Evoker',
    ];

    private const RACE_MAP = [
        1 => 'Human',
        2 => 'Orc',
        3 => 'Dwarf',
        4 => 'Night Elf',
        5 => 'Undead',
        6 => 'Tauren',
        7 => 'Gnome',
        8 => 'Troll',
        9 => 'Goblin',
        10 => 'Blood Elf',
        11 => 'Draenei',
        22 => 'Worgen',
        24 => 'Pandaren (Neutral)',
        25 => 'Pandaren (Alliance)',
        26 => 'Pandaren (Horde)',
        27 => 'Nightborne',
        28 => 'Highmountain Tauren',
        29 => 'Void Elf',
        30 => 'Lightforged Draenei',
        31 => 'Zandalari Troll',
        32 => 'Kul Tiran',
        34 => 'Dark Iron Dwarf',
        35 => 'Vulpera',
        36 => 'Mag\'har Orc',
        37 => 'Mechagnome',
        52 => 'Dracthyr',
        70 => 'Earthen',
    ];

    
    public function getClassName(int $classId): string
    {
        return self::CLASS_MAP[$classId] ?? 'Unknown';
    }

    
    public function getClassColor(int $classId): ?string
    {
        return self::CLASS_DATA[$classId]['color'] ?? null;
    }

    
    public function getClassRoles(int $classId): array
    {
        return self::CLASS_DATA[$classId]['roles'] ?? [];
    }

    
    public function getClassData(int $classId): ?array
    {
        return self::CLASS_DATA[$classId] ?? null;
    }

    
    public function getRaceName(int $raceId): string
    {
        return self::RACE_MAP[$raceId] ?? 'Unknown';
    }

    
    public function isValidClassId(int $classId): bool
    {
        return isset(self::CLASS_MAP[$classId]);
    }

    
    public function isValidRaceId(int $raceId): bool
    {
        return isset(self::RACE_MAP[$raceId]);
    }

    
    public function getAllClasses(): array
    {
        return self::CLASS_MAP;
    }

    
    public function getAllClassesWithMetadata(): array
    {
        return self::CLASS_DATA;
    }

    
    public function getAllRaces(): array
    {
        return self::RACE_MAP;
    }

    
    public function getClassIdByName(string $className): ?int
    {
        $normalizedName = strtolower(trim($className));

        foreach (self::CLASS_MAP as $id => $name) {
            if (strtolower($name) === $normalizedName) {
                return $id;
            }
        }

        return null;
    }

    
    public function getRoleFromSpec(string $specName): string
    {
        $specLower = strtolower(trim($specName));

        $tankSpecs = [

            'protection', 'blood', 'guardian', 'brewmaster', 'vengeance',

            'protection', 'sang', 'gardien', 'maître brasseur', 'vengeance',

            'schutz', 'blut', 'wächter', 'braumeister', 'vergeltung',

            'protección', 'sangre', 'guardián', 'maestro cervecero', 'venganza',
        ];

        $healerSpecs = [

            'holy', 'discipline', 'restoration', 'mistweaver', 'preservation',

            'sacré', 'discipline', 'restauration', 'tisse-brume', 'préservation',

            'heilig', 'disziplin', 'wiederherstellung', 'nebelwirker', 'bewahrung',

            'sagrado', 'disciplina', 'restauración', 'tejedor de niebla', 'preservación',
        ];

        foreach ($tankSpecs as $tank) {
            if (str_contains($specLower, $tank)) {
                return 'Tank';
            }
        }

        foreach ($healerSpecs as $healer) {
            if (str_contains($specLower, $healer)) {
                return 'Healer';
            }
        }

        return 'DPS';
    }
}
