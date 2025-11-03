<template>
  <aside
    class="flex flex-col h-screen w-64 bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950 text-slate-100 border-r border-white/10 backdrop-blur transform transition-transform duration-200"
    :class="[
      'fixed inset-y-0 left-0 z-50',
      open ? 'translate-x-0' : '-translate-x-full md:translate-x-0',
    ]"
  >
    <!-- Header avec logo -->
    <div class="px-4 py-4 border-b border-white/10 flex items-center justify-between">
      <RouterLink
        :to="{ name: 'home', query: { stay: 'true' } }"
        class="inline-flex items-center gap-2.5 font-bold tracking-tight group"
        @click="$emit('close')"
      >
        <span class="inline-grid place-items-center h-9 w-9 rounded-xl bg-gradient-to-br from-indigo-600 to-indigo-500 shadow-lg shadow-indigo-600/30 group-hover:shadow-indigo-600/50 transition-shadow">
          <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
          </svg>
        </span>
        <span class="text-lg bg-gradient-to-r from-white to-slate-300 bg-clip-text text-transparent">GuildTracker</span>
      </RouterLink>
      <button
        class="md:hidden inline-flex h-9 w-9 items-center justify-center rounded-xl ring-1 ring-inset ring-white/10 hover:bg-white/5 focus-visible:ring-2 focus-visible:ring-indigo-300 transition"
        @click="$emit('close')"
        aria-label="Close menu"
      >
        <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
        </svg>
      </button>
    </div>

    <!-- User Profile Section (when authenticated) -->
    <div v-if="userStore.isAuthenticated && !userStore.isLoading" class="px-4 py-3 border-b border-white/10">
      <div class="flex items-center gap-3">
        <div class="relative">
          <div class="h-10 w-10 rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-600 ring-2 ring-white/10 overflow-hidden flex-shrink-0">
            <img
              v-if="userStore.user?.avatar"
              :src="userStore.user.avatar"
              :alt="userStore.user.username"
              class="h-full w-full object-cover"
            />
            <svg v-else class="h-full w-full p-2 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
          </div>
          <div v-if="userStore.user?.blizzardLinked" class="absolute -bottom-0.5 -right-0.5 h-3.5 w-3.5 rounded-full bg-emerald-500 ring-2 ring-slate-950" title="Blizzard account linked"></div>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-semibold text-white truncate">{{ userStore.user?.username || 'User' }}</p>
          <p class="text-[11px] text-slate-400">
            <span v-if="userStore.user?.blizzardLinked" class="text-emerald-400">Battle.net linked</span>
            <span v-else>Discord only</span>
          </p>
        </div>
      </div>
    </div>

    <nav v-if="userStore.isAuthenticated && !userStore.isLoading"
      class="flex-1 overflow-y-auto py-4 custom-scrollbar">
      <!-- Section Guilds -->
      <div class="px-4 pb-3 flex items-center justify-between">
        <h2 class="text-[11px] uppercase tracking-wider font-semibold text-slate-400">My Guilds</h2>
        <div class="relative">
          <button
            @click.stop="showGuildMenu = !showGuildMenu"
            class="inline-flex items-center justify-center h-7 w-7 rounded-lg bg-indigo-500/15 ring-1 ring-inset ring-indigo-400/30 hover:bg-indigo-500/25 hover:ring-indigo-400/50 cursor-pointer transition group"
            title="Add Guild"
          >
            <svg class="w-4 h-4 text-indigo-300 group-hover:text-indigo-200 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
          </button>

          <!-- Dropdown Menu -->
          <Transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
          >
            <div
              v-if="showGuildMenu"
              v-click-outside="closeGuildMenu"
              @click.stop
              class="absolute right-0 top-9 w-56 rounded-xl border border-white/10 bg-slate-900/95 backdrop-blur shadow-xl ring-1 ring-black/10 z-50"
            >
              <div class="p-1.5">
                <button
                  @click="handleClaimGuild"
                  class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-left hover:bg-white/5 transition group"
                >
                  <div class="h-8 w-8 rounded-lg bg-blue-500/15 ring-1 ring-inset ring-blue-400/30 grid place-items-center flex-shrink-0">
                    <svg class="w-4 h-4 text-blue-300" fill="currentColor" viewBox="0 0 24 24">
                      <path d="M11.93 1.93c-.51-.51-1.35-.51-1.86 0L2.21 9.79c-.59.59-.59 1.54 0 2.12l7.86 7.86c.51.51 1.35.51 1.86 0l7.86-7.86c.59-.59.59-1.54 0-2.12l-7.86-7.86zm.07 3.44l4.68 4.68-4.68 4.68-4.68-4.68L12 5.37z"/>
                    </svg>
                  </div>
                  <div class="flex-1">
                    <div class="text-white group-hover:text-indigo-300 transition">Claim from Blizzard</div>
                    <div class="text-xs text-slate-400">Import via Battle.net</div>
                  </div>
                </button>
                
              </div>
            </div>
          </Transition>
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

    </nav>

    <!-- Footer Actions (only for authenticated users) -->
    <div v-if="userStore.isAuthenticated && !userStore.isLoading" class="px-4 py-4 border-t border-white/10 space-y-2.5">
      <!-- Link Blizzard Button (if not linked) -->
      <button
        v-if="!userStore.user?.blizzardLinked"
        @click="linkBlizzard"
        class="w-full inline-flex items-center justify-center gap-2.5 px-4 py-2.5 rounded-xl text-sm font-semibold bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-500 hover:to-blue-600 text-white shadow-lg shadow-blue-600/30 transition group"
      >
        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
          <path d="M11.93 1.93c-.51-.51-1.35-.51-1.86 0L2.21 9.79c-.59.59-.59 1.54 0 2.12l7.86 7.86c.51.51 1.35.51 1.86 0l7.86-7.86c.59-.59.59-1.54 0-2.12l-7.86-7.86zm.07 3.44l4.68 4.68-4.68 4.68-4.68-4.68L12 5.37z"/>
        </svg>
        Link Battle.net
      </button>

      <!-- Logout Button -->
      <button
        @click="logoutWithDiscord"
        class="w-full inline-flex items-center justify-center gap-2.5 px-4 py-2.5 rounded-xl text-sm font-medium text-slate-300 ring-1 ring-inset ring-white/10 hover:bg-white/5 hover:text-white transition group"
      >
        <svg class="w-4 h-4 group-hover:rotate-12 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
        </svg>
        Logout
      </button>
    </div>
  </aside>
</template>

<script setup lang="ts">
import type { GameGuild } from '@/interfaces/GameGuild.interface.ts'

defineOptions({ name: 'AppSidebar' })
import { onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { redirectToDiscordAuth, redirectToBlizzardAuth, logoutUser } from '@/services/auth'
import { useUserStore } from '@/stores/userStore'
import { getMyGuild } from '@/services/gameGuild.service.ts'

// Directive v-click-outside
interface HTMLElementWithClickOutside extends HTMLElement {
  clickOutsideEvent?: (event: Event) => void
}

const vClickOutside = {
  mounted(el: HTMLElementWithClickOutside, binding: any) {
    el.clickOutsideEvent = (event: Event) => {
      if (!(el === event.target || el.contains(event.target as Node))) {
        binding.value()
      }
    }
    document.addEventListener('click', el.clickOutsideEvent)
  },
  unmounted(el: HTMLElementWithClickOutside) {
    if (el.clickOutsideEvent) {
      document.removeEventListener('click', el.clickOutsideEvent)
    }
  },
}

defineEmits(['close'])
const { open } = defineProps<{ open: boolean }>()
const loading = ref(true)
const router = useRouter()
const userStore = useUserStore()
const error = ref<string | null>(null)
const gameGuilds = ref<GameGuild[]>([])
const showGuildMenu = ref(false)

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

function closeGuildMenu() {
  showGuildMenu.value = false
}

function handleClaimGuild() {
  closeGuildMenu()
  router.push({ name: 'guildClaim' })
}

function handleCreateGuild() {
  closeGuildMenu()
  router.push({ name: 'guildCreate' })
}

function linkBlizzard() {
  redirectToBlizzardAuth()
}

async function logoutWithDiscord() {
  const result = await logoutUser()
  if (result.success) {
    userStore.logout()
    await router.push({ name: 'home', query: { stay: 'true' } })
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

/* Custom Scrollbar */
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(99, 102, 241, 0.3);
  border-radius: 3px;
  transition: background 0.2s;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(99, 102, 241, 0.5);
}

/* Firefox */
.custom-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: rgba(99, 102, 241, 0.3) transparent;
}
</style>
