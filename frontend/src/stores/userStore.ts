import { defineStore } from 'pinia';
import type { DiscordUserInterface } from '@/interfaces/DiscordUser.interface';

interface UserState {
  user: DiscordUserInterface | null;
}

export const useUserStore = defineStore('user', {
  state: (): UserState => ({
    user: null,
  }),

  getters: {
    isAuthenticated: (state): boolean => !!state.user,
  },

  actions: {
    setUser(data: DiscordUserInterface): void {
      this.user = data;
    },

    logout(): void {
      this.$reset();
    },
  },
});
