<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fetch-wow-raids',
    description: 'Fetch and structure all WoW raids from Classic to Retail with Wowhead links',
)]
class FetchWowRaidsCommand extends Command
{
    
    private const RAID_DATA = [
        'Classic' => [
            'Molten Core' => [
                ['name' => 'Lucifron', 'npc' => 12118],
                ['name' => 'Magmadar', 'npc' => 11982],
                ['name' => 'Gehennas', 'npc' => 12259],
                ['name' => 'Garr', 'npc' => 12057],
                ['name' => 'Baron Geddon', 'npc' => 12056],
                ['name' => 'Shazzrah', 'npc' => 12264],
                ['name' => 'Sulfuron Harbinger', 'npc' => 12098],
                ['name' => 'Golemagg the Incinerator', 'npc' => 11988],
                ['name' => 'Majordomo Executus', 'npc' => 12018],
                ['name' => 'Ragnaros', 'npc' => 11502],
            ],
            'Blackwing Lair' => [
                ['name' => 'Razorgore the Untamed', 'npc' => 12435],
                ['name' => 'Vaelastrasz the Corrupt', 'npc' => 13020],
                ['name' => 'Broodlord Lashlayer', 'npc' => 12017],
                ['name' => 'Firemaw', 'npc' => 11983],
                ['name' => 'Ebonroc', 'npc' => 14601],
                ['name' => 'Flamegor', 'npc' => 11981],
                ['name' => 'Chromaggus', 'npc' => 14020],
                ['name' => 'Nefarian', 'npc' => 11583],
            ],
            'Ahn\'Qiraj (AQ20)' => [
                ['name' => 'Kurinnaxx', 'npc' => 15348],
                ['name' => 'General Rajaxx', 'npc' => 15341],
                ['name' => 'Moam', 'npc' => 15340],
                ['name' => 'Buru the Gorger', 'npc' => 15370],
                ['name' => 'Ayamiss the Hunter', 'npc' => 15369],
                ['name' => 'Ossirian the Unscarred', 'npc' => 15339],
            ],
            'Ahn\'Qiraj (AQ40)' => [
                ['name' => 'The Prophet Skeram', 'npc' => 15263],
                ['name' => 'Silithid Royalty', 'npc' => 15543],
                ['name' => 'Battleguard Sartura', 'npc' => 15516],
                ['name' => 'Fankriss the Unyielding', 'npc' => 15510],
                ['name' => 'Viscidus', 'npc' => 15299],
                ['name' => 'Princess Huhuran', 'npc' => 15509],
                ['name' => 'Twin Emperors', 'npc' => 15276],
                ['name' => 'Ouro', 'npc' => 15517],
                ['name' => 'C\'Thun', 'npc' => 15727],
            ],
            'Naxxramas' => [
                ['name' => 'Anub\'Rekhan', 'npc' => 15956],
                ['name' => 'Grand Widow Faerlina', 'npc' => 15953],
                ['name' => 'Maexxna', 'npc' => 15952],
                ['name' => 'Noth the Plaguebringer', 'npc' => 15954],
                ['name' => 'Heigan the Unclean', 'npc' => 15936],
                ['name' => 'Loatheb', 'npc' => 16011],
                ['name' => 'Instructor Razuvious', 'npc' => 16061],
                ['name' => 'Gothik the Harvester', 'npc' => 16060],
                ['name' => 'The Four Horsemen', 'npc' => 16064],
                ['name' => 'Patchwerk', 'npc' => 16028],
                ['name' => 'Grobbulus', 'npc' => 15931],
                ['name' => 'Gluth', 'npc' => 15932],
                ['name' => 'Thaddius', 'npc' => 15928],
                ['name' => 'Sapphiron', 'npc' => 15989],
                ['name' => 'Kel\'Thuzad', 'npc' => 15990],
            ],
            'Zul\'Gurub' => [
                ['name' => 'High Priestess Jeklik', 'npc' => 14517],
                ['name' => 'High Priest Venoxis', 'npc' => 14507],
                ['name' => 'High Priestess Mar\'li', 'npc' => 14510],
                ['name' => 'Bloodlord Mandokir', 'npc' => 11382],
                ['name' => 'Edge of Madness', 'npc' => 14988],
                ['name' => 'High Priest Thekal', 'npc' => 14509],
                ['name' => 'High Priestess Arlokk', 'npc' => 14515],
                ['name' => 'Jin\'do the Hexxer', 'npc' => 11380],
                ['name' => 'Hakkar', 'npc' => 14834],
            ],
        ],
        'TBC' => [
            'Karazhan' => [
                ['name' => 'Attumen the Huntsman', 'npc' => 16151],
                ['name' => 'Moroes', 'npc' => 15687],
                ['name' => 'Maiden of Virtue', 'npc' => 16457],
                ['name' => 'Opera Event', 'npc' => 17534],
                ['name' => 'The Curator', 'npc' => 15691],
                ['name' => 'Terestian Illhoof', 'npc' => 15688],
                ['name' => 'Shade of Aran', 'npc' => 16524],
                ['name' => 'Netherspite', 'npc' => 15689],
                ['name' => 'Chess Event', 'npc' => 16816],
                ['name' => 'Prince Malchezaar', 'npc' => 15690],
            ],
            'Gruul\'s Lair' => [
                ['name' => 'High King Maulgar', 'npc' => 18831],
                ['name' => 'Gruul the Dragonkiller', 'npc' => 19044],
            ],
            'Magtheridon\'s Lair' => [
                ['name' => 'Magtheridon', 'npc' => 17257],
            ],
            'Serpentshrine Cavern' => [
                ['name' => 'Hydross the Unstable', 'npc' => 21216],
                ['name' => 'The Lurker Below', 'npc' => 21217],
                ['name' => 'Leotheras the Blind', 'npc' => 21215],
                ['name' => 'Fathom-Lord Karathress', 'npc' => 21214],
                ['name' => 'Morogrim Tidewalker', 'npc' => 21213],
                ['name' => 'Lady Vashj', 'npc' => 21212],
            ],
            'Tempest Keep' => [
                ['name' => 'Al\'ar', 'npc' => 19514],
                ['name' => 'Void Reaver', 'npc' => 19516],
                ['name' => 'High Astromancer Solarian', 'npc' => 18805],
                ['name' => 'Kael\'thas Sunstrider', 'npc' => 19622],
            ],
            'Black Temple' => [
                ['name' => 'High Warlord Naj\'entus', 'npc' => 22887],
                ['name' => 'Supremus', 'npc' => 22898],
                ['name' => 'Shade of Akama', 'npc' => 22841],
                ['name' => 'Teron Gorefiend', 'npc' => 22871],
                ['name' => 'Gurtogg Bloodboil', 'npc' => 22948],
                ['name' => 'Reliquary of Souls', 'npc' => 22856],
                ['name' => 'Mother Shahraz', 'npc' => 22947],
                ['name' => 'The Illidari Council', 'npc' => 23426],
                ['name' => 'Illidan Stormrage', 'npc' => 22917],
            ],
            'Sunwell Plateau' => [
                ['name' => 'Kalecgos', 'npc' => 24850],
                ['name' => 'Brutallus', 'npc' => 24882],
                ['name' => 'Felmyst', 'npc' => 25038],
                ['name' => 'Eredar Twins', 'npc' => 25166],
                ['name' => 'M\'uru', 'npc' => 25741],
                ['name' => 'Kil\'jaeden', 'npc' => 25315],
            ],
        ],
        'WotLK' => [
            'Naxxramas' => [
                ['name' => 'Anub\'Rekhan', 'npc' => 15956],
                ['name' => 'Grand Widow Faerlina', 'npc' => 15953],
                ['name' => 'Maexxna', 'npc' => 15952],
                ['name' => 'Noth the Plaguebringer', 'npc' => 15954],
                ['name' => 'Heigan the Unclean', 'npc' => 15936],
                ['name' => 'Loatheb', 'npc' => 16011],
                ['name' => 'Instructor Razuvious', 'npc' => 16061],
                ['name' => 'Gothik the Harvester', 'npc' => 16060],
                ['name' => 'The Four Horsemen', 'npc' => 16064],
                ['name' => 'Patchwerk', 'npc' => 16028],
                ['name' => 'Grobbulus', 'npc' => 15931],
                ['name' => 'Gluth', 'npc' => 15932],
                ['name' => 'Thaddius', 'npc' => 15928],
                ['name' => 'Sapphiron', 'npc' => 15989],
                ['name' => 'Kel\'Thuzad', 'npc' => 15990],
            ],
            'Ulduar' => [
                ['name' => 'Flame Leviathan', 'npc' => 33113],
                ['name' => 'Ignis the Furnace Master', 'npc' => 33118],
                ['name' => 'Razorscale', 'npc' => 33186],
                ['name' => 'XT-002 Deconstructor', 'npc' => 33293],
                ['name' => 'Assembly of Iron', 'npc' => 32867],
                ['name' => 'Kologarn', 'npc' => 32930],
                ['name' => 'Auriaya', 'npc' => 33515],
                ['name' => 'Hodir', 'npc' => 32845],
                ['name' => 'Thorim', 'npc' => 32865],
                ['name' => 'Freya', 'npc' => 32906],
                ['name' => 'Mimiron', 'npc' => 33350],
                ['name' => 'General Vezax', 'npc' => 33271],
                ['name' => 'Yogg-Saron', 'npc' => 33288],
                ['name' => 'Algalon the Observer', 'npc' => 32871],
            ],
            'Trial of the Crusader' => [
                ['name' => 'Northrend Beasts', 'npc' => 34796],
                ['name' => 'Lord Jaraxxus', 'npc' => 34780],
                ['name' => 'Faction Champions', 'npc' => 34458],
                ['name' => 'Twin Val\'kyr', 'npc' => 34497],
                ['name' => 'Anub\'arak', 'npc' => 34564],
            ],
            'Icecrown Citadel' => [
                ['name' => 'Lord Marrowgar', 'npc' => 36612],
                ['name' => 'Lady Deathwhisper', 'npc' => 36855],
                ['name' => 'Gunship Battle', 'npc' => 37215],
                ['name' => 'Deathbringer Saurfang', 'npc' => 37813],
                ['name' => 'Festergut', 'npc' => 36626],
                ['name' => 'Rotface', 'npc' => 36627],
                ['name' => 'Professor Putricide', 'npc' => 36678],
                ['name' => 'Blood Prince Council', 'npc' => 37970],
                ['name' => 'Blood-Queen Lana\'thel', 'npc' => 37955],
                ['name' => 'Valithria Dreamwalker', 'npc' => 36789],
                ['name' => 'Sindragosa', 'npc' => 36853],
                ['name' => 'The Lich King', 'npc' => 36597],
            ],
        ],
        'TWW' => [
            'Nerub-ar Palace' => [
                ['name' => 'Ulgrax the Devourer', 'npc' => 215657, 'retail' => true],
                ['name' => 'The Bloodbound Horror', 'npc' => 214502, 'retail' => true],
                ['name' => 'Sikran', 'npc' => 214503, 'retail' => true],
                ['name' => 'Rasha\'nan', 'npc' => 214504, 'retail' => true],
                ['name' => 'Broodtwister Ovi\'nax', 'npc' => 214506, 'retail' => true],
                ['name' => 'Nexus-Princess Ky\'veza', 'npc' => 217748, 'retail' => true],
                ['name' => 'The Silken Court', 'npc' => 217491, 'retail' => true],
                ['name' => 'Queen Ansurek', 'npc' => 218370, 'retail' => true],
            ],
        ],
        'DF' => [
            'Vault of the Incarnates' => [
                ['name' => 'Eranog', 'npc' => 184972, 'retail' => true],
                ['name' => 'Terros', 'npc' => 190496, 'retail' => true],
                ['name' => 'The Primal Council', 'npc' => 187768, 'retail' => true],
                ['name' => 'Sennarth', 'npc' => 187967, 'retail' => true],
                ['name' => 'Dathea', 'npc' => 189813, 'retail' => true],
                ['name' => 'Kurog Grimtotem', 'npc' => 181378, 'retail' => true],
                ['name' => 'Broodkeeper Diurna', 'npc' => 190245, 'retail' => true],
                ['name' => 'Raszageth the Storm-Eater', 'npc' => 199031, 'retail' => true],
            ],
            'Aberrus, the Shadowed Crucible' => [
                ['name' => 'Kazzara', 'npc' => 201261, 'retail' => true],
                ['name' => 'The Amalgamation Chamber', 'npc' => 201774, 'retail' => true],
                ['name' => 'The Forgotten Experiments', 'npc' => 200912, 'retail' => true],
                ['name' => 'Assault of the Zaqali', 'npc' => 199659, 'retail' => true],
                ['name' => 'Rashok', 'npc' => 201320, 'retail' => true],
                ['name' => 'Zskarn', 'npc' => 202637, 'retail' => true],
                ['name' => 'Magmorax', 'npc' => 201579, 'retail' => true],
                ['name' => 'Echo of Neltharion', 'npc' => 201668, 'retail' => true],
                ['name' => 'Scalecommander Sarkareth', 'npc' => 201754, 'retail' => true],
            ],
            'Amirdrassil, the Dream\'s Hope' => [
                ['name' => 'Gnarlroot', 'npc' => 209333, 'retail' => true],
                ['name' => 'Igira the Cruel', 'npc' => 200926, 'retail' => true],
                ['name' => 'Volcoross', 'npc' => 208478, 'retail' => true],
                ['name' => 'Council of Dreams', 'npc' => 208363, 'retail' => true],
                ['name' => 'Larodar', 'npc' => 208445, 'retail' => true],
                ['name' => 'Nymue', 'npc' => 206172, 'retail' => true],
                ['name' => 'Smolderon', 'npc' => 200927, 'retail' => true],
                ['name' => 'Tindral Sageswift', 'npc' => 209090, 'retail' => true],
                ['name' => 'Fyrakk the Blazing', 'npc' => 204931, 'retail' => true],
            ],
        ],
    ];

    protected function configure(): void
    {
        $this
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Output format (json|typescript)', 'json')
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Output file path');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $format = $input->getOption('format');
        $outputFile = $input->getOption('output');

        $io->title('WoW Raids and Bosses Data Generator');

        $structuredData = $this->structureData();

        $io->section('Statistics');
        $io->table(
            ['Expansion', 'Raids', 'Bosses'],
            $this->getStats($structuredData)
        );

        if ($format === 'typescript') {
            $content = $this->generateTypeScript($structuredData);
            $defaultPath = dirname(__DIR__, 3) . '/frontend/src/data/raidData.ts';
        } else {
            $content = json_encode($structuredData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $defaultPath = dirname(__DIR__, 2) . '/var/raid_data.json';
        }

        $finalPath = $outputFile ?? $defaultPath;

        if (file_put_contents($finalPath, $content)) {
            $io->success("Data written to: $finalPath");
        } else {
            $io->error("Failed to write to: $finalPath");
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }

    private function structureData(): array
    {
        $result = [];

        foreach (self::RAID_DATA as $expansion => $raids) {
            foreach ($raids as $raidName => $bosses) {
                foreach ($bosses as $boss) {
                    $isRetail = $boss['retail'] ?? false;
                    $wowheadUrl = $isRetail
                        ? "https://www.wowhead.com/npc={$boss['npc']}"
                        : "https://www.wowhead.com/classic/npc={$boss['npc']}";

                    $result[] = [
                        'id' => $this->generateId($raidName, $boss['name']),
                        'name' => $boss['name'],
                        'raid' => $raidName,
                        'expansion' => $expansion,
                        'wowheadUrl' => $wowheadUrl,
                        'npcId' => $boss['npc'],
                    ];
                }
            }
        }

        return $result;
    }

    private function generateId(string $raid, string $boss): string
    {
        $raidSlug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $raid));
        $bossSlug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $boss));
        return trim($raidSlug, '-') . '-' . trim($bossSlug, '-');
    }

    private function getStats(array $data): array
    {
        $stats = [];
        $byExpansion = [];

        foreach ($data as $boss) {
            $exp = $boss['expansion'];
            if (!isset($byExpansion[$exp])) {
                $byExpansion[$exp] = ['raids' => [], 'bosses' => 0];
            }
            $byExpansion[$exp]['raids'][$boss['raid']] = true;
            $byExpansion[$exp]['bosses']++;
        }

        foreach ($byExpansion as $exp => $data) {
            $stats[] = [
                $exp,
                count($data['raids']),
                $data['bosses'],
            ];
        }

        return $stats;
    }

    private function generateTypeScript(array $data): string
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return <<<TS


export interface RaidBoss {
  id: string;
  name: string;
  raid: string;
  expansion: 'Classic' | 'TBC' | 'WotLK' | 'Cata' | 'MoP' | 'WoD' | 'Legion' | 'BfA' | 'SL' | 'DF' | 'TWW';
  wowheadUrl: string;
  npcId: number;
}

export const RAID_BOSSES: RaidBoss[] = $json;

export function getBossesByExpansion(expansion: string): RaidBoss[] {
  return RAID_BOSSES.filter(boss => boss.expansion === expansion);
}

export function getBossesByRaid(raidName: string): RaidBoss[] {
  return RAID_BOSSES.filter(boss => boss.raid === raidName);
}

export function getRaidsByExpansion(expansion: string): string[] {
  const raids = new Set<string>();
  RAID_BOSSES.filter(boss => boss.expansion === expansion)
    .forEach(boss => raids.add(boss.raid));
  return Array.from(raids);
}

export function getAllExpansions(): string[] {
  return ['Classic', 'TBC', 'WotLK', 'Cata', 'MoP', 'WoD', 'Legion', 'BfA', 'SL', 'DF', 'TWW'];
}

export function getAllRaids(): string[] {
  const raids = new Set<string>();
  RAID_BOSSES.forEach(boss => raids.add(boss.raid));
  return Array.from(raids).sort();
}

TS;
    }
}
