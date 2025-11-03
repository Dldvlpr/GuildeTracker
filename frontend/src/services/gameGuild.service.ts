import type { GameGuild } from '@/interfaces/GameGuild.interface'

const BASE = (import.meta.env.VITE_API_BASE_URL ?? '').replace(/\/$/, '')

export type GuildResult =
  | { ok: true; data: GameGuild[] }
  | { ok: false; error: string; status?: number };

export type GuildOneResult =
  | { ok: true; data: GameGuild }
  | { ok: false; error: string; status?: number };

export async function getGameGuild(guildId: string, opts?: { signal?: AbortSignal }): Promise<GuildOneResult> {
  if (!BASE) {
    return { ok: false, error: 'VITE_API_BASE_URL is not set' };
  }

  try {
    const res = await fetch(`${BASE}/api/guilds/${encodeURIComponent(guildId)}`, {
      method: 'GET',
      credentials: 'include',
      headers: { Accept: 'application/json' },
      signal: opts?.signal,
    });

    if (!res.ok) {
      let message = `HTTP ${res.status}`;
      try {
        const bodyText = await res.text();
        if (bodyText) {
          const body = JSON.parse(bodyText);
          if (typeof body?.message === 'string') message = body.message;
          if (Array.isArray(body?.violations) && body.violations.length) {
            message = body.violations.map((v: any) => v.message).join(' · ');
          }
        }
      } catch {
      }
      return { ok: false, error: message, status: res.status };
    }

    if (res.status === 204) {
      return { ok: false, error: 'Empty response (204 No Content)', status: 204 };
    }

    const text = await res.text();
    if (!text) {
      return { ok: false, error: 'Empty body', status: res.status };
    }

    const json = JSON.parse(text);

    if (typeof json !== 'object' || json === null || typeof json.id === 'undefined') {
      return { ok: false, error: 'Unexpected payload: expected a GameGuild object' };
    }

    const data = json as GameGuild;
    return { ok: true, data };
  } catch (e: any) {
    return { ok: false, error: e?.message ?? 'Network error' };
  }
}

export async function getAllGameGuild(opts?: { signal?: AbortSignal }): Promise<GuildResult> {
  if (!BASE) {
    return { ok: false, error: 'VITE_API_BASE_URL is not set' };
  }

  try {
    const res = await fetch(`${BASE}/api/gameguild`, {
      method: 'GET',
      credentials: 'include',
      headers: { Accept: 'application/json' },
      signal: opts?.signal,
    });

    if (!res.ok) {
      let message = `HTTP ${res.status}`;
      try {
        const bodyText = await res.text();
        if (bodyText) {
          const body = JSON.parse(bodyText);
          if (typeof body?.message === 'string') message = body.message;
          if (Array.isArray(body?.violations) && body.violations.length) {
            message = body.violations.map((v: any) => v.message).join(' · ');
          }
        }
      } catch {
        return { ok: false, error: message, status: res.status };
      }
      return { ok: false, error: message, status: res.status };
    }

    if (res.status === 204) {
      return { ok: true, data: [] };
    }

    const text = await res.text();
    const json = text ? JSON.parse(text) : [];

    if (!Array.isArray(json)) {
      return { ok: false, error: 'Unexpected payload: expected an array' };
    }

    const data = json as GameGuild[];

    return { ok: true, data };
  } catch (e: any) {
    return { ok: false, error: e?.message ?? 'Network error' };
  }
}

export async function getMyGuild(opts?: { signal?: AbortSignal }): Promise<GuildResult> {
  if (!BASE) {
    return { ok: false, error: 'VITE_API_BASE_URL is not set' };
  }

  try {
    const res = await fetch(`${BASE}/api/me/guilds`, {
      method: 'GET',
      credentials: 'include',
      headers: { Accept: 'application/json' },
      signal: opts?.signal,
    });

    if (!res.ok) {
      let message = `HTTP ${res.status}`;
      try {
        const bodyText = await res.text();
        if (bodyText) {
          const body = JSON.parse(bodyText);
          if (typeof body?.message === 'string') message = body.message;
          if (Array.isArray(body?.violations) && body.violations.length) {
            message = body.violations.map((v: any) => v.message).join(' · ');
          }
        }
      } catch {
        return { ok: false, error: message, status: res.status };
      }
      return { ok: false, error: message, status: res.status };
    }

    if (res.status === 204) {
      return { ok: true, data: [] };
    }

    const text = await res.text();
    const json = text ? JSON.parse(text) : [];

    if (!Array.isArray(json)) {
      return { ok: false, error: 'Unexpected payload: expected an array' };
    }

    const data = json as GameGuild[];

    return { ok: true, data };
  } catch (e: any) {
    return { ok: false, error: e?.message ?? 'Network error' };
  }
}


export async function createGuild(name: string, faction: 'HORDE' | 'ALLIANCE') {
  if (!BASE) {
    throw new Error('VITE_API_BASE_URL is not set');
  }

  try {
    const res = await fetch(`${BASE}/api/guilds`, {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify({ name, faction }),
    })

    if (!res.ok) {
      let msg = `Error ${res.status}`
      try {
        const data = await res.json()
        if (data?.message) msg = data.message
        if (Array.isArray(data?.violations)) {
          msg = data.violations.map((v: any) => v.message).join(' · ')
        }
        if (Array.isArray(data?.details)) {
          msg = data.details.join(' · ')
        }
      } catch {}
      throw new Error(msg)
    }

    return (await res.json()) as { status: string; id: string }
  } catch (e: any) {
    throw new Error(e?.message ?? 'Network error')
  }
}

export async function checkGuildExists(realm: string | null, name: string) {
  const BASE = (import.meta.env.VITE_API_BASE_URL ?? '').replace(/\/$/, '')
  if (!BASE) throw new Error('VITE_API_BASE_URL is not set')
  const url = new URL(`${BASE}/api/guilds/exists`)
  url.searchParams.set('name', name)
  if (realm) url.searchParams.set('realm', realm)
  const res = await fetch(url.toString(), { credentials: 'include' })
  if (!res.ok) throw new Error(`HTTP ${res.status}`)
  return res.json() as Promise<{ exists: boolean; id?: string; name?: string }>
}

export async function joinGuild(guildId: string, realm: string, characterName: string, wowType?: string) {
  const BASE = (import.meta.env.VITE_API_BASE_URL ?? '').replace(/\/$/, '')
  if (!BASE) throw new Error('VITE_API_BASE_URL is not set')
  const res = await fetch(`${BASE}/api/guilds/${encodeURIComponent(guildId)}/join`, {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ realm, characterName, wowType })
  })
  if (!res.ok) {
    const data = await res.json().catch(() => ({}))
    throw new Error(data?.message || data?.error || `HTTP ${res.status}`)
  }
  return res.json().catch(() => ({}))
}
