export interface RaidMarkerIcon {
  id: string;
  label: string;
  color: string;
  svg: string;
}

const baseSquare = (fill: string, content: string) => `
  <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
    <rect x="4" y="4" width="56" height="56" rx="14" fill="#111827" stroke="#0f172a" stroke-width="4"/>
    <rect x="10" y="10" width="44" height="44" rx="12" fill="${fill}" stroke="white" stroke-width="3"/>
    ${content}
  </svg>
`;

export const RAID_MARKER_ICONS: RaidMarkerIcon[] = [
  {
    id: 'star',
    label: 'Star',
    color: '#fcd34d',
    svg: `
      <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
        <rect x="4" y="4" width="56" height="56" rx="14" fill="#111827" stroke="#0f172a" stroke-width="4"/>
        <circle cx="32" cy="32" r="22" fill="#fbbf24" stroke="white" stroke-width="3"/>
        <path d="M32 12 L37.8 25.2 L52 26.2 L41 35.5 L44.5 48.5 L32 41 L19.5 48.5 L23 35.5 L12 26.2 L26.2 25.2 Z" fill="#fde68a" stroke="#0f172a" stroke-width="2" stroke-linejoin="round"/>
      </svg>
    `,
  },
  {
    id: 'circle',
    label: 'Circle',
    color: '#fb923c',
    svg: `
      <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
        <rect x="4" y="4" width="56" height="56" rx="14" fill="#111827" stroke="#0f172a" stroke-width="4"/>
        <circle cx="32" cy="32" r="22" fill="#fb923c" stroke="white" stroke-width="3"/>
        <circle cx="32" cy="32" r="12" fill="#fed7aa" stroke="#0f172a" stroke-width="2"/>
      </svg>
    `,
  },
  {
    id: 'diamond',
    label: 'Diamond',
    color: '#a855f7',
    svg: `
      <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
        <rect x="4" y="4" width="56" height="56" rx="14" fill="#111827" stroke="#0f172a" stroke-width="4"/>
        <rect x="11" y="11" width="42" height="42" rx="6" transform="rotate(45 32 32)" fill="#a855f7" stroke="white" stroke-width="3"/>
        <rect x="21" y="21" width="22" height="22" transform="rotate(45 32 32)" fill="#f5d0fe" stroke="#0f172a" stroke-width="2"/>
      </svg>
    `,
  },
  {
    id: 'triangle',
    label: 'Triangle',
    color: '#22c55e',
    svg: `
      <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
        <rect x="4" y="4" width="56" height="56" rx="14" fill="#111827" stroke="#0f172a" stroke-width="4"/>
        <path d="M32 10 L54 50 H10 Z" fill="#22c55e" stroke="white" stroke-width="3" stroke-linejoin="round"/>
        <path d="M32 18 L46 46 H18 Z" fill="#bbf7d0" stroke="#0f172a" stroke-width="2" stroke-linejoin="round"/>
      </svg>
    `,
  },
  {
    id: 'moon',
    label: 'Moon',
    color: '#93c5fd',
    svg: `
      <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
        <rect x="4" y="4" width="56" height="56" rx="14" fill="#111827" stroke="#0f172a" stroke-width="4"/>
        <circle cx="32" cy="32" r="22" fill="#bfdbfe" stroke="white" stroke-width="3"/>
        <circle cx="40" cy="26" r="18" fill="#0f172a" opacity="0.8"/>
      </svg>
    `,
  },
  {
    id: 'square',
    label: 'Square',
    color: '#3b82f6',
    svg: baseSquare('#3b82f6', `<rect x="16" y="16" width="32" height="32" rx="6" fill="#bfdbfe" stroke="#0f172a" stroke-width="2"/>`),
  },
  {
    id: 'cross',
    label: 'Cross',
    color: '#ef4444',
    svg: `
      <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
        <rect x="4" y="4" width="56" height="56" rx="14" fill="#111827" stroke="#0f172a" stroke-width="4"/>
        <rect x="12" y="28" width="40" height="8" fill="#ef4444" stroke="white" stroke-width="2"/>
        <rect x="28" y="12" width="8" height="40" fill="#ef4444" stroke="white" stroke-width="2"/>
        <rect x="16" y="16" width="32" height="32" fill="#fee2e2" opacity="0.3"/>
      </svg>
    `,
  },
  {
    id: 'skull',
    label: 'Skull',
    color: '#e5e7eb',
    svg: `
      <svg viewBox="0 0 64 64" xmlns="http://www.w3.org/2000/svg">
        <rect x="4" y="4" width="56" height="56" rx="14" fill="#111827" stroke="#0f172a" stroke-width="4"/>
        <path d="M32 10c-10.5 0-19 8.5-19 19 0 6.8 3.6 12.8 9 16l-1 8c-.3 2 1.3 3.8 3.3 3.8h4.7v-6h4v6h4.7c2 0 3.6-1.8 3.3-3.8l-1-8c5.4-3.2 9-9.2 9-16 0-10.5-8.5-19-19-19z" fill="#f3f4f6" stroke="#0f172a" stroke-width="3"/>
        <circle cx="25" cy="29" r="4" fill="#0f172a"/>
        <circle cx="39" cy="29" r="4" fill="#0f172a"/>
        <rect x="27" y="38" width="10" height="6" rx="2" fill="#0f172a"/>
      </svg>
    `,
  },
];
