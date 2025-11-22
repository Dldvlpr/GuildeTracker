export type RaidBuff = {
  key: string;
  id: number;
  name: string;
  class?: string;
  category?: string;
  description?: string;
  expansions?: string[];
  iconFile?: string | null;
};

export const RAID_BUFFS: RaidBuff[] = [
  // HASTE / HEROISM-LIKE
  { key: 'bloodlust', id: 2825, name: 'Bloodlust', class: 'SHAMAN', category: 'haste_raid', iconFile: 'spell_nature_bloodlust', expansions: ['tbc','wotlk','cata','mop','wod','legion','bfa','sl','df','tww'] },
  { key: 'heroism', id: 32182, name: 'Heroism', class: 'SHAMAN', category: 'haste_raid', iconFile: 'ability_shaman_heroism', expansions: ['tbc','wotlk','cata','mop','wod','legion','bfa','sl','df','tww'] },
  { key: 'time_warp', id: 80353, name: 'Time Warp', class: 'MAGE', category: 'haste_raid', iconFile: 'ability_mage_timewarp', expansions: ['cata','mop','wod','legion','bfa','sl','df','tww'] },
  { key: 'fury_of_the_aspects', id: 390386, name: 'Fury of the Aspects', class: 'EVOKER', category: 'haste_raid', iconFile: 'ability_evoker_furyoftheaspects', expansions: ['df','tww'] },

  // PRIMARY STATS / STAMINA
  { key: 'arcane_intellect', id: 1459, name: 'Arcane Intellect', class: 'MAGE', category: 'primary_stats', iconFile: 'spell_holy_magicalsentry', expansions: ['classic','tbc','wotlk','cata','mop','wod','legion','bfa','sl','df','tww'] },
  { key: 'mark_of_the_wild', id: 1126, name: 'Mark of the Wild', class: 'DRUID', category: 'primary_stats', iconFile: 'spell_nature_regeneration', expansions: ['classic','tbc','wotlk','cata','mop','wod','sl','df','tww'] },
  { key: 'blessing_of_kings', id: 20217, name: 'Blessing of Kings', class: 'PALADIN', category: 'primary_stats', expansions: ['classic','tbc','wotlk','cata','mop','wod'] },
  { key: 'legacy_of_the_emperor', id: 115921, name: 'Legacy of the Emperor', class: 'MONK', category: 'primary_stats', expansions: ['mop','wod'] },
  { key: 'power_word_fortitude', id: 21562, name: 'Power Word: Fortitude', class: 'PRIEST', category: 'stamina', iconFile: 'spell_holy_wordfortitude', expansions: ['classic','tbc','wotlk','cata','mop','wod','legion','bfa','sl','df','tww'] },
  { key: 'commanding_shout', id: 469, name: 'Commanding Shout', class: 'WARRIOR', category: 'stamina', expansions: ['tbc','wotlk','cata','mop','wod','legion'] },
  { key: 'blood_pact', id: 6307, name: 'Blood Pact', class: 'WARLOCK', category: 'stamina', expansions: ['classic','tbc','wotlk','cata','mop'] },

  // ATTACK POWER / STR-AGI STYLE
  { key: 'battle_shout', id: 6673, name: 'Battle Shout', class: 'WARRIOR', category: 'attack_power', iconFile: 'ability_warrior_battleshout', expansions: ['classic','tbc','wotlk','cata','mop','wod','legion','bfa','sl','df','tww'] },
  { key: 'horn_of_winter', id: 57330, name: 'Horn of Winter', class: 'DEATHKNIGHT', category: 'attack_power', iconFile: 'inv_misc_horn_02', expansions: ['wotlk','cata','mop','wod','legion'] },
  { key: 'trueshot_aura', id: 19506, name: 'Trueshot Aura', class: 'HUNTER', category: 'attack_power', expansions: ['classic','tbc','wotlk','cata','mop'] },
  { key: 'blessing_of_might', id: 19740, name: 'Blessing of Might', class: 'PALADIN', category: 'attack_power', expansions: ['classic','tbc','wotlk','cata','mop','wod'] },

  // CRIT
  { key: 'leader_of_the_pack', id: 17007, name: 'Leader of the Pack', class: 'DRUID', category: 'crit', expansions: ['classic','tbc','wotlk','cata'] },
  { key: 'legacy_of_the_white_tiger', id: 116781, name: 'Legacy of the White Tiger', class: 'MONK', category: 'crit', expansions: ['mop','wod'] },

  // SPELLPOWER AURAS
  { key: 'demonic_pact', id: 48090, name: 'Demonic Pact', class: 'WARLOCK', category: 'spell_power', expansions: ['wotlk','cata','mop'] },

  // DAMAGE TAKEN DEBUFFS
  { key: 'chaos_brand', id: 255260, name: 'Chaos Brand', class: 'DEMONHUNTER', category: 'magic_vulnerability', iconFile: 'ability_demonhunter_empowerwards', expansions: ['bfa','sl','df','tww'] },
  { key: 'hunters_mark_raid', id: 257284, name: "Hunter's Mark", class: 'HUNTER', category: 'execute_phase_damage', iconFile: 'ability_hunter_markedfordeath', expansions: ['bfa','sl','df','tww'] },

  // PALADIN AURAS
  { key: 'devotion_aura', id: 465, name: 'Devotion Aura', class: 'PALADIN', category: 'raid_defensive', iconFile: 'spell_holy_devotionaura', expansions: ['classic','tbc','wotlk','cata','mop','wod','legion','bfa','sl','df','tww'] },

  // PRIEST SECONDARY BUFFS
  { key: 'divine_spirit', id: 14752, name: 'Divine Spirit', class: 'PRIEST', category: 'spirit', expansions: ['classic','tbc','wotlk'] },

  // ROGUE EXTERNALS
  { key: 'tricks_of_the_trade', id: 57934, name: 'Tricks of the Trade', class: 'ROGUE', category: 'external_damage_buff', iconFile: 'ability_rogue_tricksofthetrade', expansions: ['wotlk','cata','mop','wod','legion','bfa','sl','df'] },

  // WARRIOR RAID CD
  { key: 'rallying_cry', id: 97462, name: 'Rallying Cry', class: 'WARRIOR', category: 'raid_defensive', iconFile: 'ability_warrior_rallyingcry', expansions: ['cata','mop','wod','legion','bfa','sl','df','tww'] },

  // SHAMAN RAID TOTEMS (buffs/regen/def/CDs)
  { key: 'shaman_strength_of_earth_totem', id: 8075, name: 'Strength of Earth Totem', class: 'SHAMAN', category: 'physical_stats', description: 'Strength & Agility buff', expansions: ['classic','tbc','wotlk','cata'] },
  { key: 'shaman_grace_of_air_totem', id: 25359, name: 'Grace of Air Totem', class: 'SHAMAN', category: 'agility', description: 'Agility buff', expansions: ['classic','tbc','wotlk'] },
  { key: 'shaman_windfury_totem', id: 8512, name: 'Windfury Totem', class: 'SHAMAN', category: 'melee_haste', description: 'Haste melee / extra swings', iconFile: 'spell_nature_windfury', expansions: ['classic','tbc','wotlk','cata'] },
  { key: 'shaman_flametongue_totem', id: 8227, name: 'Flametongue Totem', class: 'SHAMAN', category: 'spell_power', description: 'Spell damage/heal buff', expansions: ['classic','tbc','wotlk','cata'] },
  { key: 'shaman_wrath_of_air_totem', id: 2895, name: 'Wrath of Air Totem', class: 'SHAMAN', category: 'spell_haste', description: 'Spell haste raid-wide', expansions: ['tbc','wotlk','cata'] },
  { key: 'shaman_stoneskin_totem', id: 8071, name: 'Stoneskin Totem', class: 'SHAMAN', category: 'physical_defense', description: 'Physical damage reduction / armor', expansions: ['classic','tbc','wotlk','cata'] },
  { key: 'shaman_mana_spring_totem', id: 10497, name: 'Mana Spring Totem', class: 'SHAMAN', category: 'mana_regen', description: 'Passive mana regen', expansions: ['classic','tbc','wotlk','cata'] },
  { key: 'shaman_mana_tide_totem', id: 16190, name: 'Mana Tide Totem', class: 'SHAMAN', category: 'mana_regen_cd', description: 'Raid mana regen CD', expansions: ['tbc','wotlk','cata','mop','wod','legion','bfa','sl','df','tww'] },
  { key: 'shaman_healing_stream_totem', id: 5394, name: 'Healing Stream Totem', class: 'SHAMAN', category: 'sustained_healing', description: 'Periodic healing', iconFile: 'inv_spear_04', expansions: ['classic','tbc','wotlk','cata','mop','wod','legion','bfa','sl','df','tww'] },
  { key: 'shaman_fire_resistance_totem', id: 8184, name: 'Fire Resistance Totem', class: 'SHAMAN', category: 'resistance_fire', description: 'Fire resistance', expansions: ['classic','tbc','wotlk'] },
  { key: 'shaman_frost_resistance_totem', id: 8181, name: 'Frost Resistance Totem', class: 'SHAMAN', category: 'resistance_frost', description: 'Frost resistance', expansions: ['classic','tbc','wotlk'] },
  { key: 'shaman_nature_resistance_totem', id: 10595, name: 'Nature Resistance Totem', class: 'SHAMAN', category: 'resistance_nature', description: 'Nature resistance', expansions: ['classic','tbc','wotlk'] },
  { key: 'shaman_elemental_resistance_totem', id: 8184, name: 'Elemental Resistance Totem', class: 'SHAMAN', category: 'resistance_elemental', description: 'Merged elemental resist', expansions: ['cata'] },
  { key: 'shaman_tremor_totem', id: 8143, name: 'Tremor Totem', class: 'SHAMAN', category: 'anti_fear', description: 'Fear/Charm/Sleep break', iconFile: 'spell_nature_tremortotem', expansions: ['classic','tbc','wotlk','cata','mop','wod','legion','bfa','sl','df','tww'] },
  { key: 'shaman_grounding_totem', id: 8177, name: 'Grounding Totem', class: 'SHAMAN', category: 'anti_spell', description: 'Absorbs/redirects hostile spell', expansions: ['classic','tbc','wotlk','cata','mop','wod','legion','bfa','sl','df','tww'] },
  { key: 'shaman_tranquil_air_totem', id: 25908, name: 'Tranquil Air Totem', class: 'SHAMAN', category: 'threat_reduction', description: 'Threat reduction', expansions: ['classic','tbc'] },
  { key: 'shaman_poison_cleansing_totem', id: 8166, name: 'Poison Cleansing Totem', class: 'SHAMAN', category: 'raid_cleansing', description: 'Removes poisons', expansions: ['classic','tbc'] },
  { key: 'shaman_cleansing_totem', id: 8170, name: 'Cleansing Totem', class: 'SHAMAN', category: 'raid_cleansing', description: 'Removes poisons+diseases', expansions: ['wotlk'] },
  { key: 'shaman_spirit_link_totem', id: 98008, name: 'Spirit Link Totem', class: 'SHAMAN', category: 'raid_defensive_cd', description: 'Damage reduction + HP redistribution', iconFile: 'spell_shaman_spiritlink', expansions: ['cata','mop','wod','legion','bfa','sl','df','tww'] },
  { key: 'shaman_healing_tide_totem', id: 108280, name: 'Healing Tide Totem', class: 'SHAMAN', category: 'raid_healing_cd', description: 'Massive raid heal CD', iconFile: 'ability_shaman_healingtide', expansions: ['mop','wod','legion','bfa','sl','df','tww'] },
  { key: 'shaman_stormlash_totem', id: 120668, name: 'Stormlash Totem', class: 'SHAMAN', category: 'raid_damage_cd', description: 'Raid damage CD (Nature procs)', expansions: ['mop','wod','legion','bfa','sl','df','tww'] },
  { key: 'shaman_capacitor_totem', id: 108269, name: 'Capacitor Totem', class: 'SHAMAN', category: 'raid_cc', description: 'AoE stun after delay', expansions: ['mop','wod','legion','bfa','sl','df','tww'] },
];
