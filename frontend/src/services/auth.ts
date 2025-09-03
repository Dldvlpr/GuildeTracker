const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000';

export function redirectToDiscordAuth() {
  window.location.href = `${API_BASE}/login`;
}
