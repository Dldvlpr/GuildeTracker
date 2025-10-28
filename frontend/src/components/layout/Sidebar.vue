<template>
  <aside
    class="flex flex-col h-screen w-64 bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950 text-slate-100 border-r border-white/10 backdrop-blur transform transition-transform duration-200"
    :class="[
      'fixed inset-y-0 left-0 z-50',
      open ? 'translate-x-0' : '-translate-x-full md:translate-x-0',
    ]"
  >
    <div class="px-4 py-4 border-b border-white/10 flex items-center justify-between">
      <RouterLink
        to="/"
        class="inline-flex items-center gap-2 font-semibold tracking-tight"
        @click="$emit('close')"
      >
        <span
          class="inline-grid place-items-center h-9 w-9 rounded-xl bg-indigo-600 shadow-lg shadow-indigo-600/30"
          >🛡️</span
        >
        <span class="text-lg">GuildTracker</span>
      </RouterLink>
      <button
        class="md:hidden inline-flex h-9 w-9 items-center justify-center rounded-xl ring-1 ring-inset ring-white/10 hover:bg-white/5 focus-visible:ring-2 focus-visible:ring-indigo-300 transition"
        @click="$emit('close')"
        aria-label="Fermer le menu"
      >
        <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <nav v-if="userStore.isAuthenticated && !userStore.isLoading"
      class="flex-1 overflow-y-auto py-4">
      <div class="px-4 pb-2 flex items-center justify-between">
        <h2 class="text-[11px] uppercase tracking-wide text-slate-400">Guilds</h2>
        <div class="flex items-center gap-2">
          <button
            @click="$emit('create-guild')"
            class="h-7 px-2 rounded-lg bg-white/5 ring-1 ring-inset ring-white/10 hover:bg-white/10 text-xs cursor-pointer"
          >
            ➕
          </button>
          <button
            @click="$emit('search-guild')"
            class="h-7 px-2 rounded-lg bg-white/5 ring-1 ring-inset ring-white/10 hover:bg-white/10 text-xs cursor-pointer"
          >
            🔎
          </button>
        </div>
      </div>

      <ul class="px-2 space-y-1">
        <li v-if="gameGuilds.length === 0" class="px-3 py-2 text-xs text-slate-400">
          No guilds
        </li>
        <li v-for="guild in gameGuilds" :key="guild.id">
          <RouterLink
            :to="guildRoute(guild)"
            v-slot="{ isActive }"
            class="block"
            @click="$emit('close')"
          >
            <span
              :class="[
                'flex items-center gap-3 px-3 py-2 rounded-xl text-sm ring-1 ring-inset transition',
                isActive
                  ? 'bg-indigo-600/20 text-white ring-white/10 shadow-inner shadow-indigo-600/20'
                  : 'text-slate-200 ring-white/10 hover:bg-white/5 hover:text-white',
              ]"
            >
              <span
                class="h-7 w-7 rounded-lg bg-white/5 ring-1 ring-inset ring-white/10 grid place-items-center font-semibold"
              >
                {{ makeInitials(guild.name) }}
              </span>
              <span class="truncate">{{ guild.name }}</span>
              <span v-if="guild.nbrGuildMembers != null" class="ml-auto text-[11px] text-slate-400"
                >{{ guild.nbrGuildMembers }} members</span
              >
            </span>
          </RouterLink>
        </li>
      </ul>

      <div class="mt-6 px-4 pb-2 flex items-center justify-between">
        <h2 class="text-[11px] uppercase tracking-wide text-slate-400">Characters</h2>
        <button
          @click="$emit('add-character')"
          class="h-7 px-2 rounded-lg bg-white/5 ring-1 ring-inset ring-white/10 hover:bg-white/10 text-xs"
        >
          ➕
        </button>
      </div>

      <ul class="px-2 space-y-1">
        <li v-if="characters.length === 0" class="px-3 py-2 text-xs text-slate-400">
          No characters
        </li>
        <li v-for="c in characters" :key="c.id">
          <RouterLink
            :to="characterRoute(c)"
            v-slot="{ isActive }"
            class="block"
            @click="$emit('close')"
          >
            <span
              :class="[
                'flex items-center gap-3 px-3 py-2 rounded-xl text-sm ring-1 ring-inset transition',
                isActive
                  ? 'bg-indigo-600/20 text-white ring-white/10 shadow-inner shadow-indigo-600/20'
                  : 'text-slate-200 ring-white/10 hover:bg-white/5 hover:text-white',
              ]"
            >
              <span
                class="h-7 w-7 rounded-lg bg-white/5 ring-1 ring-inset ring-white/10 grid place-items-center"
              >
                {{ c.classIcon || '🎮' }}
              </span>
              <div class="min-w-0">
                <div class="truncate">{{ c.name }}</div>
                <div class="text-[11px] text-slate-400 truncate">
                  {{ c.guildName || 'No guild' }}
                </div>
              </div>
            </span>
          </RouterLink>
        </li>
      </ul>
    </nav>

    <div class="px-4 py-4 border-t border-white/10 space-y-3">
      <button
        v-if="userStore.isAuthenticated && !userStore.isLoading"
        @click="logoutWithDiscord"
        class="w-full inline-flex items-center gap-3 px-3 py-2 rounded-xl text-sm ring-1 ring-inset ring-white/10 hover:bg-white/5 hover:text-white transition cursor-pointer"
      >
        🚪 Logout
      </button>
      <button
        v-else
        @click="loginWithDiscord"
        class="w-full inline-flex items-center gap-3 px-3 py-2 rounded-xl text-sm ring-1 ring-inset ring-white/10 hover:bg-white/5 hover:text-white transition cursor-pointer"
      >
        🔑 Login
      </button>
    </div>
  </aside>
</template>

<script setup lang="ts">
import type { GameGuild } from '@/interfaces/GameGuild.interface.ts'

defineOptions({ name: 'AppSidebar' })
import { onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { redirectToDiscordAuth, logoutUser } from '@/services/auth'
import { useUserStore } from '@/stores/userStore'
import { getMyGuild } from '@/services/gameGuild.service.ts'

defineEmits(['close', 'create-guild', 'search-guild', 'add-character'])
const { open } = defineProps<{ open: boolean }>()
const loading = ref(true)
const router = useRouter()
const userStore = useUserStore()
const error = ref<string | null>(null)
const gameGuilds = ref<GameGuild[]>([])
const characters = ref<Array<{ id: string; name: string; guildName?: string; classIcon?: string }>>(
  [],
)

onMounted(async () => {
  try {
    const res = await getMyGuild()
    if (res.ok) {
      gameGuilds.value = res.data
    } else {
      error.value = res.error
    }
  } catch (e: any) {
    error.value = e?.message ?? 'Network error'
  } finally {
    loading.value = false
  }
})

function makeInitials(name: string) {
  const p = name.split(' ').filter(Boolean)
  return ((p[0]?.[0] || 'G') + (p[1]?.[0] || '')).toUpperCase()
}

const guildRoute = (g: { id: string }) => `/guild/${encodeURIComponent(g.id)}`
const characterRoute = (c: { id: string }) => `/list`

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
  content: '';
  position: absolute;
  top: 0;
  right: -1px;
  bottom: 0;
  width: 1px;
  background: linear-gradient(
    to bottom,
    rgba(255, 255, 255, 0.1),
    rgba(255, 255, 255, 0.02),
    rgba(255, 255, 255, 0.1)
  );
  pointer-events: none;
}
</style>
