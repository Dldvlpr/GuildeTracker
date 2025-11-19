export interface CanvasMapDefinition {
  id: string;
  name: string;
  raid?: string;
  expansion?: string;
  description?: string;
  tags?: string[];
  aspectRatio: number;
  svg: string;
}

export const CANVAS_MAPS: CanvasMapDefinition[] = [
  {
    id: 'circle-arena',
    name: 'Circular Arena',
    raid: 'Generic',
    expansion: 'Any',
    description: 'Large round platform with concentric safe zones',
    tags: ['circle', 'platform', 'aoe'],
    aspectRatio: 1,
    svg: `
      <svg viewBox="0 0 800 800" xmlns="http://www.w3.org/2000/svg">
        <rect width="800" height="800" fill="#030b16"/>
        <circle cx="400" cy="400" r="320" fill="rgba(16,185,129,0.08)" stroke="#10b981" stroke-width="3" stroke-dasharray="20 14"/>
        <circle cx="400" cy="400" r="220" fill="rgba(14,165,233,0.08)" stroke="#38bdf8" stroke-width="2" stroke-dasharray="12 12"/>
        <circle cx="400" cy="400" r="120" fill="rgba(248,113,113,0.12)" stroke="#f87171" stroke-width="2" stroke-dasharray="6 10"/>
        <line x1="400" y1="80" x2="400" y2="720" stroke="#1f2937" stroke-width="6" stroke-dasharray="18 14" opacity="0.4"/>
        <line x1="80" y1="400" x2="720" y2="400" stroke="#1f2937" stroke-width="6" stroke-dasharray="18 14" opacity="0.4"/>
        <circle cx="400" cy="400" r="18" fill="#fef08a" stroke="#1f2937" stroke-width="4"/>
      </svg>
    `,
  },
  {
    id: 'dual-platform',
    name: 'Twin Platforms',
    raid: 'Siege',
    expansion: 'Any',
    description: 'Two bridged circles often used for split phases',
    tags: ['split', 'platform'],
    aspectRatio: 1.2,
    svg: `
      <svg viewBox="0 0 960 800" xmlns="http://www.w3.org/2000/svg">
        <rect width="960" height="800" fill="#050b18"/>
        <circle cx="320" cy="400" r="220" fill="rgba(59,130,246,0.08)" stroke="#3b82f6" stroke-width="3"/>
        <circle cx="640" cy="400" r="220" fill="rgba(249,115,22,0.08)" stroke="#f97316" stroke-width="3"/>
        <rect x="320" y="360" width="320" height="80" fill="#111827" stroke="#475569" stroke-width="3" rx="30"/>
        <circle cx="320" cy="400" r="24" fill="#fde68a" stroke="#0f172a" stroke-width="3"/>
        <circle cx="640" cy="400" r="24" fill="#fde68a" stroke="#0f172a" stroke-width="3"/>
      </svg>
    `,
  },
  {
    id: 'donut-platform',
    name: 'Ring Platform',
    raid: 'Temple',
    expansion: 'Any',
    description: 'Donut arena with dangerous center',
    tags: ['ring', 'movement'],
    aspectRatio: 1,
    svg: `
      <svg viewBox="0 0 800 800" xmlns="http://www.w3.org/2000/svg">
        <rect width="800" height="800" fill="#050c19"/>
        <circle cx="400" cy="400" r="300" fill="#0f172a" stroke="#facc15" stroke-width="4"/>
        <circle cx="400" cy="400" r="170" fill="#1e293b" stroke="#f87171" stroke-width="6" stroke-dasharray="8 18"/>
        <circle cx="400" cy="400" r="100" fill="#7f1d1d" opacity="0.4"/>
        <line x1="400" y1="100" x2="400" y2="700" stroke="#facc15" stroke-width="2" stroke-dasharray="8 10" opacity="0.6"/>
        <line x1="100" y1="400" x2="700" y2="400" stroke="#facc15" stroke-width="2" stroke-dasharray="8 10" opacity="0.6"/>
      </svg>
    `,
  },
  {
    id: 'bridge-hall',
    name: 'Bridge Hall',
    raid: 'Citadel',
    expansion: 'Any',
    description: 'Long hallway with alcoves for spread mechanics',
    tags: ['hallway', 'bridge'],
    aspectRatio: 0.65,
    svg: `
      <svg viewBox="0 0 520 800" xmlns="http://www.w3.org/2000/svg">
        <rect width="520" height="800" fill="#04070f"/>
        <rect x="60" y="40" width="400" height="720" rx="50" fill="#0f172a" stroke="#94a3b8" stroke-width="3"/>
        <rect x="140" y="40" width="240" height="720" fill="#111c2d" stroke="#1f2937" stroke-width="2"/>
        <circle cx="260" cy="200" r="32" fill="#6366f1" opacity="0.5"/>
        <circle cx="260" cy="400" r="32" fill="#22d3ee" opacity="0.5"/>
        <circle cx="260" cy="600" r="32" fill="#f472b6" opacity="0.5"/>
        <line x1="60" y1="400" x2="460" y2="400" stroke="#334155" stroke-width="2" stroke-dasharray="16 14"/>
      </svg>
    `,
  },
  {
    id: 'hex-sanctum',
    name: 'Hex Sanctum',
    raid: 'Sanctum',
    expansion: 'Any',
    description: 'Angular room with pillars',
    tags: ['hexagon', 'pillars'],
    aspectRatio: 1,
    svg: `
      <svg viewBox="0 0 820 820" xmlns="http://www.w3.org/2000/svg">
        <polygon points="410,30 760,205 760,615 410,790 60,615 60,205" fill="#0b1220" stroke="#64748b" stroke-width="4"/>
        <polygon points="410,120 680,255 680,565 410,700 140,565 140,255" fill="#111c2d" stroke="#475569" stroke-width="2"/>
        <circle cx="410" cy="410" r="60" fill="#fbbf24" opacity="0.4"/>
        <circle cx="410" cy="410" r="18" fill="#fde68a" stroke="#0f172a" stroke-width="3"/>
        <circle cx="410" cy="160" r="32" fill="#1f2937" stroke="#94a3b8" stroke-width="2"/>
        <circle cx="610" cy="260" r="32" fill="#1f2937" stroke="#94a3b8" stroke-width="2"/>
        <circle cx="610" cy="560" r="32" fill="#1f2937" stroke="#94a3b8" stroke-width="2"/>
        <circle cx="410" cy="660" r="32" fill="#1f2937" stroke="#94a3b8" stroke-width="2"/>
        <circle cx="210" cy="560" r="32" fill="#1f2937" stroke="#94a3b8" stroke-width="2"/>
        <circle cx="210" cy="260" r="32" fill="#1f2937" stroke="#94a3b8" stroke-width="2"/>
      </svg>
    `,
  },
  {
    id: 'quadrant-room',
    name: 'Quadrant Room',
    raid: 'Vault',
    expansion: 'Any',
    description: 'Square divided into safe quadrants',
    tags: ['square', 'quadrants'],
    aspectRatio: 1,
    svg: `
      <svg viewBox="0 0 800 800" xmlns="http://www.w3.org/2000/svg">
        <rect width="800" height="800" fill="#03060d"/>
        <rect x="80" y="80" width="640" height="640" fill="#0f172a" stroke="#94a3b8" stroke-width="4" stroke-dasharray="12 12"/>
        <line x1="400" y1="80" x2="400" y2="720" stroke="#1d4ed8" stroke-width="3" stroke-dasharray="10 16"/>
        <line x1="80" y1="400" x2="720" y2="400" stroke="#1d4ed8" stroke-width="3" stroke-dasharray="10 16"/>
        <rect x="260" y="260" width="120" height="120" fill="#dc2626" opacity="0.4"/>
        <rect x="420" y="420" width="120" height="120" fill="#22c55e" opacity="0.4"/>
        <circle cx="400" cy="400" r="20" fill="#fef08a" stroke="#111827" stroke-width="3"/>
      </svg>
    `,
  },
]
