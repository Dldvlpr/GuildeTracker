<template>
  <div
    class="min-h-[100svh] flex flex-col bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950 text-slate-100 selection:bg-indigo-600/30 selection:text-white"
  >
    <Topbar />

    <AppToaster :items="toasts" />
    <main class="flex-1 overflow-auto">
      <div class="pointer-events-none -z-10 h-0">
        <div class="relative" aria-hidden="true">
          <div
            class="absolute inset-x-0 -top-10 h-0 bg-[radial-gradient(60rem_60rem_at_50%_-10%,rgba(99,102,241,0.15),transparent_60%)]"
          />
        </div>
      </div>
      <div class="px-4 py-4 md:px-6 md:py-6 mx-auto" style="max-width: 1920px;">
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
                aria-label="Fermer l'alerte"
              >✕</button>
            </div>
          </div>
        </div>
        <RouterView />
      </div>
    </main>

    <footer class="border-t border-white/5">
      <div
        class="mx-auto px-4 py-6 text-xs text-slate-400 flex items-center justify-between"
        style="max-width: 1920px;"
      >
        <span>© {{ currentYear }} GuildForge</span>
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
import Topbar from '@/components/layout/Topbar.vue'
import { useUserStore } from '@/stores/userStore'
import { invalidateBlizzardCharactersCache } from '@/services/blizzard.service'
import { checkAuthStatus } from '@/services/auth'
import AppToaster from '@/components/Toaster.vue'

const userStore = useUserStore()
const router = useRouter()
const currentYear = computed(() => new Date().getFullYear())

type Banner = { visible: boolean; type: 'success' | 'error'; message: string }
const banner = ref<Banner>({ visible: false, type: 'success', message: '' })
type ToastType = 'success' | 'error' | 'warning' | 'info'
type Toast = { id: string; message: string; type: ToastType }
const toasts = ref<Toast[]>([])

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
      try { localStorage.setItem('justLinkedBlizzard', '1') } catch {}
      try { invalidateBlizzardCharactersCache() } catch {}
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

  if (userStore.isAuthenticated) {
    try {
      const redirect = localStorage.getItem('postLoginRedirect')
      if (redirect) {
        localStorage.removeItem('postLoginRedirect')
        if (location.pathname + location.search !== redirect) {
          router.replace(redirect)
        }
      }
    } catch {}
  }

  window.addEventListener('app:toast', (e: Event) => {
    const ev = e as CustomEvent<{ type?: ToastType; message: string; timeout?: number }>
    const t: Toast = {
      id: Math.random().toString(36).slice(2),
      message: ev.detail?.message || '',
      type: ev.detail?.type || 'info',
    }
    toasts.value.push(t)
    const ttl = ev.detail?.timeout ?? 3000
    window.setTimeout(() => {
      toasts.value = toasts.value.filter(x => x.id !== t.id)
    }, ttl)
  })
})
</script>
