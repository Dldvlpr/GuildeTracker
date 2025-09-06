<template>
  <aside
    class="flex flex-col h-full w-64 bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950 text-slate-100 border-r border-white/10 backdrop-blur supports-[backdrop-filter]:bg-slate-950/70 transform transition-transform duration-200"
    :class="[
      'fixed inset-y-0 left-0 z-50',
      open ? 'translate-x-0' : '-translate-x-full md:translate-x-0'
    ]"
  >
    <div class="px-4 py-4 border-b border-white/10 flex items-center justify-between">
      <RouterLink to="/" class="group inline-flex items-center gap-2 font-semibold tracking-tight">
        <span class="inline-grid place-items-center h-9 w-9 rounded-xl"><img src="@/assets/image/logo.png">️</span>
        <span class="text-lg group-hover:text-white">Guilde Tracker</span>
      </RouterLink>
      <button
        class="md:hidden inline-flex h-9 w-9 items-center justify-center rounded-xl ring-1 ring-inset ring-white/10 hover:bg-white/5 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-300 transition"
        @click="$emit('close')"
        aria-label="Fermer le menu"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
        </svg>
      </button>
    </div>

    <div class="px-4 py-3 border-b border-white/10">
      <div v-if="userStore.isLoading" class="text-sm text-slate-400">Chargement…</div>

      <div v-else-if="userStore.isAuthenticated" class="flex items-center gap-3">
        <div class="h-10 w-10 rounded-xl bg-white/5 ring-1 ring-inset ring-white/10 grid place-items-center text-sm font-semibold">
          {{ initials }}
        </div>
        <div class="min-w-0">
          <p class="text-sm font-medium truncate">{{ userStore.user?.username || 'Membre' }}</p>
        </div>
      </div>

      <div v-else class="text-sm text-slate-400">
        Non connecté
      </div>
    </div>

    <nav class="flex-1 overflow-y-auto py-3">
      <div aria-hidden="true" class="pointer-events-none px-3">
        <div class="h-0 relative">
          <div class="absolute inset-x-0 -top-2 h-0 bg-[radial-gradient(40rem_40rem_at_10%_-20%,rgba(99,102,241,0.15),transparent_60%)]"></div>
        </div>
      </div>

      <ul v-if="!userStore.isLoading && userStore.isAuthenticated" class="px-2 space-y-1">
        <li>
          <RouterLink to="/guild" v-slot="{ isActive }" class="block">
            <span
              :class="[
                'flex items-center gap-3 px-3 py-2 rounded-xl text-sm ring-1 ring-inset transition',
                isActive
                  ? 'bg-indigo-600/20 text-white ring-white/10 shadow-inner shadow-indigo-600/20'
                  : 'bg-white/0 text-slate-200 ring-white/10 hover:bg-white/5 hover:text-white'
              ]"
            >
              <span class="grid place-items-center h-6 w-6 rounded-lg bg-white/5 ring-1 ring-inset ring-white/10">➕</span>
              Guilde
            </span>
          </RouterLink>
        </li>
        <li>
          <RouterLink to="/add" v-slot="{ isActive }" class="block">
            <span
              :class="[
                'flex items-center gap-3 px-3 py-2 rounded-xl text-sm ring-1 ring-inset transition',
                isActive
                  ? 'bg-indigo-600/20 text-white ring-white/10 shadow-inner shadow-indigo-600/20'
                  : 'bg-white/0 text-slate-200 ring-white/10 hover:bg-white/5 hover:text-white'
              ]"
            >
              <span class="grid place-items-center h-6 w-6 rounded-lg bg-white/5 ring-1 ring-inset ring-white/10">➕</span>
              Ajouter un joueur
            </span>
          </RouterLink>
        </li>
       <li>
          <RouterLink to="/list" v-slot="{ isActive }" class="block">
            <span
              :class="[
                'flex items-center gap-3 px-3 py-2 rounded-xl text-sm ring-1 ring-inset transition',
                isActive
                  ? 'bg-indigo-600/20 text-white ring-white/10 shadow-inner shadow-indigo-600/20'
                  : 'bg-white/0 text-slate-200 ring-white/10 hover:bg-white/5 hover:text-white'
              ]"
            >
              <span class="grid place-items-center h-6 w-6 rounded-lg bg-white/5 ring-1 ring-inset ring-white/10">📋</span>
              Liste des joueurs
            </span>
          </RouterLink>
        </li>
      </ul>

      <div v-else-if="!userStore.isLoading" class="px-2 space-y-2">
        <button
          @click="loginWithDiscord"
          class="w-full inline-flex items-center gap-3 px-3 py-2 rounded-xl text-sm bg-white/0 text-slate-200 ring-1 ring-inset ring-white/10 hover:bg-white/5 hover:text-white transition"
        >
          <span class="grid place-items-center h-6 w-6 rounded-lg bg-white/5 ring-1 ring-inset ring-white/10">🔑</span>
          Connexion
        </button>
      </div>
    </nav>

    <div class="px-4 py-4 border-t border-white/10 space-y-3">
      <a
        href="https://github.com/dldvlpr"
        target="_blank"
        rel="noopener noreferrer"
        class="flex items-center gap-2 text-sm rounded-xl px-3 py-2 ring-1 ring-inset ring-white/10 hover:bg-white/5 hover:text-white transition"
      >
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
          <path fill-rule="evenodd" clip-rule="evenodd"
                d="M12 0C5.373 0 0 5.373 0 12c0 5.303 3.438 9.8 8.205 11.387.6.111.82-.261.82-.58 0-.287-.01-1.045-.016-2.05-3.338.726-4.042-1.61-4.042-1.61-.546-1.387-1.333-1.756-1.333-1.756-1.09-.745.082-.73.082-.73 1.205.085 1.84 1.237 1.84 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.76-1.605-2.665-.304-5.467-1.332-5.467-5.93 0-1.31.468-2.38 1.235-3.22-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.3 1.23a11.52 11.52 0 013.003-.404 11.5 11.5 0 013.003.404c2.29-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.233 1.91 1.233 3.22 0 4.61-2.807 5.625-5.48 5.921.43.372.814 1.102.814 2.222 0 1.606-.015 2.903-.015 3.293 0 .32.218.695.825.577C20.565 21.796 24 17.299 24 12c0-6.627-5.373-12-12-12z"
          />
        </svg>
        <span>GitHub</span>
      </a>

      <button
        v-if="userStore.isAuthenticated && !userStore.isLoading"
        @click="logoutWithDiscord"
        class="w-full inline-flex items-center gap-3 px-3 py-2 rounded-xl text-sm bg-white/0 text-slate-200 ring-1 ring-inset ring-white/10 hover:bg-white/5 hover:text-white transition"
      >
        <span class="grid place-items-center h-6 w-6 rounded-lg bg-white/5 ring-1 ring-inset ring-white/10">🚪</span>
        Déconnexion
      </button>
    </div>
  </aside>
</template>

<script setup lang="ts">
defineOptions({ name: 'AppSidebar' })
import { computed } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { redirectToDiscordAuth, logoutUser } from '@/services/auth'
import { useUserStore } from '@/stores/userStore'

const router = useRouter()
const { open } = defineProps<{ open: boolean }>()
defineEmits(['close'])
const userStore = useUserStore()

const initials = computed(() => {
  const name = userStore.user?.username || ''
  const parts = name.split(' ').filter(Boolean)
  const first = parts[0]?.[0] || name[0] || 'U'
  const second = parts[1]?.[0] || ''
  return (first + second).toUpperCase()
})

function loginWithDiscord() {
  redirectToDiscordAuth()
}

async function logoutWithDiscord() {
    const result = await logoutUser()
    if (result.success) {
      userStore.logout()
      await router.push({ name: 'home' })
    }
}
</script>

<style scoped>
aside::after {
  content: "";
  position: absolute;
  top: 0; right: -1px; bottom: 0; width: 1px;
  background: linear-gradient(to bottom, rgba(255,255,255,0.1), rgba(255,255,255,0.02), rgba(255,255,255,0.1));
  pointer-events: none;
}
</style>
