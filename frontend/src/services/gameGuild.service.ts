import type { GameGuild } from '@/interfaces/GameGuild.interface'

const BASE = (import.meta.env.VITE_API_BASE_URL ?? '').replace(/\/$/, '')

type GuildResult =
  | { ok: true; data: GameGuild }
  | { ok: false; error: string; status?: number }

export async function getGameGuild(guildId: string): Promise<GuildResult> {
  if (!BASE) {
    return { ok: false, error: 'VITE_API_BASE_URL is not set' }
  }

  try {
    const res = await fetch(
      `${BASE}/api/getGuild/${encodeURIComponent(guildId)}`,
      {
        method: 'GET',
        credentials: 'include',
        headers: { Accept: 'application/json' }
      }
    )

    if (!res.ok) {
      let message = `HTTP ${res.status}`
      try {
        const body = await res.json()
        if (body?.message) message = body.message
        if (Array.isArray(body?.violations) && body.violations.length) {
          message = body.violations.map((v: any) => v.message).join(' · ')
        }
      } catch {
        return { ok: false, error: 'error' }
      }
      return { ok: false, error: message, status: res.status }
    }

    if (res.status === 204) {
      return { ok: false, error: 'Empty response (204 No Content)' }
    }

    const data = (await res.json()) as GameGuild
    return { ok: true, data }
  } catch (e: any) {
    return { ok: false, error: e?.message ?? 'Network error' }
  }
}

export async function getAllGameGuild(guildId: string): Promise<GuildResult> {
  if (!BASE) {
    return { ok: false, error: 'VITE_API_BASE_URL is not set' }
  }

  try {
    const res = await fetch(
      `${BASE}/api/getAllGuild/${encodeURIComponent(guildId)}`,
      {
        method: 'GET',
        credentials: 'include',
        headers: { Accept: 'application/json' }
      }
    )

    if (!res.ok) {
      let message = `HTTP ${res.status}`
      try {
        const body = await res.json()
        if (body?.message) message = body.message
        if (Array.isArray(body?.violations) && body.violations.length) {
          message = body.violations.map((v: any) => v.message).join(' · ')
        }
      } catch {
        return { ok: false, error: 'error' }
      }
      return { ok: false, error: message, status: res.status }
    }

    if (res.status === 204) {
      return { ok: false, error: 'Empty response (204 No Content)' }
    }

    const data = (await res.json()) as GameGuild
    return { ok: true, data }
  } catch (e: any) {
    return { ok: false, error: e?.message ?? 'Network error' }
  }
}


export async function createGuild(name: string) {
  try {
    const res = await fetch(`${BASE}/api/guilds`, {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify({ name }),
    })

    if (!res.ok) {
      let msg = `Erreur ${res.status}`
      try {
        const data = await res.json()
        if (data?.message) msg = data.message
        if (Array.isArray(data?.violations)) {
          msg = data.violations.map((v: any) => v.message).join(' · ')
        }
      } catch {}
      throw new Error(msg)
    }

    return (await res.json()) as { id: string; name: string }
  } catch (e: any) {
    throw new Error(e?.message ?? 'Erreur réseau')
  }
}
