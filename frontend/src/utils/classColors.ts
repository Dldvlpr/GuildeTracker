export const BLIZZARD_CLASS_COLORS: Record<string, string> = {
  warrior: '#C79C6E',
  paladin: '#F58CBA',
  hunter: '#ABD473',
  rogue: '#FFF569',
  priest: '#FFFFFF',
  deathknight: '#C41F3B',
  shaman: '#0070DE',
  mage: '#69CCF0',
  warlock: '#9482C9',
  monk: '#00FF96',
  druid: '#FF7D0A',
  demonhunter: '#A330C9',
  evoker: '#33937F'
};

export function getClassColor(className?: string): string {
  if (!className) return '#94a3b8'; // slate-400 fallback
  const key = className.toLowerCase().replace(/\s+/g, '');
  return BLIZZARD_CLASS_COLORS[key] ?? '#94a3b8';
}
