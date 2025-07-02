// frontend/src/stores/userStore.ts
import { defineStore } from 'pinia';
import type { DiscordUserInterface } from '@/interfaces/DiscordUser.interface';

interface UserState {
  user: DiscordUserInterface | null;
  isLoading: boolean;
}

export const useUserStore = defineStore('user', {
  state: (): UserState => ({
    user: null,
    isLoading: true,
  }),

  getters: {
    isAuthenticated: (state): boolean => !!state.user,
    isReady: (state): boolean => !state.isLoading,
  },

  actions: {
    setUser(data: DiscordUserInterface): void {
      console.log('📝 Store: setUser called with:', data);
      this.user = data;
      this.isLoading = false;
    },

    logout(): void {
      console.log('🚪 Store: logout called');
      this.user = null;
      this.isLoading = false;
    },

    setLoading(loading: boolean): void {
      this.isLoading = loading;
    },
  },
});
