

export interface RaidBoss {
  id: string;
  name: string;
  raid: string;
  expansion: 'Classic' | 'TBC' | 'WotLK' | 'Cata' | 'MoP' | 'WoD' | 'Legion' | 'BfA' | 'SL' | 'DF' | 'TWW';
  wowheadUrl: string;
  npcId: number;
}

export const RAID_BOSSES: RaidBoss[] = [
    {
        "id": "molten-core-lucifron",
        "name": "Lucifron",
        "raid": "Molten Core",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=12118",
        "npcId": 12118
    },
    {
        "id": "molten-core-magmadar",
        "name": "Magmadar",
        "raid": "Molten Core",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=11982",
        "npcId": 11982
    },
    {
        "id": "molten-core-gehennas",
        "name": "Gehennas",
        "raid": "Molten Core",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=12259",
        "npcId": 12259
    },
    {
        "id": "molten-core-garr",
        "name": "Garr",
        "raid": "Molten Core",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=12057",
        "npcId": 12057
    },
    {
        "id": "molten-core-baron-geddon",
        "name": "Baron Geddon",
        "raid": "Molten Core",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=12056",
        "npcId": 12056
    },
    {
        "id": "molten-core-shazzrah",
        "name": "Shazzrah",
        "raid": "Molten Core",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=12264",
        "npcId": 12264
    },
    {
        "id": "molten-core-sulfuron-harbinger",
        "name": "Sulfuron Harbinger",
        "raid": "Molten Core",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=12098",
        "npcId": 12098
    },
    {
        "id": "molten-core-golemagg-the-incinerator",
        "name": "Golemagg the Incinerator",
        "raid": "Molten Core",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=11988",
        "npcId": 11988
    },
    {
        "id": "molten-core-majordomo-executus",
        "name": "Majordomo Executus",
        "raid": "Molten Core",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=12018",
        "npcId": 12018
    },
    {
        "id": "molten-core-ragnaros",
        "name": "Ragnaros",
        "raid": "Molten Core",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=11502",
        "npcId": 11502
    },
    {
        "id": "blackwing-lair-razorgore-the-untamed",
        "name": "Razorgore the Untamed",
        "raid": "Blackwing Lair",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=12435",
        "npcId": 12435
    },
    {
        "id": "blackwing-lair-vaelastrasz-the-corrupt",
        "name": "Vaelastrasz the Corrupt",
        "raid": "Blackwing Lair",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=13020",
        "npcId": 13020
    },
    {
        "id": "blackwing-lair-broodlord-lashlayer",
        "name": "Broodlord Lashlayer",
        "raid": "Blackwing Lair",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=12017",
        "npcId": 12017
    },
    {
        "id": "blackwing-lair-firemaw",
        "name": "Firemaw",
        "raid": "Blackwing Lair",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=11983",
        "npcId": 11983
    },
    {
        "id": "blackwing-lair-ebonroc",
        "name": "Ebonroc",
        "raid": "Blackwing Lair",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=14601",
        "npcId": 14601
    },
    {
        "id": "blackwing-lair-flamegor",
        "name": "Flamegor",
        "raid": "Blackwing Lair",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=11981",
        "npcId": 11981
    },
    {
        "id": "blackwing-lair-chromaggus",
        "name": "Chromaggus",
        "raid": "Blackwing Lair",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=14020",
        "npcId": 14020
    },
    {
        "id": "blackwing-lair-nefarian",
        "name": "Nefarian",
        "raid": "Blackwing Lair",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=11583",
        "npcId": 11583
    },
    {
        "id": "ahn-qiraj-aq20-kurinnaxx",
        "name": "Kurinnaxx",
        "raid": "Ahn'Qiraj (AQ20)",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15348",
        "npcId": 15348
    },
    {
        "id": "ahn-qiraj-aq20-general-rajaxx",
        "name": "General Rajaxx",
        "raid": "Ahn'Qiraj (AQ20)",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15341",
        "npcId": 15341
    },
    {
        "id": "ahn-qiraj-aq20-moam",
        "name": "Moam",
        "raid": "Ahn'Qiraj (AQ20)",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15340",
        "npcId": 15340
    },
    {
        "id": "ahn-qiraj-aq20-buru-the-gorger",
        "name": "Buru the Gorger",
        "raid": "Ahn'Qiraj (AQ20)",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15370",
        "npcId": 15370
    },
    {
        "id": "ahn-qiraj-aq20-ayamiss-the-hunter",
        "name": "Ayamiss the Hunter",
        "raid": "Ahn'Qiraj (AQ20)",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15369",
        "npcId": 15369
    },
    {
        "id": "ahn-qiraj-aq20-ossirian-the-unscarred",
        "name": "Ossirian the Unscarred",
        "raid": "Ahn'Qiraj (AQ20)",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15339",
        "npcId": 15339
    },
    {
        "id": "ahn-qiraj-aq40-the-prophet-skeram",
        "name": "The Prophet Skeram",
        "raid": "Ahn'Qiraj (AQ40)",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15263",
        "npcId": 15263
    },
    {
        "id": "ahn-qiraj-aq40-silithid-royalty",
        "name": "Silithid Royalty",
        "raid": "Ahn'Qiraj (AQ40)",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15543",
        "npcId": 15543
    },
    {
        "id": "ahn-qiraj-aq40-battleguard-sartura",
        "name": "Battleguard Sartura",
        "raid": "Ahn'Qiraj (AQ40)",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15516",
        "npcId": 15516
    },
    {
        "id": "ahn-qiraj-aq40-fankriss-the-unyielding",
        "name": "Fankriss the Unyielding",
        "raid": "Ahn'Qiraj (AQ40)",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15510",
        "npcId": 15510
    },
    {
        "id": "ahn-qiraj-aq40-viscidus",
        "name": "Viscidus",
        "raid": "Ahn'Qiraj (AQ40)",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15299",
        "npcId": 15299
    },
    {
        "id": "ahn-qiraj-aq40-princess-huhuran",
        "name": "Princess Huhuran",
        "raid": "Ahn'Qiraj (AQ40)",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15509",
        "npcId": 15509
    },
    {
        "id": "ahn-qiraj-aq40-twin-emperors",
        "name": "Twin Emperors",
        "raid": "Ahn'Qiraj (AQ40)",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15276",
        "npcId": 15276
    },
    {
        "id": "ahn-qiraj-aq40-ouro",
        "name": "Ouro",
        "raid": "Ahn'Qiraj (AQ40)",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15517",
        "npcId": 15517
    },
    {
        "id": "ahn-qiraj-aq40-c-thun",
        "name": "C'Thun",
        "raid": "Ahn'Qiraj (AQ40)",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15727",
        "npcId": 15727
    },
    {
        "id": "naxxramas-anub-rekhan",
        "name": "Anub'Rekhan",
        "raid": "Naxxramas",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15956",
        "npcId": 15956
    },
    {
        "id": "naxxramas-grand-widow-faerlina",
        "name": "Grand Widow Faerlina",
        "raid": "Naxxramas",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15953",
        "npcId": 15953
    },
    {
        "id": "naxxramas-maexxna",
        "name": "Maexxna",
        "raid": "Naxxramas",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15952",
        "npcId": 15952
    },
    {
        "id": "naxxramas-noth-the-plaguebringer",
        "name": "Noth the Plaguebringer",
        "raid": "Naxxramas",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15954",
        "npcId": 15954
    },
    {
        "id": "naxxramas-heigan-the-unclean",
        "name": "Heigan the Unclean",
        "raid": "Naxxramas",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15936",
        "npcId": 15936
    },
    {
        "id": "naxxramas-loatheb",
        "name": "Loatheb",
        "raid": "Naxxramas",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=16011",
        "npcId": 16011
    },
    {
        "id": "naxxramas-instructor-razuvious",
        "name": "Instructor Razuvious",
        "raid": "Naxxramas",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=16061",
        "npcId": 16061
    },
    {
        "id": "naxxramas-gothik-the-harvester",
        "name": "Gothik the Harvester",
        "raid": "Naxxramas",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=16060",
        "npcId": 16060
    },
    {
        "id": "naxxramas-the-four-horsemen",
        "name": "The Four Horsemen",
        "raid": "Naxxramas",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=16064",
        "npcId": 16064
    },
    {
        "id": "naxxramas-patchwerk",
        "name": "Patchwerk",
        "raid": "Naxxramas",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=16028",
        "npcId": 16028
    },
    {
        "id": "naxxramas-grobbulus",
        "name": "Grobbulus",
        "raid": "Naxxramas",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15931",
        "npcId": 15931
    },
    {
        "id": "naxxramas-gluth",
        "name": "Gluth",
        "raid": "Naxxramas",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15932",
        "npcId": 15932
    },
    {
        "id": "naxxramas-thaddius",
        "name": "Thaddius",
        "raid": "Naxxramas",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15928",
        "npcId": 15928
    },
    {
        "id": "naxxramas-sapphiron",
        "name": "Sapphiron",
        "raid": "Naxxramas",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15989",
        "npcId": 15989
    },
    {
        "id": "naxxramas-kel-thuzad",
        "name": "Kel'Thuzad",
        "raid": "Naxxramas",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15990",
        "npcId": 15990
    },
    {
        "id": "zul-gurub-high-priestess-jeklik",
        "name": "High Priestess Jeklik",
        "raid": "Zul'Gurub",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=14517",
        "npcId": 14517
    },
    {
        "id": "zul-gurub-high-priest-venoxis",
        "name": "High Priest Venoxis",
        "raid": "Zul'Gurub",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=14507",
        "npcId": 14507
    },
    {
        "id": "zul-gurub-high-priestess-mar-li",
        "name": "High Priestess Mar'li",
        "raid": "Zul'Gurub",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=14510",
        "npcId": 14510
    },
    {
        "id": "zul-gurub-bloodlord-mandokir",
        "name": "Bloodlord Mandokir",
        "raid": "Zul'Gurub",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=11382",
        "npcId": 11382
    },
    {
        "id": "zul-gurub-edge-of-madness",
        "name": "Edge of Madness",
        "raid": "Zul'Gurub",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=14988",
        "npcId": 14988
    },
    {
        "id": "zul-gurub-high-priest-thekal",
        "name": "High Priest Thekal",
        "raid": "Zul'Gurub",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=14509",
        "npcId": 14509
    },
    {
        "id": "zul-gurub-high-priestess-arlokk",
        "name": "High Priestess Arlokk",
        "raid": "Zul'Gurub",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=14515",
        "npcId": 14515
    },
    {
        "id": "zul-gurub-jin-do-the-hexxer",
        "name": "Jin'do the Hexxer",
        "raid": "Zul'Gurub",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=11380",
        "npcId": 11380
    },
    {
        "id": "zul-gurub-hakkar",
        "name": "Hakkar",
        "raid": "Zul'Gurub",
        "expansion": "Classic",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=14834",
        "npcId": 14834
    },
    {
        "id": "karazhan-attumen-the-huntsman",
        "name": "Attumen the Huntsman",
        "raid": "Karazhan",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=16151",
        "npcId": 16151
    },
    {
        "id": "karazhan-moroes",
        "name": "Moroes",
        "raid": "Karazhan",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15687",
        "npcId": 15687
    },
    {
        "id": "karazhan-maiden-of-virtue",
        "name": "Maiden of Virtue",
        "raid": "Karazhan",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=16457",
        "npcId": 16457
    },
    {
        "id": "karazhan-opera-event",
        "name": "Opera Event",
        "raid": "Karazhan",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=17534",
        "npcId": 17534
    },
    {
        "id": "karazhan-the-curator",
        "name": "The Curator",
        "raid": "Karazhan",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15691",
        "npcId": 15691
    },
    {
        "id": "karazhan-terestian-illhoof",
        "name": "Terestian Illhoof",
        "raid": "Karazhan",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15688",
        "npcId": 15688
    },
    {
        "id": "karazhan-shade-of-aran",
        "name": "Shade of Aran",
        "raid": "Karazhan",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=16524",
        "npcId": 16524
    },
    {
        "id": "karazhan-netherspite",
        "name": "Netherspite",
        "raid": "Karazhan",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15689",
        "npcId": 15689
    },
    {
        "id": "karazhan-chess-event",
        "name": "Chess Event",
        "raid": "Karazhan",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=16816",
        "npcId": 16816
    },
    {
        "id": "karazhan-prince-malchezaar",
        "name": "Prince Malchezaar",
        "raid": "Karazhan",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15690",
        "npcId": 15690
    },
    {
        "id": "gruul-s-lair-high-king-maulgar",
        "name": "High King Maulgar",
        "raid": "Gruul's Lair",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=18831",
        "npcId": 18831
    },
    {
        "id": "gruul-s-lair-gruul-the-dragonkiller",
        "name": "Gruul the Dragonkiller",
        "raid": "Gruul's Lair",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=19044",
        "npcId": 19044
    },
    {
        "id": "magtheridon-s-lair-magtheridon",
        "name": "Magtheridon",
        "raid": "Magtheridon's Lair",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=17257",
        "npcId": 17257
    },
    {
        "id": "serpentshrine-cavern-hydross-the-unstable",
        "name": "Hydross the Unstable",
        "raid": "Serpentshrine Cavern",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=21216",
        "npcId": 21216
    },
    {
        "id": "serpentshrine-cavern-the-lurker-below",
        "name": "The Lurker Below",
        "raid": "Serpentshrine Cavern",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=21217",
        "npcId": 21217
    },
    {
        "id": "serpentshrine-cavern-leotheras-the-blind",
        "name": "Leotheras the Blind",
        "raid": "Serpentshrine Cavern",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=21215",
        "npcId": 21215
    },
    {
        "id": "serpentshrine-cavern-fathom-lord-karathress",
        "name": "Fathom-Lord Karathress",
        "raid": "Serpentshrine Cavern",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=21214",
        "npcId": 21214
    },
    {
        "id": "serpentshrine-cavern-morogrim-tidewalker",
        "name": "Morogrim Tidewalker",
        "raid": "Serpentshrine Cavern",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=21213",
        "npcId": 21213
    },
    {
        "id": "serpentshrine-cavern-lady-vashj",
        "name": "Lady Vashj",
        "raid": "Serpentshrine Cavern",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=21212",
        "npcId": 21212
    },
    {
        "id": "tempest-keep-al-ar",
        "name": "Al'ar",
        "raid": "Tempest Keep",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=19514",
        "npcId": 19514
    },
    {
        "id": "tempest-keep-void-reaver",
        "name": "Void Reaver",
        "raid": "Tempest Keep",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=19516",
        "npcId": 19516
    },
    {
        "id": "tempest-keep-high-astromancer-solarian",
        "name": "High Astromancer Solarian",
        "raid": "Tempest Keep",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=18805",
        "npcId": 18805
    },
    {
        "id": "tempest-keep-kael-thas-sunstrider",
        "name": "Kael'thas Sunstrider",
        "raid": "Tempest Keep",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=19622",
        "npcId": 19622
    },
    {
        "id": "black-temple-high-warlord-naj-entus",
        "name": "High Warlord Naj'entus",
        "raid": "Black Temple",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=22887",
        "npcId": 22887
    },
    {
        "id": "black-temple-supremus",
        "name": "Supremus",
        "raid": "Black Temple",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=22898",
        "npcId": 22898
    },
    {
        "id": "black-temple-shade-of-akama",
        "name": "Shade of Akama",
        "raid": "Black Temple",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=22841",
        "npcId": 22841
    },
    {
        "id": "black-temple-teron-gorefiend",
        "name": "Teron Gorefiend",
        "raid": "Black Temple",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=22871",
        "npcId": 22871
    },
    {
        "id": "black-temple-gurtogg-bloodboil",
        "name": "Gurtogg Bloodboil",
        "raid": "Black Temple",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=22948",
        "npcId": 22948
    },
    {
        "id": "black-temple-reliquary-of-souls",
        "name": "Reliquary of Souls",
        "raid": "Black Temple",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=22856",
        "npcId": 22856
    },
    {
        "id": "black-temple-mother-shahraz",
        "name": "Mother Shahraz",
        "raid": "Black Temple",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=22947",
        "npcId": 22947
    },
    {
        "id": "black-temple-the-illidari-council",
        "name": "The Illidari Council",
        "raid": "Black Temple",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=23426",
        "npcId": 23426
    },
    {
        "id": "black-temple-illidan-stormrage",
        "name": "Illidan Stormrage",
        "raid": "Black Temple",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=22917",
        "npcId": 22917
    },
    {
        "id": "sunwell-plateau-kalecgos",
        "name": "Kalecgos",
        "raid": "Sunwell Plateau",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=24850",
        "npcId": 24850
    },
    {
        "id": "sunwell-plateau-brutallus",
        "name": "Brutallus",
        "raid": "Sunwell Plateau",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=24882",
        "npcId": 24882
    },
    {
        "id": "sunwell-plateau-felmyst",
        "name": "Felmyst",
        "raid": "Sunwell Plateau",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=25038",
        "npcId": 25038
    },
    {
        "id": "sunwell-plateau-eredar-twins",
        "name": "Eredar Twins",
        "raid": "Sunwell Plateau",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=25166",
        "npcId": 25166
    },
    {
        "id": "sunwell-plateau-m-uru",
        "name": "M'uru",
        "raid": "Sunwell Plateau",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=25741",
        "npcId": 25741
    },
    {
        "id": "sunwell-plateau-kil-jaeden",
        "name": "Kil'jaeden",
        "raid": "Sunwell Plateau",
        "expansion": "TBC",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=25315",
        "npcId": 25315
    },
    {
        "id": "naxxramas-anub-rekhan",
        "name": "Anub'Rekhan",
        "raid": "Naxxramas",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15956",
        "npcId": 15956
    },
    {
        "id": "naxxramas-grand-widow-faerlina",
        "name": "Grand Widow Faerlina",
        "raid": "Naxxramas",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15953",
        "npcId": 15953
    },
    {
        "id": "naxxramas-maexxna",
        "name": "Maexxna",
        "raid": "Naxxramas",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15952",
        "npcId": 15952
    },
    {
        "id": "naxxramas-noth-the-plaguebringer",
        "name": "Noth the Plaguebringer",
        "raid": "Naxxramas",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15954",
        "npcId": 15954
    },
    {
        "id": "naxxramas-heigan-the-unclean",
        "name": "Heigan the Unclean",
        "raid": "Naxxramas",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15936",
        "npcId": 15936
    },
    {
        "id": "naxxramas-loatheb",
        "name": "Loatheb",
        "raid": "Naxxramas",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=16011",
        "npcId": 16011
    },
    {
        "id": "naxxramas-instructor-razuvious",
        "name": "Instructor Razuvious",
        "raid": "Naxxramas",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=16061",
        "npcId": 16061
    },
    {
        "id": "naxxramas-gothik-the-harvester",
        "name": "Gothik the Harvester",
        "raid": "Naxxramas",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=16060",
        "npcId": 16060
    },
    {
        "id": "naxxramas-the-four-horsemen",
        "name": "The Four Horsemen",
        "raid": "Naxxramas",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=16064",
        "npcId": 16064
    },
    {
        "id": "naxxramas-patchwerk",
        "name": "Patchwerk",
        "raid": "Naxxramas",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=16028",
        "npcId": 16028
    },
    {
        "id": "naxxramas-grobbulus",
        "name": "Grobbulus",
        "raid": "Naxxramas",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15931",
        "npcId": 15931
    },
    {
        "id": "naxxramas-gluth",
        "name": "Gluth",
        "raid": "Naxxramas",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15932",
        "npcId": 15932
    },
    {
        "id": "naxxramas-thaddius",
        "name": "Thaddius",
        "raid": "Naxxramas",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15928",
        "npcId": 15928
    },
    {
        "id": "naxxramas-sapphiron",
        "name": "Sapphiron",
        "raid": "Naxxramas",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15989",
        "npcId": 15989
    },
    {
        "id": "naxxramas-kel-thuzad",
        "name": "Kel'Thuzad",
        "raid": "Naxxramas",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=15990",
        "npcId": 15990
    },
    {
        "id": "ulduar-flame-leviathan",
        "name": "Flame Leviathan",
        "raid": "Ulduar",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=33113",
        "npcId": 33113
    },
    {
        "id": "ulduar-ignis-the-furnace-master",
        "name": "Ignis the Furnace Master",
        "raid": "Ulduar",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=33118",
        "npcId": 33118
    },
    {
        "id": "ulduar-razorscale",
        "name": "Razorscale",
        "raid": "Ulduar",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=33186",
        "npcId": 33186
    },
    {
        "id": "ulduar-xt-002-deconstructor",
        "name": "XT-002 Deconstructor",
        "raid": "Ulduar",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=33293",
        "npcId": 33293
    },
    {
        "id": "ulduar-assembly-of-iron",
        "name": "Assembly of Iron",
        "raid": "Ulduar",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=32867",
        "npcId": 32867
    },
    {
        "id": "ulduar-kologarn",
        "name": "Kologarn",
        "raid": "Ulduar",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=32930",
        "npcId": 32930
    },
    {
        "id": "ulduar-auriaya",
        "name": "Auriaya",
        "raid": "Ulduar",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=33515",
        "npcId": 33515
    },
    {
        "id": "ulduar-hodir",
        "name": "Hodir",
        "raid": "Ulduar",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=32845",
        "npcId": 32845
    },
    {
        "id": "ulduar-thorim",
        "name": "Thorim",
        "raid": "Ulduar",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=32865",
        "npcId": 32865
    },
    {
        "id": "ulduar-freya",
        "name": "Freya",
        "raid": "Ulduar",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=32906",
        "npcId": 32906
    },
    {
        "id": "ulduar-mimiron",
        "name": "Mimiron",
        "raid": "Ulduar",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=33350",
        "npcId": 33350
    },
    {
        "id": "ulduar-general-vezax",
        "name": "General Vezax",
        "raid": "Ulduar",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=33271",
        "npcId": 33271
    },
    {
        "id": "ulduar-yogg-saron",
        "name": "Yogg-Saron",
        "raid": "Ulduar",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=33288",
        "npcId": 33288
    },
    {
        "id": "ulduar-algalon-the-observer",
        "name": "Algalon the Observer",
        "raid": "Ulduar",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=32871",
        "npcId": 32871
    },
    {
        "id": "trial-of-the-crusader-northrend-beasts",
        "name": "Northrend Beasts",
        "raid": "Trial of the Crusader",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=34796",
        "npcId": 34796
    },
    {
        "id": "trial-of-the-crusader-lord-jaraxxus",
        "name": "Lord Jaraxxus",
        "raid": "Trial of the Crusader",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=34780",
        "npcId": 34780
    },
    {
        "id": "trial-of-the-crusader-faction-champions",
        "name": "Faction Champions",
        "raid": "Trial of the Crusader",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=34458",
        "npcId": 34458
    },
    {
        "id": "trial-of-the-crusader-twin-val-kyr",
        "name": "Twin Val'kyr",
        "raid": "Trial of the Crusader",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=34497",
        "npcId": 34497
    },
    {
        "id": "trial-of-the-crusader-anub-arak",
        "name": "Anub'arak",
        "raid": "Trial of the Crusader",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=34564",
        "npcId": 34564
    },
    {
        "id": "icecrown-citadel-lord-marrowgar",
        "name": "Lord Marrowgar",
        "raid": "Icecrown Citadel",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=36612",
        "npcId": 36612
    },
    {
        "id": "icecrown-citadel-lady-deathwhisper",
        "name": "Lady Deathwhisper",
        "raid": "Icecrown Citadel",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=36855",
        "npcId": 36855
    },
    {
        "id": "icecrown-citadel-gunship-battle",
        "name": "Gunship Battle",
        "raid": "Icecrown Citadel",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=37215",
        "npcId": 37215
    },
    {
        "id": "icecrown-citadel-deathbringer-saurfang",
        "name": "Deathbringer Saurfang",
        "raid": "Icecrown Citadel",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=37813",
        "npcId": 37813
    },
    {
        "id": "icecrown-citadel-festergut",
        "name": "Festergut",
        "raid": "Icecrown Citadel",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=36626",
        "npcId": 36626
    },
    {
        "id": "icecrown-citadel-rotface",
        "name": "Rotface",
        "raid": "Icecrown Citadel",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=36627",
        "npcId": 36627
    },
    {
        "id": "icecrown-citadel-professor-putricide",
        "name": "Professor Putricide",
        "raid": "Icecrown Citadel",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=36678",
        "npcId": 36678
    },
    {
        "id": "icecrown-citadel-blood-prince-council",
        "name": "Blood Prince Council",
        "raid": "Icecrown Citadel",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=37970",
        "npcId": 37970
    },
    {
        "id": "icecrown-citadel-blood-queen-lana-thel",
        "name": "Blood-Queen Lana'thel",
        "raid": "Icecrown Citadel",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=37955",
        "npcId": 37955
    },
    {
        "id": "icecrown-citadel-valithria-dreamwalker",
        "name": "Valithria Dreamwalker",
        "raid": "Icecrown Citadel",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=36789",
        "npcId": 36789
    },
    {
        "id": "icecrown-citadel-sindragosa",
        "name": "Sindragosa",
        "raid": "Icecrown Citadel",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=36853",
        "npcId": 36853
    },
    {
        "id": "icecrown-citadel-the-lich-king",
        "name": "The Lich King",
        "raid": "Icecrown Citadel",
        "expansion": "WotLK",
        "wowheadUrl": "https://www.wowhead.com/classic/npc=36597",
        "npcId": 36597
    },
    {
        "id": "nerub-ar-palace-ulgrax-the-devourer",
        "name": "Ulgrax the Devourer",
        "raid": "Nerub-ar Palace",
        "expansion": "TWW",
        "wowheadUrl": "https://www.wowhead.com/npc=215657",
        "npcId": 215657
    },
    {
        "id": "nerub-ar-palace-the-bloodbound-horror",
        "name": "The Bloodbound Horror",
        "raid": "Nerub-ar Palace",
        "expansion": "TWW",
        "wowheadUrl": "https://www.wowhead.com/npc=214502",
        "npcId": 214502
    },
    {
        "id": "nerub-ar-palace-sikran",
        "name": "Sikran",
        "raid": "Nerub-ar Palace",
        "expansion": "TWW",
        "wowheadUrl": "https://www.wowhead.com/npc=214503",
        "npcId": 214503
    },
    {
        "id": "nerub-ar-palace-rasha-nan",
        "name": "Rasha'nan",
        "raid": "Nerub-ar Palace",
        "expansion": "TWW",
        "wowheadUrl": "https://www.wowhead.com/npc=214504",
        "npcId": 214504
    },
    {
        "id": "nerub-ar-palace-broodtwister-ovi-nax",
        "name": "Broodtwister Ovi'nax",
        "raid": "Nerub-ar Palace",
        "expansion": "TWW",
        "wowheadUrl": "https://www.wowhead.com/npc=214506",
        "npcId": 214506
    },
    {
        "id": "nerub-ar-palace-nexus-princess-ky-veza",
        "name": "Nexus-Princess Ky'veza",
        "raid": "Nerub-ar Palace",
        "expansion": "TWW",
        "wowheadUrl": "https://www.wowhead.com/npc=217748",
        "npcId": 217748
    },
    {
        "id": "nerub-ar-palace-the-silken-court",
        "name": "The Silken Court",
        "raid": "Nerub-ar Palace",
        "expansion": "TWW",
        "wowheadUrl": "https://www.wowhead.com/npc=217491",
        "npcId": 217491
    },
    {
        "id": "nerub-ar-palace-queen-ansurek",
        "name": "Queen Ansurek",
        "raid": "Nerub-ar Palace",
        "expansion": "TWW",
        "wowheadUrl": "https://www.wowhead.com/npc=218370",
        "npcId": 218370
    },
    {
        "id": "vault-of-the-incarnates-eranog",
        "name": "Eranog",
        "raid": "Vault of the Incarnates",
        "expansion": "DF",
        "wowheadUrl": "https://www.wowhead.com/npc=184972",
        "npcId": 184972
    },
    {
        "id": "vault-of-the-incarnates-terros",
        "name": "Terros",
        "raid": "Vault of the Incarnates",
        "expansion": "DF",
        "wowheadUrl": "https://www.wowhead.com/npc=190496",
        "npcId": 190496
    },
    {
        "id": "vault-of-the-incarnates-the-primal-council",
        "name": "The Primal Council",
        "raid": "Vault of the Incarnates",
        "expansion": "DF",
        "wowheadUrl": "https://www.wowhead.com/npc=187768",
        "npcId": 187768
    },
    {
        "id": "vault-of-the-incarnates-sennarth",
        "name": "Sennarth",
        "raid": "Vault of the Incarnates",
        "expansion": "DF",
        "wowheadUrl": "https://www.wowhead.com/npc=187967",
        "npcId": 187967
    },
    {
        "id": "vault-of-the-incarnates-dathea",
        "name": "Dathea",
        "raid": "Vault of the Incarnates",
        "expansion": "DF",
        "wowheadUrl": "https://www.wowhead.com/npc=189813",
        "npcId": 189813
    },
    {
        "id": "vault-of-the-incarnates-kurog-grimtotem",
        "name": "Kurog Grimtotem",
        "raid": "Vault of the Incarnates",
        "expansion": "DF",
        "wowheadUrl": "https://www.wowhead.com/npc=181378",
        "npcId": 181378
    },
    {
        "id": "vault-of-the-incarnates-broodkeeper-diurna",
        "name": "Broodkeeper Diurna",
        "raid": "Vault of the Incarnates",
        "expansion": "DF",
        "wowheadUrl": "https://www.wowhead.com/npc=190245",
        "npcId": 190245
    },
    {
        "id": "vault-of-the-incarnates-raszageth-the-storm-eater",
        "name": "Raszageth the Storm-Eater",
        "raid": "Vault of the Incarnates",
        "expansion": "DF",
        "wowheadUrl": "https://www.wowhead.com/npc=199031",
        "npcId": 199031
    },
    {
        "id": "aberrus-the-shadowed-crucible-kazzara",
        "name": "Kazzara",
        "raid": "Aberrus, the Shadowed Crucible",
        "expansion": "DF",
        "wowheadUrl": "https://www.wowhead.com/npc=201261",
        "npcId": 201261
    },
    {
        "id": "aberrus-the-shadowed-crucible-the-amalgamation-chamber",
        "name": "The Amalgamation Chamber",
        "raid": "Aberrus, the Shadowed Crucible",
        "expansion": "DF",
        "wowheadUrl": "https://www.wowhead.com/npc=201774",
        "npcId": 201774
    },
    {
        "id": "aberrus-the-shadowed-crucible-the-forgotten-experiments",
        "name": "The Forgotten Experiments",
        "raid": "Aberrus, the Shadowed Crucible",
        "expansion": "DF",
        "wowheadUrl": "https://www.wowhead.com/npc=200912",
        "npcId": 200912
    },
    {
        "id": "aberrus-the-shadowed-crucible-assault-of-the-zaqali",
        "name": "Assault of the Zaqali",
        "raid": "Aberrus, the Shadowed Crucible",
        "expansion": "DF",
        "wowheadUrl": "https://www.wowhead.com/npc=199659",
        "npcId": 199659
    },
    {
        "id": "aberrus-the-shadowed-crucible-rashok",
        "name": "Rashok",
        "raid": "Aberrus, the Shadowed Crucible",
        "expansion": "DF",
        "wowheadUrl": "https://www.wowhead.com/npc=201320",
        "npcId": 201320
    },
    {
        "id": "aberrus-the-shadowed-crucible-zskarn",
        "name": "Zskarn",
        "raid": "Aberrus, the Shadowed Crucible",
        "expansion": "DF",
        "wowheadUrl": "https://www.wowhead.com/npc=202637",
        "npcId": 202637
    },
    {
        "id": "aberrus-the-shadowed-crucible-magmorax",
        "name": "Magmorax",
        "raid": "Aberrus, the Shadowed Crucible",
        "expansion": "DF",
        "wowheadUrl": "https://www.wowhead.com/npc=201579",
        "npcId": 201579
    },
    {
        "id": "aberrus-the-shadowed-crucible-echo-of-neltharion",
        "name": "Echo of Neltharion",
        "raid": "Aberrus, the Shadowed Crucible",
        "expansion": "DF",
        "wowheadUrl": "https://www.wowhead.com/npc=201668",
        "npcId": 201668
    },
    {
        "id": "aberrus-the-shadowed-crucible-scalecommander-sarkareth",
        "name": "Scalecommander Sarkareth",
        "raid": "Aberrus, the Shadowed Crucible",
        "expansion": "DF",
        "wowheadUrl": "https://www.wowhead.com/npc=201754",
        "npcId": 201754
    },
    {
        "id": "amirdrassil-the-dream-s-hope-gnarlroot",
        "name": "Gnarlroot",
        "raid": "Amirdrassil, the Dream's Hope",
        "expansion": "DF",
        "wowheadUrl": "https://www.wowhead.com/npc=209333",
        "npcId": 209333
    },
    {
        "id": "amirdrassil-the-dream-s-hope-igira-the-cruel",
        "name": "Igira the Cruel",
        "raid": "Amirdrassil, the Dream's Hope",
        "expansion": "DF",
        "wowheadUrl": "https://www.wowhead.com/npc=200926",
        "npcId": 200926
    },
    {
        "id": "amirdrassil-the-dream-s-hope-volcoross",
        "name": "Volcoross",
        "raid": "Amirdrassil, the Dream's Hope",
        "expansion": "DF",
        "wowheadUrl": "https://www.wowhead.com/npc=208478",
        "npcId": 208478
    },
    {
        "id": "amirdrassil-the-dream-s-hope-council-of-dreams",
        "name": "Council of Dreams",
        "raid": "Amirdrassil, the Dream's Hope",
        "expansion": "DF",
        "wowheadUrl": "https://www.wowhead.com/npc=208363",
        "npcId": 208363
    },
    {
        "id": "amirdrassil-the-dream-s-hope-larodar",
        "name": "Larodar",
        "raid": "Amirdrassil, the Dream's Hope",
        "expansion": "DF",
        "wowheadUrl": "https://www.wowhead.com/npc=208445",
        "npcId": 208445
    },
    {
        "id": "amirdrassil-the-dream-s-hope-nymue",
        "name": "Nymue",
        "raid": "Amirdrassil, the Dream's Hope",
        "expansion": "DF",
        "wowheadUrl": "https://www.wowhead.com/npc=206172",
        "npcId": 206172
    },
    {
        "id": "amirdrassil-the-dream-s-hope-smolderon",
        "name": "Smolderon",
        "raid": "Amirdrassil, the Dream's Hope",
        "expansion": "DF",
        "wowheadUrl": "https://www.wowhead.com/npc=200927",
        "npcId": 200927
    },
    {
        "id": "amirdrassil-the-dream-s-hope-tindral-sageswift",
        "name": "Tindral Sageswift",
        "raid": "Amirdrassil, the Dream's Hope",
        "expansion": "DF",
        "wowheadUrl": "https://www.wowhead.com/npc=209090",
        "npcId": 209090
    },
    {
        "id": "amirdrassil-the-dream-s-hope-fyrakk-the-blazing",
        "name": "Fyrakk the Blazing",
        "raid": "Amirdrassil, the Dream's Hope",
        "expansion": "DF",
        "wowheadUrl": "https://www.wowhead.com/npc=204931",
        "npcId": 204931
    }
];

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
