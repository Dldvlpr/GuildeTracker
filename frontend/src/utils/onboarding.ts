export type WowFilter = 'All' | 'Retail' | 'Classic'

export function isClassicLabel(wowType?: string): boolean {
  const s = (wowType || '').toLowerCase()
  return s.includes('classic')
}

export function matchesWowFilter(wowType: string | undefined, filter: WowFilter): boolean {
  if (filter === 'All') return true
  const classic = isClassicLabel(wowType)
  return filter === 'Classic' ? classic : !classic
}

export function rankWeight(rank: number | null | undefined, hasGuild: boolean): number {
  if (rank === 0) return 0
  if (rank === 1) return 1
  if (hasGuild) return 2
  return 3
}

