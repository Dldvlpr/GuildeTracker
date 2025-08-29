const API_BASE = import.meta.env.VITE_API_BASE_URL;

export function redirectToDiscordAuth() {
  window.location.href = `${API_BASE}/connect/discord`;
}

export async function checkAuthStatus() {
  try {
    const response = await fetch('/api/me', {
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
  try {
    const response = await fetch('/api/logout', {
      method: 'GET',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
      },
    });

    if (response.ok) {
      const authStatus = await checkAuthStatus();
      return { success: true, isStillAuthenticated: authStatus.isAuthenticated };
    } else {
      return { success: false, error: `Status: ${response.status}` };
    }
  } catch (error: unknown) {
    const message = error instanceof Error ? error.message : String(error);
    return { success: false, error: message };
  }
}
