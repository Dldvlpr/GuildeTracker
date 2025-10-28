import type { DiscordUserInterface } from '@/interfaces/DiscordUser.interface.ts';

const API_BASE = import.meta.env.VITE_API_BASE_URL;

type UserResult =
  | { isAuthenticated: false, user: null }
  | { isAuthenticated: true, user: DiscordUserInterface }


export function redirectToDiscordAuth() {
  window.location.href = `${API_BASE}/connect/discord`;
}

export function redirectToBlizzardAuth() {
  window.location.href = `${API_BASE}/api/oauth/blizzard/connect`;
}

export async function checkAuthStatus(): Promise<UserResult> {
  try {
    const response = await fetch(`${API_BASE}/api/me`, {
      method: 'GET',
      credentials: 'include',
    });

    if (response.ok) {
      const userData = await response.json();
      return { isAuthenticated: true, user: userData as DiscordUserInterface };
    } else {
      return { isAuthenticated: false, user: null };
    }
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
  } catch (error) {
    return { isAuthenticated: false, user: null };
  }
}

export async function logoutUser() {
  try {
    await fetch(`${API_BASE}/logout`, {
      method: 'POST',
      credentials: 'include',
    })

    return { success: true, isStillAuthenticated: false };
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
  } catch (error) {
    return { success: false, isStillAuthenticated: true };
  }
}
