

export interface BossData {
  id: string;
  name: string;
  raid: string;
  expansion: 'Classic' | 'TBC' | 'WotLK' | 'Cata' | 'MoP' | 'WoD' | 'Legion' | 'BfA' | 'SL' | 'DF' | 'TWW';
  difficulty?: string[];
  phases?: string[];
  wowheadUrl?: string;
  defaultPositions?: Array<{ label: string; accepts?: string[] | 'ANY' }>;
  defaultCooldowns?: Array<{ timestamp: string; phase?: string }>;
  notes?: string;
}

export const CLASSIC_RAIDS: BossData[] = [

  {
    id: 'mc-lucifron',
    name: 'Lucifron',
    raid: 'Molten Core',
    expansion: 'Classic',
    wowheadUrl: 'https://www.wowhead.com/classic/npc=12118/lucifron',
    defaultPositions: [
      { label: 'Main Tank', accepts: ['Tanks'] },
      { label: 'MC Tank 1', accepts: ['Tanks'] },
      { label: 'MC Tank 2', accepts: ['Tanks'] },
      { label: 'Melee Group', accepts: 'ANY' },
      { label: 'Ranged Spread', accepts: ['Ranged', 'Healers'] },
    ],
  },
  {
    id: 'mc-magmadar',
    name: 'Magmadar',
    raid: 'Molten Core',
    expansion: 'Classic',
    wowheadUrl: 'https://www.wowhead.com/classic/npc=11982/magmadar',
    defaultPositions: [
      { label: 'Main Tank', accepts: ['Tanks'] },
      { label: 'Melee DPS', accepts: ['Melee'] },
      { label: 'Ranged DPS', accepts: ['Ranged'] },
      { label: 'Healers', accepts: ['Healers'] },
    ],
  },
  {
    id: 'mc-gehennas',
    name: 'Gehennas',
    raid: 'Molten Core',
    expansion: 'Classic',
    wowheadUrl: 'https://www.wowhead.com/classic/npc=12259/gehennas',
  },
  {
    id: 'mc-garr',
    name: 'Garr',
    raid: 'Molten Core',
    expansion: 'Classic',
    wowheadUrl: 'https://www.wowhead.com/classic/npc=12057/garr',
  },
  {
    id: 'mc-baron-geddon',
    name: 'Baron Geddon',
    raid: 'Molten Core',
    expansion: 'Classic',
    wowheadUrl: 'https://www.wowhead.com/classic/npc=12056/baron-geddon',
  },
  {
    id: 'mc-shazzrah',
    name: 'Shazzrah',
    raid: 'Molten Core',
    expansion: 'Classic',
    wowheadUrl: 'https://www.wowhead.com/classic/npc=12264/shazzrah',
  },
  {
    id: 'mc-sulfuron',
    name: 'Sulfuron Harbinger',
    raid: 'Molten Core',
    expansion: 'Classic',
    wowheadUrl: 'https://www.wowhead.com/classic/npc=12098/sulfuron-harbinger',
  },
  {
    id: 'mc-golemagg',
    name: 'Golemagg the Incinerator',
    raid: 'Molten Core',
    expansion: 'Classic',
    wowheadUrl: 'https://www.wowhead.com/classic/npc=11988/golemagg-the-incinerator',
  },
  {
    id: 'mc-majordomo',
    name: 'Majordomo Executus',
    raid: 'Molten Core',
    expansion: 'Classic',
    wowheadUrl: 'https://www.wowhead.com/classic/npc=12018/majordomo-executus',
  },
  {
    id: 'mc-ragnaros',
    name: 'Ragnaros',
    raid: 'Molten Core',
    expansion: 'Classic',
    wowheadUrl: 'https://www.wowhead.com/classic/npc=11502/ragnaros',
    phases: ['Phase 1 - Melee', 'Sons of Flame', 'Phase 2 - Melee', 'Submerge'],
    defaultPositions: [
      { label: 'Main Tank', accepts: ['Tanks'] },
      { label: 'Son Tank 1', accepts: ['Tanks'] },
      { label: 'Son Tank 2', accepts: ['Tanks'] },
      { label: 'Melee Group', accepts: ['Melee'] },
      { label: 'Ranged Spread', accepts: ['Ranged', 'Healers'] },
    ],
    defaultCooldowns: [
      { timestamp: '0:00', phase: 'Pull' },
      { timestamp: '3:00', phase: 'Sons spawn' },
    ],
  },

  {
    id: 'bwl-razorgore',
    name: 'Razorgore the Untamed',
    raid: 'Blackwing Lair',
    expansion: 'Classic',
    wowheadUrl: 'https://www.wowhead.com/classic/npc=12435/razorgore-the-untamed',
    phases: ['Phase 1 - Control', 'Phase 2 - Burn'],
    defaultPositions: [
      { label: 'Razorgore Tank', accepts: ['Tanks'] },
      { label: 'Add Tank 1', accepts: ['Tanks'] },
      { label: 'Add Tank 2', accepts: ['Tanks'] },
      { label: 'Orb Clicker', accepts: 'ANY' },
      { label: 'DPS on Adds', accepts: 'ANY' },
    ],
  },
  {
    id: 'bwl-vaelastrasz',
    name: 'Vaelastrasz the Corrupt',
    raid: 'Blackwing Lair',
    expansion: 'Classic',
    wowheadUrl: 'https://www.wowhead.com/classic/npc=13020/vaelastrasz-the-corrupt',
    defaultCooldowns: [
      { timestamp: '0:00', phase: 'Pull' },
      { timestamp: '0:45', phase: 'First Burning Adrenaline' },
      { timestamp: '1:30', phase: 'Second BA' },
      { timestamp: '2:15', phase: 'Third BA' },
    ],
  },
  {
    id: 'bwl-broodlord',
    name: 'Broodlord Lashlayer',
    raid: 'Blackwing Lair',
    expansion: 'Classic',
    wowheadUrl: 'https://www.wowhead.com/classic/npc=12017/broodlord-lashlayer',
  },
  {
    id: 'bwl-firemaw',
    name: 'Firemaw',
    raid: 'Blackwing Lair',
    expansion: 'Classic',
    wowheadUrl: 'https://www.wowhead.com/classic/npc=11983/firemaw',
  },
  {
    id: 'bwl-ebonroc',
    name: 'Ebonroc',
    raid: 'Blackwing Lair',
    expansion: 'Classic',
    wowheadUrl: 'https://www.wowhead.com/classic/npc=14601/ebonroc',
  },
  {
    id: 'bwl-flamegor',
    name: 'Flamegor',
    raid: 'Blackwing Lair',
    expansion: 'Classic',
    wowheadUrl: 'https://www.wowhead.com/classic/npc=11981/flamegor',
  },
  {
    id: 'bwl-chromaggus',
    name: 'Chromaggus',
    raid: 'Blackwing Lair',
    expansion: 'Classic',
    wowheadUrl: 'https://www.wowhead.com/classic/npc=14020/chromaggus',
  },
  {
    id: 'bwl-nefarian',
    name: 'Nefarian',
    raid: 'Blackwing Lair',
    expansion: 'Classic',
    wowheadUrl: 'https://www.wowhead.com/classic/npc=11583/nefarian',
    phases: ['Phase 1 - Ground', 'Phase 2 - Adds', 'Phase 3 - Landing'],
    defaultPositions: [
      { label: 'Main Tank', accepts: ['Tanks'] },
      { label: 'Add Tanks', accepts: ['Tanks'] },
      { label: 'Melee Stack', accepts: ['Melee'] },
      { label: 'Ranged Spread', accepts: ['Ranged', 'Healers'] },
    ],
  },
];

export const RETAIL_RAIDS: BossData[] = [
  {
    id: 'voti-raszageth',
    name: 'Raszageth the Storm-Eater',
    raid: 'Vault of the Incarnates',
    expansion: 'DF',
    difficulty: ['LFR', 'Normal', 'Heroic', 'Mythic'],
    wowheadUrl: 'https://www.wowhead.com/npc=199031/raszageth-the-storm-eater',
    phases: ['Intermission 1', 'Phase 2', 'Intermission 2', 'Phase 3'],
  },
  {
    id: 'atsc-kyveza',
    name: 'Kyveza',
    raid: 'Nerub-ar Palace',
    expansion: 'TWW',
    difficulty: ['LFR', 'Normal', 'Heroic', 'Mythic'],
    wowheadUrl: 'https://www.wowhead.com/npc=217748/kyveza',
  },
];

export const ALL_BOSSES = [...CLASSIC_RAIDS, ...RETAIL_RAIDS];

export function getBossesByRaid(raidName: string): BossData[] {
  return ALL_BOSSES.filter((boss) => boss.raid === raidName);
}

export function getBossesByExpansion(expansion: string): BossData[] {
  return ALL_BOSSES.filter((boss) => boss.expansion === expansion);
}

export function getRaidNames(): string[] {
  return Array.from(new Set(ALL_BOSSES.map((boss) => boss.raid)));
}
