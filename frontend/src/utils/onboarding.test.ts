import { describe, it, expect } from 'vitest'
import { matchesWowFilter, rankWeight, type WowFilter } from './onboarding'

describe('onboarding utils', () => {
  it('matchesWowFilter: All matches everything', () => {
    expect(matchesWowFilter(undefined, 'All')).toBe(true)
    expect(matchesWowFilter('Retail', 'All')).toBe(true)
    expect(matchesWowFilter('Classic', 'All')).toBe(true)
  })

  it('matchesWowFilter: Retail filter excludes classic', () => {
    expect(matchesWowFilter('Retail', 'Retail')).toBe(true)
    expect(matchesWowFilter('Classic', 'Retail')).toBe(false)
    expect(matchesWowFilter('Season of Discovery', 'Retail')).toBe(false)
  })

  it('matchesWowFilter: Classic filter includes any classic variant', () => {
    const filter: WowFilter = 'Classic'
    expect(matchesWowFilter('Classic', filter)).toBe(true)
    expect(matchesWowFilter('Classic Anniversary', filter)).toBe(true)
    expect(matchesWowFilter('Season of Discovery', filter)).toBe(true)
    expect(matchesWowFilter('Retail', filter)).toBe(false)
  })

  it('rankWeight sorts GM < Officer < Member < NoGuild', () => {

    expect(rankWeight(0, true)).toBeLessThan(rankWeight(1, true))
    expect(rankWeight(1, true)).toBeLessThan(rankWeight(2, true))
    expect(rankWeight(2, true)).toBeLessThan(rankWeight(null, false))
  })
})

