<template>
  <div
    class="min-h-[100svh] flex flex-col bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950 text-slate-100 selection:bg-indigo-600/30 selection:text-white"
  >
    <Transition
      enter-active-class="transition duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="sidebarOpen"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 md:hidden"
        @click="sidebarOpen = false"
        aria-hidden="true"
      />
    </Transition>

    <div class="flex flex-1">
      <Sidebar
        :open="sidebarOpen"
        @close="sidebarOpen = false"
        @create-guild="router.push({ path: '/guild/create'})"
        @claim-guild="router.push({ path: '/guild/claim'})"
        @search-guild="router.push({ path: '/features'})"
        @add-character="router.push('/player/add')"
      />

      <main class="flex-1 overflow-auto md:pl-64">
        <header class="md:hidden sticky top-0 z-30 bg-slate-950/80 backdrop-blur border-b border-white/10 px-4 py-3">
          <button
            @click="sidebarOpen = true"
            class="inline-flex h-9 w-9 items-center justify-center rounded-xl ring-1 ring-inset ring-white/10 hover:bg-white/5 focus-visible:ring-2 focus-visible:ring-indigo-300 transition"
            aria-label="Ouvrir le menu"
          >
            <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M3 12h18M3 6h18M3 18h18" />
            </svg>
          </button>
        </header>
        <div class="pointer-events-none -z-10 h-0">
          <div class="relative" aria-hidden="true">
            <div
              class="absolute inset-x-0 -top-10 h-0 bg-[radial-gradient(60rem_60rem_at_50%_-10%,rgba(99,102,241,0.15),transparent_60%)]"
            />
          </div>
        </div>
        <div class="px-4 py-4 md:px-6 md:py-6 max-w-7xl mx-auto">
          <div v-if="banner.visible" class="mb-3">
            <div
              :class="[
                'rounded-xl px-4 py-3 ring-1 ring-inset',
                banner.type === 'success'
                  ? 'bg-emerald-500/10 text-emerald-200 ring-emerald-500/20'
                  : 'bg-red-500/10 text-red-200 ring-red-500/20',
              ]"
              role="status"
            >
              <div class="flex items-start gap-3">
                <span class="text-lg">{{ banner.type === 'success' ? '✅' : '⚠️' }}</span>
                <div class="flex-1">
                  <p class="text-sm">{{ banner.message }}</p>
                </div>
                <button
                  class="text-slate-300/70 hover:text-white"
                  @click="dismissBanner()"
                  aria-label="Fermer l’alerte"
                >✕</button>
              </div>
            </div>
          </div>
          <RouterView />
        </div>
      </main>
    </div>

    <footer class="border-t border-white/5">
      <div
        class="mx-auto max-w-7xl px-4 py-6 text-xs text-slate-400 flex items-center justify-between"
      >
        <span>© {{ currentYear }} GuildTracker</span>
        <div class="flex gap-4">
          <p>privacy</p>
          <p>condition</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { RouterView, useRouter } from 'vue-router'
import Sidebar from '@/components/layout/Sidebar.vue'
import { useUserStore } from '@/stores/userStore'
import { checkAuthStatus } from '@/services/auth'

const router = useRouter()
const userStore = useUserStore()
const sidebarOpen = ref(false)
const currentYear = computed(() => new Date().getFullYear())

type Banner = { visible: boolean; type: 'success' | 'error'; message: string }
const banner = ref<Banner>({ visible: false, type: 'success', message: '' })

function showBanner(type: 'success' | 'error', message: string) {
  banner.value = { visible: true, type, message }
}
function dismissBanner() {
  banner.value.visible = false
}

function checkOAuthParams() {
  try {
    const url = new URL(window.location.href)
    const params = url.searchParams
    const linked = params.get('linked')
    const reason = params.get('reason')

    if (linked === 'blizzard') {
      showBanner('success', 'Votre compte Blizzard a été lié avec succès.')
    }
    if (reason) {
      const map: Record<string, string> = {
        bnet_token: 'Erreur de connexion à Battle.net. Veuillez réessayer.',
        bnet_profile: 'Impossible de récupérer le profil Battle.net.',
        auth_failed: "Échec d’authentification. Veuillez réessayer.",
      }
      const msg = map[reason] ?? 'Une erreur est survenue.'
      showBanner('error', msg)
    }

    if (linked || reason) {
      params.delete('linked')
      params.delete('reason')
      window.history.replaceState({}, '', url.pathname + (params.toString() ? '?' + params.toString() : '') + url.hash)
    }
  } catch {}
}

onMounted(async () => {
  userStore.setLoading(true)
  try {
    const a = await checkAuthStatus()
    if (a.isAuthenticated) userStore.setUser(a.user)
    else userStore.logout()
  } catch {
    userStore.logout()
  }
  checkOAuthParams()
})
</script>
