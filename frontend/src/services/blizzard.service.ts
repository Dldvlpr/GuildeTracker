import { redirectToBlizzardAuth } from '@/services/auth'

const BASE = (import.meta.env.VITE_API_BASE_URL ?? '').replace(/\/$/, '')

type FetchOpts = { signal?: AbortSignal }

function relinkBlizzard() {
  try { localStorage.setItem('postLoginRedirect', window.location.pathname + window.location.search) } catch {}
  redirectToBlizzardAuth()
}

async function handleBlizzardResponse(res: Response) {
  if (res.status === 401) {
    try {
      const text = await res.text()
      const body = text ? JSON.parse(text) : null
      if (body?.error === 'blizzard_token_expired') {
        relinkBlizzard()
        // Throw to stop caller logic
        throw new Error('blizzard_token_expired')
      }
    } catch (e) {
      // If parsing fails, propagate original 401
    }
  }
}

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

export async function getBlizzardCharacterDetails(realm: string, characterName: string, opts?: FetchOpts) {
  if (!BASE) throw new Error('VITE_API_BASE_URL is not set')
  const res = await fetch(`${BASE}/api/blizzard/characters/${encodeURIComponent(realm)}/${encodeURIComponent(characterName)}`, {
    method: 'GET',
    credentials: 'include',
    headers: { Accept: 'application/json' },
    signal: opts?.signal,
  })
  await handleBlizzardResponse(res)
  if (!res.ok) throw new Error(`HTTP ${res.status}`)
  return await res.json()
}

export async function getBlizzardCharacterGuild(realm: string, characterName: string, opts?: FetchOpts) {
  if (!BASE) throw new Error('VITE_API_BASE_URL is not set')
  const res = await fetch(`${BASE}/api/blizzard/characters/${encodeURIComponent(realm)}/${encodeURIComponent(characterName)}/guild`, {
    method: 'GET',
    credentials: 'include',
    headers: { Accept: 'application/json' },
    signal: opts?.signal,
  })
  await handleBlizzardResponse(res)
  if (!res.ok) throw new Error(`HTTP ${res.status}`)
  return await res.json()
}

