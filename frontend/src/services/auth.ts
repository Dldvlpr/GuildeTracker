import axios from "axios";

const API_BASE = import.meta.env.VITE_API_BASE_URL;

export function fetchDiscordAuthUrl() {
  return axios.get<{ url: string }>(`${API_BASE}/connect/discord`, {
    withCredentials: true,
  });
}
