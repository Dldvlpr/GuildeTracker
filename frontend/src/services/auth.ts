import { useUserStore } from '@/stores/userStore.ts'

const API_BASE = import.meta.env.VITE_API_BASE_URL;

export function redirectToDiscordAuth() {
  window.location.href = `${API_BASE}/connect/discord`;
}

export async function checkAuthStatus() {
  try {
    const response = await fetch(`${API_BASE}/api/me`, {
      method: 'GET',
      credentials: 'include',
    });

    if (response.ok) {
      const userData = await response.json();
      return { isAuthenticated: true, user: userData };
    } else {
      return { isAuthenticated: false, user: null };
    }
  } catch (error) {
    console.warn('Erreur lors de la vérification d\'authentification:', error);
    return { isAuthenticated: false, user: null };
  }
}

export async function logoutUser() {
    const response = await fetch(`${API_BASE}/api/logout`, {
      method: 'POST',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
      },
    });
    if (response.ok) {
      useUserStore().logout();
    }

      return { success: true, isStillAuthenticated: false};
    }
