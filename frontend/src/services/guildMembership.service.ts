import type { guildMembership } from '@/interfaces/guildMemebership.interface.ts';

const BASE = (import.meta.env.VITE_API_BASE_URL ?? '').replace(/\/$/, '')

export type GuildMembershipResult =
  | { ok: true; data: guildMembership[] }
  | { ok: false; error: string; status?: number };

export type GuildMembershipOneResult =
  | { ok: true; data: guildMembership }
  | { ok: false; error: string; status?: number };

export type GuildMembershipDeleteResult =
  | { ok: true }
  | { ok: false; error: string; status?: number };

export async function getAllMembership(guildId: string, opts?: { signal?: AbortSignal }): Promise<GuildMembershipResult> {
  if (!BASE) {
    return { ok: false, error: 'VITE_API_BASE_URL is not set' };
  }

  try {
    const res = await fetch(`${BASE}/api/guildmembers/${encodeURIComponent(guildId)}`, {
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

    const data = json as guildMembership[];
    console.log(data)

    return { ok: true, data };
  } catch (e: any) {
    return { ok: false, error: e?.message ?? 'Network error' };
  }
}

export async function updateMemberRole(memberId: string, role: string, opts?: { signal?: AbortSignal }): Promise<GuildMembershipOneResult> {
  if (!BASE) {
    return { ok: false, error: 'VITE_API_BASE_URL is not set' };
  }

  try {
    const res = await fetch(`${BASE}/api/guildmembers/${encodeURIComponent(memberId)}`, {
      method: 'PATCH',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json'
      },
      body: JSON.stringify({ role }),
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

    const text = await res.text();
    if (!text) {
      return { ok: false, error: 'Empty body', status: res.status };
    }

    const json = JSON.parse(text);

    if (typeof json !== 'object' || json === null) {
      return { ok: false, error: 'Unexpected payload: expected an object' };
    }

    const data = json as guildMembership;
    return { ok: true, data };
  } catch (e: any) {
    return { ok: false, error: e?.message ?? 'Network error' };
  }
}

export async function deleteMemberRole(memberId: string, opts?: { signal?: AbortSignal }): Promise<GuildMembershipDeleteResult> {
  if (!BASE) {
    return { ok: false, error: 'VITE_API_BASE_URL is not set' };
  }

  try {
    const res = await fetch(`${BASE}/api/guildmembers/${encodeURIComponent(memberId)}`, {
      method: 'DELETE',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json'
      },
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

    return { ok: true };
  } catch (e: any) {
    return { ok: false, error: e?.message ?? 'Network error' };
  }
}
