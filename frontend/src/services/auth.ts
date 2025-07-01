import axios from "axios";

const API_BASE = import.meta.env.VITE_API_BASE_URL;

export function redirectToDiscordAuth() {
  window.location.href = `${API_BASE}/connect/discord`;
}
