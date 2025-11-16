<?php

namespace App\Command;

use App\Entity\WowBoss;
use App\Entity\WowRaid;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:import-wow-raids-db',
    description: 'Import WoW raids and bosses data into database',
)]
class ImportWowRaidsToDbCommand extends Command
{
    
    private const RAID_DATA = [
        'Classic' => [
            'Molten Core' => [
                'maxPlayers' => 40,
                'minLevel' => 60,
                'bosses' => [
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
            ],
            'Blackwing Lair' => [
                'maxPlayers' => 40,
                'minLevel' => 60,
                'bosses' => [
                    ['name' => 'Razorgore the Untamed', 'npc' => 12435],
                    ['name' => 'Vaelastrasz the Corrupt', 'npc' => 13020],
                    ['name' => 'Broodlord Lashlayer', 'npc' => 12017],
                    ['name' => 'Firemaw', 'npc' => 11983],
                    ['name' => 'Ebonroc', 'npc' => 14601],
                    ['name' => 'Flamegor', 'npc' => 11981],
                    ['name' => 'Chromaggus', 'npc' => 14020],
                    ['name' => 'Nefarian', 'npc' => 11583],
                ],
            ],
            'Ahn\'Qiraj (AQ20)' => [
                'maxPlayers' => 20,
                'minLevel' => 60,
                'bosses' => [
                    ['name' => 'Kurinnaxx', 'npc' => 15348],
                    ['name' => 'General Rajaxx', 'npc' => 15341],
                    ['name' => 'Moam', 'npc' => 15340],
                    ['name' => 'Buru the Gorger', 'npc' => 15370],
                    ['name' => 'Ayamiss the Hunter', 'npc' => 15369],
                    ['name' => 'Ossirian the Unscarred', 'npc' => 15339],
                ],
            ],
            'Ahn\'Qiraj (AQ40)' => [
                'maxPlayers' => 40,
                'minLevel' => 60,
                'bosses' => [
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
            ],
            'Naxxramas' => [
                'maxPlayers' => 40,
                'minLevel' => 60,
                'bosses' => [
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
            ],
            'Zul\'Gurub' => [
                'maxPlayers' => 20,
                'minLevel' => 60,
                'bosses' => [
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
        ],
        'TBC' => [
            'Karazhan' => [
                'maxPlayers' => 10,
                'minLevel' => 70,
                'bosses' => [
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
            ],
            'Gruul\'s Lair' => [
                'maxPlayers' => 25,
                'minLevel' => 70,
                'bosses' => [
                    ['name' => 'High King Maulgar', 'npc' => 18831],
                    ['name' => 'Gruul the Dragonkiller', 'npc' => 19044],
                ],
            ],
            'Magtheridon\'s Lair' => [
                'maxPlayers' => 25,
                'minLevel' => 70,
                'bosses' => [
                    ['name' => 'Magtheridon', 'npc' => 17257],
                ],
            ],
            'Serpentshrine Cavern' => [
                'maxPlayers' => 25,
                'minLevel' => 70,
                'bosses' => [
                    ['name' => 'Hydross the Unstable', 'npc' => 21216],
                    ['name' => 'The Lurker Below', 'npc' => 21217],
                    ['name' => 'Leotheras the Blind', 'npc' => 21215],
                    ['name' => 'Fathom-Lord Karathress', 'npc' => 21214],
                    ['name' => 'Morogrim Tidewalker', 'npc' => 21213],
                    ['name' => 'Lady Vashj', 'npc' => 21212],
                ],
            ],
            'Tempest Keep' => [
                'maxPlayers' => 25,
                'minLevel' => 70,
                'bosses' => [
                    ['name' => 'Al\'ar', 'npc' => 19514],
                    ['name' => 'Void Reaver', 'npc' => 19516],
                    ['name' => 'High Astromancer Solarian', 'npc' => 18805],
                    ['name' => 'Kael\'thas Sunstrider', 'npc' => 19622],
                ],
            ],
            'Black Temple' => [
                'maxPlayers' => 25,
                'minLevel' => 70,
                'bosses' => [
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
            ],
            'Sunwell Plateau' => [
                'maxPlayers' => 25,
                'minLevel' => 70,
                'bosses' => [
                    ['name' => 'Kalecgos', 'npc' => 24850],
                    ['name' => 'Brutallus', 'npc' => 24882],
                    ['name' => 'Felmyst', 'npc' => 25038],
                    ['name' => 'Eredar Twins', 'npc' => 25166],
                    ['name' => 'M\'uru', 'npc' => 25741],
                    ['name' => 'Kil\'jaeden', 'npc' => 25315],
                ],
            ],
        ],
        'WotLK' => [
            'Naxxramas' => [
                'maxPlayers' => 25,
                'minLevel' => 80,
                'bosses' => [
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
            ],
            'Ulduar' => [
                'maxPlayers' => 25,
                'minLevel' => 80,
                'bosses' => [
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
            ],
            'Trial of the Crusader' => [
                'maxPlayers' => 25,
                'minLevel' => 80,
                'bosses' => [
                    ['name' => 'Northrend Beasts', 'npc' => 34796],
                    ['name' => 'Lord Jaraxxus', 'npc' => 34780],
                    ['name' => 'Faction Champions', 'npc' => 34458],
                    ['name' => 'Twin Val\'kyr', 'npc' => 34497],
                    ['name' => 'Anub\'arak', 'npc' => 34564],
                ],
            ],
            'Icecrown Citadel' => [
                'maxPlayers' => 25,
                'minLevel' => 80,
                'bosses' => [
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
        ],
        'Cata' => [
            'Blackwing Descent' => [
                'maxPlayers' => 25,
                'minLevel' => 85,
                'bosses' => [
                    ['name' => 'Magmaw', 'npc' => 41570],
                    ['name' => 'Omnotron Defense System', 'npc' => 42166],
                    ['name' => 'Maloriak', 'npc' => 41378],
                    ['name' => 'Atramedes', 'npc' => 41442],
                    ['name' => 'Chimaeron', 'npc' => 43296],
                    ['name' => 'Nefarian', 'npc' => 41376],
                ],
            ],
            'The Bastion of Twilight' => [
                'maxPlayers' => 25,
                'minLevel' => 85,
                'bosses' => [
                    ['name' => 'Halfus Wyrmbreaker', 'npc' => 44600],
                    ['name' => 'Theralion and Valiona', 'npc' => 45993],
                    ['name' => 'Ascendant Council', 'npc' => 43735],
                    ['name' => 'Cho\'gall', 'npc' => 43324],
                    ['name' => 'Sinestra', 'npc' => 45213],
                ],
            ],
            'Throne of the Four Winds' => [
                'maxPlayers' => 25,
                'minLevel' => 85,
                'bosses' => [
                    ['name' => 'Conclave of Wind', 'npc' => 45870],
                    ['name' => 'Al\'Akir', 'npc' => 46753],
                ],
            ],
            'Firelands' => [
                'maxPlayers' => 25,
                'minLevel' => 85,
                'bosses' => [
                    ['name' => 'Beth\'tilac', 'npc' => 52498],
                    ['name' => 'Lord Rhyolith', 'npc' => 52558],
                    ['name' => 'Alysrazor', 'npc' => 52530],
                    ['name' => 'Shannox', 'npc' => 53691],
                    ['name' => 'Baleroc', 'npc' => 53494],
                    ['name' => 'Majordomo Staghelm', 'npc' => 52571],
                    ['name' => 'Ragnaros', 'npc' => 52409],
                ],
            ],
            'Dragon Soul' => [
                'maxPlayers' => 25,
                'minLevel' => 85,
                'bosses' => [
                    ['name' => 'Morchok', 'npc' => 55265],
                    ['name' => 'Warlord Zon\'ozz', 'npc' => 55308],
                    ['name' => 'Yor\'sahj the Unsleeping', 'npc' => 55312],
                    ['name' => 'Hagara the Stormbinder', 'npc' => 55689],
                    ['name' => 'Ultraxion', 'npc' => 55294],
                    ['name' => 'Warmaster Blackhorn', 'npc' => 56427],
                    ['name' => 'Spine of Deathwing', 'npc' => 53879],
                    ['name' => 'Madness of Deathwing', 'npc' => 56173],
                ],
            ],
            'Baradin Hold' => [
                'maxPlayers' => 25,
                'minLevel' => 85,
                'bosses' => [
                    ['name' => 'Argaloth', 'npc' => 47120],
                    ['name' => 'Occu\'thar', 'npc' => 52363],
                    ['name' => 'Alizabal', 'npc' => 55869],
                ],
            ],
        ],
        'MoP' => [
            'Mogu\'shan Vaults' => [
                'maxPlayers' => 25,
                'minLevel' => 90,
                'retail' => true,
                'bosses' => [
                    ['name' => 'The Stone Guard', 'npc' => 60089],
                    ['name' => 'Feng the Accursed', 'npc' => 60009],
                    ['name' => 'Gara\'jal the Spiritbinder', 'npc' => 60143],
                    ['name' => 'The Spirit Kings', 'npc' => 61421],
                    ['name' => 'Elegon', 'npc' => 60410],
                    ['name' => 'Will of the Emperor', 'npc' => 60400],
                ],
            ],
            'Heart of Fear' => [
                'maxPlayers' => 25,
                'minLevel' => 90,
                'retail' => true,
                'bosses' => [
                    ['name' => 'Imperial Vizier Zor\'lok', 'npc' => 62980],
                    ['name' => 'Blade Lord Ta\'yak', 'npc' => 62543],
                    ['name' => 'Garalon', 'npc' => 62164],
                    ['name' => 'Wind Lord Mel\'jarak', 'npc' => 62397],
                    ['name' => 'Amber-Shaper Un\'sok', 'npc' => 62511],
                    ['name' => 'Grand Empress Shek\'zeer', 'npc' => 62837],
                ],
            ],
            'Terrace of Endless Spring' => [
                'maxPlayers' => 25,
                'minLevel' => 90,
                'retail' => true,
                'bosses' => [
                    ['name' => 'Protectors of the Endless', 'npc' => 60583],
                    ['name' => 'Tsulong', 'npc' => 62442],
                    ['name' => 'Lei Shi', 'npc' => 62983],
                    ['name' => 'Sha of Fear', 'npc' => 60999],
                ],
            ],
            'Throne of Thunder' => [
                'maxPlayers' => 25,
                'minLevel' => 90,
                'retail' => true,
                'bosses' => [
                    ['name' => 'Jin\'rokh the Breaker', 'npc' => 69465],
                    ['name' => 'Horridon', 'npc' => 68476],
                    ['name' => 'Council of Elders', 'npc' => 69134],
                    ['name' => 'Tortos', 'npc' => 67977],
                    ['name' => 'Megaera', 'npc' => 68065],
                    ['name' => 'Ji-Kun', 'npc' => 68905],
                    ['name' => 'Durumu the Forgotten', 'npc' => 68036],
                    ['name' => 'Primordius', 'npc' => 69017],
                    ['name' => 'Dark Animus', 'npc' => 69427],
                    ['name' => 'Iron Qon', 'npc' => 68078],
                    ['name' => 'Twin Consorts', 'npc' => 68905],
                    ['name' => 'Lei Shen', 'npc' => 68397],
                    ['name' => 'Ra-den', 'npc' => 69473],
                ],
            ],
            'Siege of Orgrimmar' => [
                'maxPlayers' => 25,
                'minLevel' => 90,
                'retail' => true,
                'bosses' => [
                    ['name' => 'Immerseus', 'npc' => 71543],
                    ['name' => 'The Fallen Protectors', 'npc' => 71479],
                    ['name' => 'Norushen', 'npc' => 71967],
                    ['name' => 'Sha of Pride', 'npc' => 71734],
                    ['name' => 'Galakras', 'npc' => 72249],
                    ['name' => 'Iron Juggernaut', 'npc' => 71466],
                    ['name' => 'Kor\'kron Dark Shaman', 'npc' => 71859],
                    ['name' => 'General Nazgrim', 'npc' => 71515],
                    ['name' => 'Malkorok', 'npc' => 71454],
                    ['name' => 'Spoils of Pandaria', 'npc' => 71889],
                    ['name' => 'Thok the Bloodthirsty', 'npc' => 71529],
                    ['name' => 'Siegecrafter Blackfuse', 'npc' => 71504],
                    ['name' => 'Paragons of the Klaxxi', 'npc' => 71152],
                    ['name' => 'Garrosh Hellscream', 'npc' => 71865],
                ],
            ],
        ],
        'WoD' => [
            'Highmaul' => [
                'maxPlayers' => 20,
                'minLevel' => 100,
                'retail' => true,
                'bosses' => [
                    ['name' => 'Kargath Bladefist', 'npc' => 78714],
                    ['name' => 'The Butcher', 'npc' => 77404],
                    ['name' => 'Tectus', 'npc' => 78948],
                    ['name' => 'Brackenspore', 'npc' => 78491],
                    ['name' => 'Twin Ogron', 'npc' => 78238],
                    ['name' => 'Ko\'ragh', 'npc' => 79015],
                    ['name' => 'Imperator Mar\'gok', 'npc' => 77428],
                ],
            ],
            'Blackrock Foundry' => [
                'maxPlayers' => 20,
                'minLevel' => 100,
                'retail' => true,
                'bosses' => [
                    ['name' => 'Oregorger', 'npc' => 77182],
                    ['name' => 'Gruul', 'npc' => 76877],
                    ['name' => 'Blast Furnace', 'npc' => 76809],
                    ['name' => 'Hans\'gar and Franzok', 'npc' => 76973],
                    ['name' => 'Flamebender Ka\'graz', 'npc' => 76814],
                    ['name' => 'Kromog', 'npc' => 77692],
                    ['name' => 'Beastlord Darmac', 'npc' => 76865],
                    ['name' => 'Operator Thogar', 'npc' => 76906],
                    ['name' => 'The Iron Maidens', 'npc' => 77477],
                    ['name' => 'Blackhand', 'npc' => 77325],
                ],
            ],
            'Hellfire Citadel' => [
                'maxPlayers' => 20,
                'minLevel' => 100,
                'retail' => true,
                'bosses' => [
                    ['name' => 'Hellfire Assault', 'npc' => 93023],
                    ['name' => 'Iron Reaver', 'npc' => 90284],
                    ['name' => 'Kormrok', 'npc' => 90435],
                    ['name' => 'Hellfire High Council', 'npc' => 92146],
                    ['name' => 'Kilrogg Deadeye', 'npc' => 90378],
                    ['name' => 'Gorefiend', 'npc' => 90199],
                    ['name' => 'Shadow-Lord Iskar', 'npc' => 90316],
                    ['name' => 'Socrethar the Eternal', 'npc' => 92330],
                    ['name' => 'Fel Lord Zakuun', 'npc' => 89890],
                    ['name' => 'Xhul\'horac', 'npc' => 93068],
                    ['name' => 'Tyrant Velhari', 'npc' => 90269],
                    ['name' => 'Mannoroth', 'npc' => 91349],
                    ['name' => 'Archimonde', 'npc' => 91331],
                ],
            ],
        ],
        'Legion' => [
            'The Emerald Nightmare' => [
                'maxPlayers' => 20,
                'minLevel' => 110,
                'retail' => true,
                'bosses' => [
                    ['name' => 'Nythendra', 'npc' => 102672],
                    ['name' => 'Elerethe Renferal', 'npc' => 106087],
                    ['name' => 'Il\'gynoth', 'npc' => 105393],
                    ['name' => 'Ursoc', 'npc' => 100497],
                    ['name' => 'Dragons of Nightmare', 'npc' => 102679],
                    ['name' => 'Cenarius', 'npc' => 104636],
                    ['name' => 'Xavius', 'npc' => 103769],
                ],
            ],
            'The Nighthold' => [
                'maxPlayers' => 20,
                'minLevel' => 110,
                'retail' => true,
                'bosses' => [
                    ['name' => 'Skorpyron', 'npc' => 102263],
                    ['name' => 'Chronomatic Anomaly', 'npc' => 104415],
                    ['name' => 'Trilliax', 'npc' => 104288],
                    ['name' => 'Spellblade Aluriel', 'npc' => 107699],
                    ['name' => 'Tichondrius', 'npc' => 103685],
                    ['name' => 'Krosus', 'npc' => 101002],
                    ['name' => 'High Botanist Tel\'arn', 'npc' => 104528],
                    ['name' => 'Star Augur Etraeus', 'npc' => 103758],
                    ['name' => 'Elisande', 'npc' => 106643],
                    ['name' => 'Gul\'dan', 'npc' => 105503],
                ],
            ],
            'Tomb of Sargeras' => [
                'maxPlayers' => 20,
                'minLevel' => 110,
                'retail' => true,
                'bosses' => [
                    ['name' => 'Goroth', 'npc' => 115844],
                    ['name' => 'Demonic Inquisition', 'npc' => 116689],
                    ['name' => 'Harjatan', 'npc' => 116407],
                    ['name' => 'Mistress Sassz\'ine', 'npc' => 115767],
                    ['name' => 'Sisters of the Moon', 'npc' => 118523],
                    ['name' => 'The Desolate Host', 'npc' => 118460],
                    ['name' => 'Maiden of Vigilance', 'npc' => 118289],
                    ['name' => 'Fallen Avatar', 'npc' => 116939],
                    ['name' => 'Kil\'jaeden', 'npc' => 117269],
                ],
            ],
            'Antorus, the Burning Throne' => [
                'maxPlayers' => 20,
                'minLevel' => 110,
                'retail' => true,
                'bosses' => [
                    ['name' => 'Garothi Worldbreaker', 'npc' => 122450],
                    ['name' => 'Felhounds of Sargeras', 'npc' => 122477],
                    ['name' => 'Antoran High Command', 'npc' => 122367],
                    ['name' => 'Portal Keeper Hasabel', 'npc' => 122104],
                    ['name' => 'Eonar the Life-Binder', 'npc' => 122500],
                    ['name' => 'Imonar the Soulhunter', 'npc' => 124158],
                    ['name' => 'Kin\'garoth', 'npc' => 122578],
                    ['name' => 'Varimathras', 'npc' => 122366],
                    ['name' => 'The Coven of Shivarra', 'npc' => 122468],
                    ['name' => 'Aggramar', 'npc' => 121975],
                    ['name' => 'Argus the Unmaker', 'npc' => 124828],
                ],
            ],
        ],
        'BfA' => [
            'Uldir' => [
                'maxPlayers' => 20,
                'minLevel' => 120,
                'retail' => true,
                'bosses' => [
                    ['name' => 'Taloc', 'npc' => 137119],
                    ['name' => 'MOTHER', 'npc' => 135452],
                    ['name' => 'Fetid Devourer', 'npc' => 133298],
                    ['name' => 'Zek\'voz', 'npc' => 134445],
                    ['name' => 'Vectis', 'npc' => 134442],
                    ['name' => 'Zul, Reborn', 'npc' => 138967],
                    ['name' => 'Mythrax the Unraveler', 'npc' => 134546],
                    ['name' => 'G\'huun', 'npc' => 132998],
                ],
            ],
            'Battle of Dazar\'alor' => [
                'maxPlayers' => 20,
                'minLevel' => 120,
                'retail' => true,
                'bosses' => [
                    ['name' => 'Champion of the Light', 'npc' => 144680],
                    ['name' => 'Grong', 'npc' => 144637],
                    ['name' => 'Jadefire Masters', 'npc' => 144690],
                    ['name' => 'Opulence', 'npc' => 145261],
                    ['name' => 'Conclave of the Chosen', 'npc' => 144754],
                    ['name' => 'King Rastakhan', 'npc' => 145616],
                    ['name' => 'Mekkatorque', 'npc' => 144796],
                    ['name' => 'Stormwall Blockade', 'npc' => 146256],
                    ['name' => 'Jaina Proudmoore', 'npc' => 146409],
                ],
            ],
            'Crucible of Storms' => [
                'maxPlayers' => 20,
                'minLevel' => 120,
                'retail' => true,
                'bosses' => [
                    ['name' => 'The Restless Cabal', 'npc' => 146497],
                    ['name' => 'Uu\'nat', 'npc' => 145371],
                ],
            ],
            'The Eternal Palace' => [
                'maxPlayers' => 20,
                'minLevel' => 120,
                'retail' => true,
                'bosses' => [
                    ['name' => 'Abyssal Commander Sivara', 'npc' => 155434],
                    ['name' => 'Radiance of Azshara', 'npc' => 152364],
                    ['name' => 'Blackwater Behemoth', 'npc' => 150653],
                    ['name' => 'Lady Ashvane', 'npc' => 153142],
                    ['name' => 'Orgozoa', 'npc' => 152128],
                    ['name' => 'The Queen\'s Court', 'npc' => 152852],
                    ['name' => 'Za\'qul', 'npc' => 150859],
                    ['name' => 'Queen Azshara', 'npc' => 152910],
                ],
            ],
            'Ny\'alotha, the Waking City' => [
                'maxPlayers' => 20,
                'minLevel' => 120,
                'retail' => true,
                'bosses' => [
                    ['name' => 'Wrathion', 'npc' => 156523],
                    ['name' => 'Maut', 'npc' => 156523],
                    ['name' => 'The Prophet Skitra', 'npc' => 157602],
                    ['name' => 'Dark Inquisitor Xanesh', 'npc' => 156575],
                    ['name' => 'Vexiona', 'npc' => 157354],
                    ['name' => 'Ra-den the Despoiled', 'npc' => 156866],
                    ['name' => 'Shad\'har the Insatiable', 'npc' => 157231],
                    ['name' => 'Drest\'agath', 'npc' => 157602],
                    ['name' => 'Il\'gynoth, Corruption Reborn', 'npc' => 158041],
                    ['name' => 'Carapace of N\'Zoth', 'npc' => 157439],
                    ['name' => 'N\'Zoth the Corruptor', 'npc' => 158376],
                ],
            ],
        ],
        'SL' => [
            'Castle Nathria' => [
                'maxPlayers' => 20,
                'minLevel' => 60,
                'retail' => true,
                'bosses' => [
                    ['name' => 'Shriekwing', 'npc' => 164406],
                    ['name' => 'Huntsman Altimor', 'npc' => 165066],
                    ['name' => 'Sun King\'s Salvation', 'npc' => 168163],
                    ['name' => 'Artificer Xy\'mox', 'npc' => 166644],
                    ['name' => 'Hungering Destroyer', 'npc' => 164261],
                    ['name' => 'Lady Inerva Darkvein', 'npc' => 165521],
                    ['name' => 'The Council of Blood', 'npc' => 166969],
                    ['name' => 'Sludgefist', 'npc' => 164407],
                    ['name' => 'Stone Legion Generals', 'npc' => 168113],
                    ['name' => 'Sire Denathrius', 'npc' => 167406],
                ],
            ],
            'Sanctum of Domination' => [
                'maxPlayers' => 20,
                'minLevel' => 60,
                'retail' => true,
                'bosses' => [
                    ['name' => 'The Tarragrue', 'npc' => 175611],
                    ['name' => 'The Eye of the Jailer', 'npc' => 175729],
                    ['name' => 'The Nine', 'npc' => 175726],
                    ['name' => 'Remnant of Ner\'zhul', 'npc' => 176929],
                    ['name' => 'Soulrender Dormazain', 'npc' => 175727],
                    ['name' => 'Painsmith Raznal', 'npc' => 176523],
                    ['name' => 'Guardian of the First Ones', 'npc' => 175731],
                    ['name' => 'Fatescribe Roh-Kalo', 'npc' => 175730],
                    ['name' => 'Kel\'Thuzad', 'npc' => 175559],
                    ['name' => 'Sylvanas Windrunner', 'npc' => 175732],
                ],
            ],
            'Sepulcher of the First Ones' => [
                'maxPlayers' => 20,
                'minLevel' => 60,
                'retail' => true,
                'bosses' => [
                    ['name' => 'Vigilant Guardian', 'npc' => 180773],
                    ['name' => 'Skolex', 'npc' => 181395],
                    ['name' => 'Artificer Xy\'mox', 'npc' => 183501],
                    ['name' => 'Dausegne', 'npc' => 181224],
                    ['name' => 'Prototype Pantheon', 'npc' => 181549],
                    ['name' => 'Lihuvim', 'npc' => 182169],
                    ['name' => 'Halondrus', 'npc' => 180906],
                    ['name' => 'Anduin Wrynn', 'npc' => 181954],
                    ['name' => 'Lords of Dread', 'npc' => 181535],
                    ['name' => 'Rygelon', 'npc' => 182777],
                    ['name' => 'The Jailer', 'npc' => 180990],
                ],
            ],
        ],
        'TWW' => [
            'Nerub-ar Palace' => [
                'maxPlayers' => 20,
                'minLevel' => 80,
                'retail' => true,
                'bosses' => [
                    ['name' => 'Ulgrax the Devourer', 'npc' => 215657],
                    ['name' => 'The Bloodbound Horror', 'npc' => 214502],
                    ['name' => 'Sikran', 'npc' => 214503],
                    ['name' => 'Rasha\'nan', 'npc' => 214504],
                    ['name' => 'Broodtwister Ovi\'nax', 'npc' => 214506],
                    ['name' => 'Nexus-Princess Ky\'veza', 'npc' => 217748],
                    ['name' => 'The Silken Court', 'npc' => 217491],
                    ['name' => 'Queen Ansurek', 'npc' => 218370],
                ],
            ],
        ],
        'DF' => [
            'Vault of the Incarnates' => [
                'maxPlayers' => 20,
                'minLevel' => 70,
                'retail' => true,
                'bosses' => [
                    ['name' => 'Eranog', 'npc' => 184972],
                    ['name' => 'Terros', 'npc' => 190496],
                    ['name' => 'The Primal Council', 'npc' => 187768],
                    ['name' => 'Sennarth', 'npc' => 187967],
                    ['name' => 'Dathea', 'npc' => 189813],
                    ['name' => 'Kurog Grimtotem', 'npc' => 181378],
                    ['name' => 'Broodkeeper Diurna', 'npc' => 190245],
                    ['name' => 'Raszageth the Storm-Eater', 'npc' => 199031],
                ],
            ],
            'Aberrus, the Shadowed Crucible' => [
                'maxPlayers' => 20,
                'minLevel' => 70,
                'retail' => true,
                'bosses' => [
                    ['name' => 'Kazzara', 'npc' => 201261],
                    ['name' => 'The Amalgamation Chamber', 'npc' => 201774],
                    ['name' => 'The Forgotten Experiments', 'npc' => 200912],
                    ['name' => 'Assault of the Zaqali', 'npc' => 199659],
                    ['name' => 'Rashok', 'npc' => 201320],
                    ['name' => 'Zskarn', 'npc' => 202637],
                    ['name' => 'Magmorax', 'npc' => 201579],
                    ['name' => 'Echo of Neltharion', 'npc' => 201668],
                    ['name' => 'Scalecommander Sarkareth', 'npc' => 201754],
                ],
            ],
            'Amirdrassil, the Dream\'s Hope' => [
                'maxPlayers' => 20,
                'minLevel' => 70,
                'retail' => true,
                'bosses' => [
                    ['name' => 'Gnarlroot', 'npc' => 209333],
                    ['name' => 'Igira the Cruel', 'npc' => 200926],
                    ['name' => 'Volcoross', 'npc' => 208478],
                    ['name' => 'Council of Dreams', 'npc' => 208363],
                    ['name' => 'Larodar', 'npc' => 208445],
                    ['name' => 'Nymue', 'npc' => 206172],
                    ['name' => 'Smolderon', 'npc' => 200927],
                    ['name' => 'Tindral Sageswift', 'npc' => 209090],
                    ['name' => 'Fyrakk the Blazing', 'npc' => 204931],
                ],
            ],
        ],
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('truncate', null, InputOption::VALUE_NONE, 'Truncate existing data before import')
            ->addOption('expansion', null, InputOption::VALUE_OPTIONAL, 'Import only specific expansion (Classic, TBC, WotLK, DF, TWW)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $truncate = $input->getOption('truncate');
        $filterExpansion = $input->getOption('expansion');

        $io->title('🎮 Import WoW Raids & Bosses to Database');

        if ($truncate) {
            $io->section('🗑️  Truncating existing data...');
            $this->truncateTables();
            $io->success('Existing data cleared');
        }

        $stats = [
            'raids' => 0,
            'bosses' => 0,
            'skipped' => 0,
        ];

        foreach (self::RAID_DATA as $expansion => $raids) {

            if ($filterExpansion && $expansion !== $filterExpansion) {
                continue;
            }

            $io->section("📦 Processing expansion: $expansion");

            foreach ($raids as $raidName => $raidConfig) {
                $isRetail = $raidConfig['retail'] ?? false;
                $bosses = $raidConfig['bosses'] ?? [];
                $maxPlayers = $raidConfig['maxPlayers'] ?? null;
                $minLevel = $raidConfig['minLevel'] ?? null;

                $raidSlug = $this->generateSlug($expansion . '-' . $raidName);

                $existingRaid = $this->entityManager->getRepository(WowRaid::class)
                    ->findOneBy(['slug' => $raidSlug]);

                if ($existingRaid && !$truncate) {
                    $io->warning("Raid '$raidName' already exists, skipping...");
                    $stats['skipped']++;
                    continue;
                }

                $raid = new WowRaid();
                $raid->setName($raidName);
                $raid->setSlug($raidSlug);
                $raid->setExpansion($expansion);
                $raid->setMaxPlayers($maxPlayers);
                $raid->setMinLevel($minLevel);

                foreach ($bosses as $index => $bossData) {
                    $boss = new WowBoss();
                    $boss->setName($bossData['name']);

                    $boss->setSlug($this->generateSlug($expansion . '-' . $raidName . '-' . $bossData['name']));
                    $boss->setNpcId($bossData['npc']);
                    $boss->setOrderIndex($index + 1);

                    $wowheadUrl = $isRetail
                        ? "https://www.wowhead.com/npc={$bossData['npc']}"
                        : "https://www.wowhead.com/classic/npc={$bossData['npc']}";
                    $boss->setWowheadUrl($wowheadUrl);

                    $raid->addBoss($boss);
                    $stats['bosses']++;
                }

                $this->entityManager->persist($raid);
                $stats['raids']++;

                $bossCount = count($bosses);
                $io->text("✓ Imported: $raidName ($bossCount bosses)");
            }
        }

        $io->section('💾 Saving to database...');
        $this->entityManager->flush();

        $io->success('Import completed successfully!');
        $io->table(
            ['Metric', 'Count'],
            [
                ['Raids imported', $stats['raids']],
                ['Bosses imported', $stats['bosses']],
                ['Raids skipped', $stats['skipped']],
            ]
        );

        return Command::SUCCESS;
    }

    private function truncateTables(): void
    {
        $connection = $this->entityManager->getConnection();
        $platform = $connection->getDatabasePlatform();

        $connection->executeStatement('TRUNCATE TABLE wow_bosses CASCADE');
        $connection->executeStatement('TRUNCATE TABLE wow_raids CASCADE');

        $connection->executeStatement('ALTER SEQUENCE wow_bosses_id_seq RESTART WITH 1');
        $connection->executeStatement('ALTER SEQUENCE wow_raids_id_seq RESTART WITH 1');
    }

    private function generateSlug(string $text): string
    {
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $text));
        return trim($slug, '-');
    }
}
