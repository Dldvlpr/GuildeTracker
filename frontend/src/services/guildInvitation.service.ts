const BASE = (import.meta.env.VITE_API_BASE_URL ?? '').replace(/\/$/, '')

export type Invitation = {
  id: string
  token: string
  role: 'GM' | 'Officer' | 'Member'
  expiresAt?: string
  maxUses: number
  usedCount: number
}

export type InvitationResult =
  | { ok: true; data: Invitation }
  | { ok: false; error: string; status?: number }

export async function createGuildInvitation(
  guildId: string,
  payload: { role?: 'GM' | 'Officer' | 'Member'; expiresInDays?: number } = {}
): Promise<InvitationResult> {
  if (!BASE) {
    return { ok: false, error: 'VITE_API_BASE_URL is not set' }
  }

  try {
    const res = await fetch(`${BASE}/api/guilds/${encodeURIComponent(guildId)}/invitations`, {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
      },
      body: JSON.stringify({
        role: payload.role ?? 'Member',
        ...(payload.expiresInDays ? { expiresInDays: payload.expiresInDays } : {}),
      }),
    })

    if (!res.ok) {
      let message = `HTTP ${res.status}`
      try {
        const bodyText = await res.text()
        if (bodyText) {
          const body = JSON.parse(bodyText)
          if (typeof body?.message === 'string') message = body.message
          if (typeof body?.error === 'string') message = body.error
          if (Array.isArray(body?.violations) && body.violations.length) {
            message = body.violations.map((v: any) => v.message ?? String(v)).join(' · ')
          }
          if (Array.isArray(body?.details) && body.details.length) {
            message = body.details.join(' · ')
          }
        }
      } catch {}
      return { ok: false, error: message, status: res.status }
    }

    const text = await res.text()
    if (!text) {
      return { ok: false, error: 'Empty body', status: res.status }
    }

    const json = JSON.parse(text)
    const data: Invitation = {
      id: json.id,
      token: json.token,
      role: json.role,
      expiresAt: json.expiresAt,
      maxUses: json.maxUses,
      usedCount: json.usedCount,
    }

    return { ok: true, data }
  } catch (e: any) {
    return { ok: false, error: e?.message ?? 'Network error' }
  }
}

export type AcceptInvitationSuccess = {
  message: string
  guild: { id: string; name: string }
  role: 'GM' | 'Officer' | 'Member'
}

export type AcceptInvitationResult =
  | { ok: true; data: AcceptInvitationSuccess }
  | { ok: false; error: string; status?: number }

export async function acceptInvitation(token: string): Promise<AcceptInvitationResult> {
  if (!BASE) {
    return { ok: false, error: 'VITE_API_BASE_URL is not set' }
  }

  try {
    const res = await fetch(`${BASE}/api/invitations/${encodeURIComponent(token)}/accept`, {
      method: 'POST',
      credentials: 'include',
      headers: { Accept: 'application/json' },
    })

    if (!res.ok) {
      let message = `HTTP ${res.status}`
      try {
        const bodyText = await res.text()
        if (bodyText) {
          const body = JSON.parse(bodyText)
          if (typeof body?.message === 'string') message = body.message
          if (typeof body?.error === 'string') message = body.error
          if (Array.isArray(body?.violations) && body.violations.length) {
            message = body.violations.map((v: any) => v.message ?? String(v)).join(' · ')
          }
          if (Array.isArray(body?.details) && body.details.length) {
            message = body.details.join(' · ')
          }
        }
      } catch {}
      return { ok: false, error: message, status: res.status }
    }

    const text = await res.text()
    if (!text) {
      return { ok: false, error: 'Empty body', status: res.status }
    }

    const json = JSON.parse(text)
    const data: AcceptInvitationSuccess = {
      message: json.message,
      guild: json.guild,
      role: json.role,
    }
    return { ok: true, data }
  } catch (e: any) {
    return { ok: false, error: e?.message ?? 'Network error' }
  }
}
