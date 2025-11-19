import { redirectToBlizzardAuth } from '@/services/auth'
import { resolvePostLoginTarget, DEFAULT_POST_LOGIN_REDIRECT } from '@/utils/redirect'

const BASE = (import.meta.env.VITE_API_BASE_URL ?? '').replace(/\/$/, '')

type FetchOpts = { signal?: AbortSignal }

function relinkBlizzard() {
  const target = resolvePostLoginTarget(window.location.pathname + window.location.search, DEFAULT_POST_LOGIN_REDIRECT)
  redirectToBlizzardAuth(target)
}

async function handleBlizzardResponse(res: Response) {
  if (res.status === 401) {
    try {
      const text = await res.text()
      const body = text ? JSON.parse(text) : null
      if (body?.error === 'blizzard_token_expired') {
        relinkBlizzard()

        throw new Error('blizzard_token_expired')
      }
    } catch (e) {

    }
  }
}

type CharactersCache = { ts: number; payload: any }

const CHAR_CACHE_KEY = 'blizzardCharactersCache'

export async function getBlizzardCharacters(opts?: FetchOpts) {
  if (!BASE) throw new Error('VITE_API_BASE_URL is not set')
  const res = await fetch(`${BASE}/api/blizzard/characters`, {
    method: 'GET',
    credentials: 'include',
    headers: { Accept: 'application/json' },
    signal: opts?.signal,
  })
  await handleBlizzardResponse(res)
  if (!res.ok) throw new Error(`HTTP ${res.status}`)
  return await res.json()
}

export async function getBlizzardCharacterDetails(realm: string, characterName: string, wowType?: string, opts?: FetchOpts) {
  if (!BASE) throw new Error('VITE_API_BASE_URL is not set')
  const url = new URL(`${BASE}/api/blizzard/characters/${encodeURIComponent(realm)}/${encodeURIComponent(characterName)}`)
  if (wowType) {
    url.searchParams.set('wowType', wowType)
  }
  const res = await fetch(url.toString(), {
    method: 'GET',
    credentials: 'include',
    headers: { Accept: 'application/json' },
    signal: opts?.signal,
  })
  await handleBlizzardResponse(res)
  if (!res.ok) throw new Error(`HTTP ${res.status}`)
  return await res.json()
}

export async function getBlizzardCharacterGuild(realm: string, characterName: string, wowType?: string, opts?: FetchOpts) {
  if (!BASE) throw new Error('VITE_API_BASE_URL is not set')
  const url = new URL(`${BASE}/api/blizzard/characters/${encodeURIComponent(realm)}/${encodeURIComponent(characterName)}/guild`)
  if (wowType) url.searchParams.set('wowType', wowType)
  const res = await fetch(url.toString(), {
    method: 'GET',
    credentials: 'include',
    headers: { Accept: 'application/json' },
    signal: opts?.signal,
  })
  await handleBlizzardResponse(res)
  if (!res.ok) throw new Error(`HTTP ${res.status}`)
  return await res.json()
}

export async function claimGuild(realm: string, characterName: string, wowType?: string, opts?: FetchOpts) {
  if (!BASE) throw new Error('VITE_API_BASE_URL is not set')
  const res = await fetch(`${BASE}/api/guilds/claim`, {
    method: 'POST',
    credentials: 'include',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({ realm, characterName, wowType }),
    signal: opts?.signal,
  })
  await handleBlizzardResponse(res)
  if (!res.ok) {
    const errorData = await res.json().catch(() => ({}))
    throw new Error(errorData.message || `HTTP ${res.status}`)
  }
  return await res.json()
}

export async function getBlizzardCharactersCached(opts?: FetchOpts & { ttlMs?: number, force?: boolean }) {
  const ttl = opts?.ttlMs ?? 10 * 60 * 1000
  if (!opts?.force) {
    try {
      const raw = localStorage.getItem(CHAR_CACHE_KEY)
      if (raw) {
        const cache: CharactersCache = JSON.parse(raw)
        if (cache?.ts && Date.now() - cache.ts < ttl) {
          return cache.payload
        }
      }
    } catch {}
  }
  const payload = await getBlizzardCharacters(opts)
  try {
    const toSave: CharactersCache = { ts: Date.now(), payload }
    localStorage.setItem(CHAR_CACHE_KEY, JSON.stringify(toSave))
  } catch {}
  return payload
}

export function invalidateBlizzardCharactersCache() {
  try { localStorage.removeItem(CHAR_CACHE_KEY) } catch {}
}
