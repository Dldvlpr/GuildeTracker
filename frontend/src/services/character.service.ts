import type { Character } from "@/interfaces/game.interface";
import type { GameGuild } from '@/interfaces/GameGuild.interface.ts'

const BASE = (import.meta.env.VITE_API_BASE_URL ?? '').replace(/\/$/, '')

export type characterResult =
  | { ok: true; data: Character[] }
  | { ok: false; error: string; status?: number };

export type characterOneResult =
  | { ok: true; data: Character }
  | { ok: false; error: string; status?: number };

export type DeleteResult =
  | { ok: true; message: string }
  | { ok: false; error: string; status?: number };

export async function getCharactersByGuildId(guildId: string, opts?: { signal?: AbortSignal }): Promise<characterResult> {
  if (!BASE) {
    return { ok: false, error: 'VITE_API_BASE_URL is not set' };
  }
  try {
    const res = await fetch(`${BASE}/api/guilds/${encodeURIComponent(guildId)}/characters`, {
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

    const data = json as Character[];

    return { ok: true, data };
  } catch (e: any) {
    return { ok: false, error: e?.message ?? 'Network error' };
  }
}

export async function createCharacter(
  character: Omit<Character, 'id' | 'createdAt'>,
  guildId?: string,
  opts?: { signal?: AbortSignal }
): Promise<characterOneResult> {
  if (!BASE) {
    return { ok: false, error: 'VITE_API_BASE_URL is not set' };
  }

  const payload: any = {
    name: character.name,
    class: character.class,
    classSpec: character.spec,
    role: character.role,
  };

  if (guildId) {
    payload.guildId = guildId;
  }

  try {
    const res = await fetch(`${BASE}/api/characters`, {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
      },
      body: JSON.stringify(payload),
      signal: opts?.signal,
    });

    if (!res.ok) {
      let message = `HTTP ${res.status}`;
      try {
        const bodyText = await res.text();
        if (bodyText) {
          const body = JSON.parse(bodyText);
          if (typeof body?.error === 'string') message = body.error;
          if (Array.isArray(body?.violations) && body.violations.length) {
            message = body.violations.join(' · ');
          }
          if (Array.isArray(body?.details) && body.details.length) {
            message = body.details.join(' · ');
          }
        }
      } catch {
        return { ok: false, error: message, status: res.status };
      }
      return { ok: false, error: message, status: res.status };
    }

    const json = await res.json();

    if (!json.character) {
      return { ok: false, error: 'Unexpected response: missing character data' };
    }

    return { ok: true, data: json.character as Character };
  } catch (e: any) {
    return { ok: false, error: e?.message ?? 'Network error' };
  }
}

export async function deleteCharacter(
  characterId: string,
  opts?: { signal?: AbortSignal }
): Promise<DeleteResult> {
  if (!BASE) {
    return { ok: false, error: 'VITE_API_BASE_URL is not set' };
  }

  try {
    const res = await fetch(`${BASE}/api/characters/${encodeURIComponent(characterId)}`, {
      method: 'DELETE',
      credentials: 'include',
      headers: {
        'Accept': 'application/json',
      },
      signal: opts?.signal,
    });

    if (!res.ok) {
      let message = `HTTP ${res.status}`;
      try {
        const bodyText = await res.text();
        if (bodyText) {
          const body = JSON.parse(bodyText);
          if (typeof body?.error === 'string') message = body.error;
          if (typeof body?.message === 'string') message = body.message;
        }
      } catch {
        return { ok: false, error: message, status: res.status };
      }
      return { ok: false, error: message, status: res.status };
    }

    const json = await res.json();
    return { ok: true, message: json.message || 'Character deleted successfully' };
  } catch (e: any) {
    return { ok: false, error: e?.message ?? 'Network error' };
  }
}
