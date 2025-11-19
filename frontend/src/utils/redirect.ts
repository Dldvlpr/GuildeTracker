const DEFAULT_REDIRECT = '/app'
const hasWindow = typeof window !== 'undefined'

export const DEFAULT_POST_LOGIN_REDIRECT = DEFAULT_REDIRECT

function isAbsoluteUrl(value: string) {
  return /^[a-zA-Z][a-zA-Z0-9+\-.]*:/.test(value)
}

export function sanitizeInternalPath(candidate?: string | null): string | null {
  if (!candidate) return null
  const trimmed = candidate.trim()
  if (!trimmed) return null

  if (trimmed.startsWith('//')) return null

  if (isAbsoluteUrl(trimmed)) {
    if (!hasWindow) return null
    try {
      const url = new URL(trimmed)
      if (url.origin !== window.location.origin) return null
      return (url.pathname || '/') + url.search + url.hash
    } catch {
      return null
    }
  }

  if (!trimmed.startsWith('/')) return null

  if (!hasWindow) return trimmed || null

  try {
    const url = new URL(trimmed, window.location.origin)
    return (url.pathname || '/') + url.search + url.hash
  } catch {
    return null
  }
}

export function resolvePostLoginTarget(candidate?: string | null, fallback = DEFAULT_REDIRECT): string {
  return sanitizeInternalPath(candidate) ?? fallback
}

export function buildOAuthRedirectUrl(baseUrl: string, candidate?: string | null): string {
  const url = new URL(baseUrl)
  const target = resolvePostLoginTarget(candidate)
  url.searchParams.set('returnTo', target)
  return url.toString()
}
