<template>
  <section class="min-h-[60vh] flex items-center justify-center px-4 py-16">
    <div class="w-full max-w-md rounded-2xl border border-white/10 bg-white/5 backdrop-blur px-8 py-10 text-center">
      <div
        class="inline-flex h-16 w-16 items-center justify-center rounded-2xl"
        :class="state === 'success' ? 'bg-emerald-500/20 text-emerald-200' : state === 'error' ? 'bg-red-500/20 text-red-200' : 'bg-indigo-500/20 text-indigo-200'"
      >
        <svg v-if="state === 'pending'" class="h-7 w-7 animate-spin" fill="none" viewBox="0 0 24 24">
          <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
          <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
        </svg>
        <span v-else-if="state === 'success'" class="text-2xl">✅</span>
        <span v-else class="text-2xl">⚠️</span>
      </div>

      <h1 class="mt-6 text-2xl font-semibold text-white">
        {{ linkedService === 'blizzard' ? 'Connexion Battle.net' : 'Connexion Discord' }}
      </h1>
      <p class="mt-3 text-sm text-slate-300">{{ message }}</p>

      <div v-if="state === 'error'" class="mt-6 flex flex-col gap-3">
        <button
          @click="retry"
          class="inline-flex items-center justify-center gap-2 rounded-lg bg-white/10 px-4 py-2 text-sm font-semibold text-white hover:bg-white/20 transition"
        >
          Réessayer
        </button>
        <RouterLink
          :to="{ name: 'home', query: { stay: 'true' } }"
          class="text-sm text-slate-400 hover:text-white transition"
        >
          Retour à l’accueil
        </RouterLink>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref, computed } from 'vue'
import { useRouter, useRoute, RouterLink } from 'vue-router'
import { useUserStore } from '@/stores/userStore'
import { resolvePostLoginTarget, DEFAULT_POST_LOGIN_REDIRECT } from '@/utils/redirect'

type State = 'pending' | 'success' | 'error'

const router = useRouter()
const route = useRoute()
const userStore = useUserStore()
const API_BASE = import.meta.env.VITE_API_BASE_URL as string

const state = ref<State>('pending')
const message = ref('Connexion en cours…')
let redirectTimer: number | null = null
const linkedService = computed<'discord' | 'blizzard'>(() => {
  const linked = (route.query.linked as string | undefined)?.toLowerCase()
  return linked === 'blizzard' ? 'blizzard' : 'discord'
})
const returnTarget = computed(() =>
  resolvePostLoginTarget(route.query.returnTo as string | undefined, DEFAULT_POST_LOGIN_REDIRECT),
)

async function finishHandshake() {
  state.value = 'pending'
  message.value = linkedService.value === 'blizzard'
    ? 'Validation de votre compte Battle.net…'
    : 'Vérification de votre session Discord…'

  try {
    await userStore.initFromApi(API_BASE)
    if (userStore.isAuthenticated) {
      state.value = 'success'
      message.value = linkedService.value === 'blizzard'
        ? 'Compte Battle.net mis à jour. Redirection en cours…'
        : 'Connexion confirmée. Redirection en cours…'
      window.dispatchEvent(new CustomEvent('app:toast', {
        detail: {
          type: 'success',
          message: linkedService.value === 'blizzard'
            ? 'Battle.net est désormais lié à votre profil.'
            : 'Connexion Discord réussie.',
        },
      }))
      const target = returnTarget.value
      redirectTimer = window.setTimeout(() => {
        router.replace(target)
      }, 600)
      return
    }
    state.value = 'error'
    message.value = 'Impossible de confirmer la connexion. Merci de relancer la procédure.'
  } catch {
    state.value = 'error'
    message.value = 'Erreur réseau pendant la vérification. Réessayez dans un instant.'
  }
}

function retry() {
  if (redirectTimer) {
    window.clearTimeout(redirectTimer)
    redirectTimer = null
  }
  finishHandshake()
}

onMounted(() => {
  finishHandshake()
})

onUnmounted(() => {
  if (redirectTimer) {
    window.clearTimeout(redirectTimer)
  }
})
</script>
