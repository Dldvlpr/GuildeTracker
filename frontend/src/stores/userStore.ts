import { defineStore } from 'pinia'
import type { DiscordUserInterface } from '@/interfaces/DiscordUser.interface'

interface UserState {
  user: DiscordUserInterface | null
  isLoading: boolean
  _initPromise: Promise<void> | null
}

export const useUserStore = defineStore('user', {
  state: (): UserState => ({
    user: null,
    isLoading: true,
    _initPromise: null,
  }),

  getters: {
    isAuthenticated: (s) => !!s.user,
    isReady: (s) => !s.isLoading,
  },

  actions: {
    setUser(data: DiscordUserInterface): void {
      this.user = data
      this.isLoading = false
    },

    logout(): void {
      this.user = null
      this.isLoading = false
    },

    setLoading(loading: boolean): void {
      this.isLoading = loading
    },

    async initFromApi(apiBase: string): Promise<void> {
      if (this._initPromise) return this._initPromise

      this.isLoading = true
      this._initPromise = (async () => {
        try {
          const res = await fetch(`${apiBase}/api/me`, {
            credentials: 'include', // VERY IMPORTANT
            headers: { 'Accept': 'application/json' }})
          if (res.ok) {
            const data = (await res.json()) as DiscordUserInterface
            this.user = data
          } else {
            this.user = null
          }
        } catch {
          this.user = null
        } finally {
          this.isLoading = false
          this._initPromise = null
        }
      })()

      return this._initPromise
    },
  },
})
