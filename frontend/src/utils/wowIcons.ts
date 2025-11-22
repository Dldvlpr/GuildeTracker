const DEFAULT_ICON = 'inv_misc_questionmark';

function normalizeIcon(iconFile?: string | null): string {
  const trimmed = (iconFile ?? '').trim();
  if (!trimmed) return DEFAULT_ICON;
  return trimmed.replace(/\.jpg$/i, '').replace(/\.png$/i, '');
}

/**
 * Construit l’URL CDN Blizzard à partir d’un icon_file stocké en base.
 * Aucun token / aucun call API côté front.
 *
 * @param iconFile valeur `icon_file` (ex: "spell_holy_powerwordshield")
 * @param size taille du pictogramme (32, 56, 64…)
 */
export function spellIconUrl(iconFile?: string | null, size = 56): string {
  const region = (import.meta.env.VITE_WOW_ICON_REGION || 'eu').toLowerCase();
  const icon = normalizeIcon(iconFile);
  return `https://render.worldofwarcraft.com/${region}/icons/${size}/${icon}.jpg`;
}
