<template>
  <header class="sticky top-0 z-50 border-b border-white/10 bg-slate-950/80 backdrop-blur-xl">
    <nav class="mx-auto flex h-16 max-w-[1920px] items-center justify-between px-4 md:px-6">
      <div class="flex items-center gap-4">
        <RouterLink
          :to="{ name: 'home', query: { stay: 'true' } }"
          class="inline-flex items-center gap-2.5 font-bold tracking-tight group"
        >
          <div class="h-10 w-10 rounded-lg bg-gradient-to-br from-orange-500 to-orange-700 p-1.5 shadow-lg shadow-orange-600/30 group-hover:shadow-orange-600/50 transition-all group-hover:scale-105">
            <img
              src="@/assets/image/logo-icon.svg"
              alt="GuildForge"
              class="h-full w-full object-contain"
            />
          </div>
          <span class="hidden sm:inline text-lg font-bold bg-gradient-to-r from-orange-400 via-yellow-300 to-orange-500 bg-clip-text text-transparent group-hover:from-orange-300 group-hover:via-yellow-200 group-hover:to-orange-400 transition-all">
            GuildForge
          </span>
        </RouterLink>

        <div v-if="userStore.isAuthenticated && !userStore.isLoading && gameGuilds.length > 0" class="relative">
          <button
            @click.stop="showGuildDropdown = !showGuildDropdown"
            class="inline-flex items-center gap-2 h-9 px-3 rounded-lg bg-white/5 ring-1 ring-inset ring-white/10 hover:bg-white/10 hover:ring-white/20 transition text-sm font-medium"
          >
            <svg class="w-4 h-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
            <span class="text-white hidden md:inline">Guilds</span>
            <span class="inline-flex items-center justify-center h-5 w-5 rounded-md bg-indigo-500/20 text-indigo-300 text-xs font-semibold">
              {{ gameGuilds.length }}
            </span>
          </button>

          <Transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
          >
            <div
              v-if="showGuildDropdown"
              v-click-outside="closeGuildDropdown"
              @click.stop
              class="absolute left-0 top-12 w-80 rounded-xl border border-white/10 bg-slate-900/95 backdrop-blur shadow-2xl ring-1 ring-black/10 z-50 max-h-96 overflow-hidden flex flex-col"
            >
              <div class="flex items-center justify-between px-4 py-3 border-b border-white/10">
                <h3 class="text-sm font-semibold text-slate-300">Your Guilds</h3>
                <button
                  @click="toggleAddGuildMenu"
                  class="inline-flex items-center justify-center h-7 w-7 rounded-lg bg-indigo-500/15 ring-1 ring-inset ring-indigo-400/30 hover:bg-indigo-500/25 hover:ring-indigo-400/50 transition"
                  title="Add Guild"
                >
                  <svg class="w-4 h-4 text-indigo-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                  </svg>
                </button>
              </div>

              <div class="overflow-y-auto p-2 custom-scrollbar">
                <RouterLink
                  v-for="guild in gameGuilds"
                  :key="guild.id"
                  :to="guildRoute(guild)"
                  @click="closeGuildDropdown"
                  class="flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-white/5 transition group"
                >
                  <span class="h-8 w-8 rounded-lg bg-gradient-to-br from-indigo-500 to-indigo-600 grid place-items-center font-semibold text-sm flex-shrink-0">
                    {{ makeInitials(guild.name) }}
                  </span>
                  <div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-white truncate group-hover:text-indigo-300 transition">{{ guild.name }}</div>
                    <div class="text-xs text-slate-400">{{ guild.nbrGuildMembers || 0 }} members</div>
                  </div>
                  <svg class="w-4 h-4 text-slate-500 group-hover:text-indigo-400 transition flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                  </svg>
                </RouterLink>
              </div>
            </div>
          </Transition>

          <Transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
          >
            <div
              v-if="showAddGuildMenu"
              v-click-outside="closeAddGuildMenu"
              @click.stop
              class="absolute left-0 top-12 w-64 rounded-xl border border-white/10 bg-slate-900/95 backdrop-blur shadow-2xl ring-1 ring-black/10 z-50"
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
                <button
                  @click="handleCreateGuild"
                  class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-left hover:bg-white/5 transition group"
                >
                  <div class="h-8 w-8 rounded-lg bg-white/5 ring-1 ring-inset ring-white/10 grid place-items-center flex-shrink-0">
                    <svg class="w-4 h-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                  </div>
                  <div class="flex-1">
                    <div class="text-white group-hover:text-indigo-300 transition">Create Manually</div>
                    <div class="text-xs text-slate-400">Custom guild setup</div>
                  </div>
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </div>

      <div v-if="userStore.isAuthenticated && !userStore.isLoading" class="flex items-center gap-3">
        <button
          v-if="!userStore.user?.blizzardLinked"
          @click="linkBlizzard"
          class="hidden lg:inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-medium bg-blue-600/15 text-blue-300 ring-1 ring-inset ring-blue-400/30 hover:bg-blue-600/25 hover:ring-blue-400/50 transition"
        >
          <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24">
            <path d="M11.93 1.93c-.51-.51-1.35-.51-1.86 0L2.21 9.79c-.59.59-.59 1.54 0 2.12l7.86 7.86c.51.51 1.35.51 1.86 0l7.86-7.86c.59-.59.59-1.54 0-2.12l-7.86-7.86zm.07 3.44l4.68 4.68-4.68 4.68-4.68-4.68L12 5.37z"/>
          </svg>
          Link Battle.net
        </button>

        <div class="relative">
          <button
            @click.stop="showUserMenu = !showUserMenu"
            class="inline-flex items-center gap-2 h-9 px-2 pr-3 rounded-lg bg-white/5 ring-1 ring-inset ring-white/10 hover:bg-white/10 hover:ring-white/20 transition"
          >
            <div class="relative">
              <div class="h-7 w-7 rounded-lg bg-gradient-to-br from-indigo-500 to-indigo-600 overflow-hidden flex-shrink-0">
                <img
                  v-if="userStore.user?.avatar"
                  :src="userStore.user.avatar"
                  :alt="userStore.user.username"
                  class="h-full w-full object-cover"
                />
                <svg v-else class="h-full w-full p-1.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                </svg>
              </div>
              <div v-if="userStore.user?.blizzardLinked" class="absolute -bottom-0.5 -right-0.5 h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-slate-950" title="Blizzard account linked"></div>
            </div>
            <span class="hidden sm:inline text-sm font-medium text-white">{{ userStore.user?.username || 'User' }}</span>
            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
          </button>

          <Transition
            enter-active-class="transition duration-100 ease-out"
            enter-from-class="opacity-0 scale-95"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition duration-75 ease-in"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-95"
          >
            <div
              v-if="showUserMenu"
              v-click-outside="closeUserMenu"
              @click.stop
              class="absolute right-0 top-12 w-64 rounded-xl border border-white/10 bg-slate-900/95 backdrop-blur shadow-2xl ring-1 ring-black/10 z-50"
            >
              <div class="p-3 border-b border-white/10">
                <div class="text-sm font-semibold text-white">{{ userStore.user?.username || 'User' }}</div>
                <div class="text-xs text-slate-400 mt-0.5">
                  <span v-if="userStore.user?.blizzardLinked" class="text-emerald-400">Battle.net linked</span>
                  <span v-else>Discord only</span>
                </div>
              </div>
              <div class="p-1.5">
                <button
                  v-if="!userStore.user?.blizzardLinked"
                  @click="linkBlizzard"
                  class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-left hover:bg-white/5 transition group"
                >
                  <svg class="w-4 h-4 text-blue-300" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M11.93 1.93c-.51-.51-1.35-.51-1.86 0L2.21 9.79c-.59.59-.59 1.54 0 2.12l7.86 7.86c.51.51 1.35.51 1.86 0l7.86-7.86c.59-.59.59-1.54 0-2.12l-7.86-7.86zm.07 3.44l4.68 4.68-4.68 4.68-4.68-4.68L12 5.37z"/>
                  </svg>
                  <span class="text-white group-hover:text-indigo-300 transition">Link Battle.net</span>
                </button>
                <button
                  @click="logoutWithDiscord"
                  class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-left hover:bg-red-500/10 text-slate-300 hover:text-red-300 transition group"
                >
                  <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                  </svg>
                  <span>Logout</span>
                </button>
              </div>
            </div>
          </Transition>
        </div>
      </div>

      <div v-else class="flex items-center gap-3">
        <button
          @click="handleDiscordLogin"
          class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold bg-indigo-500/20 text-indigo-100 ring-1 ring-inset ring-indigo-400/40 hover:bg-indigo-500/30 hover:ring-indigo-400/60 transition"
        >
          <svg class="w-4 h-4" viewBox="0 0 127.14 96.36" xmlns="http://www.w3.org/2000/svg">
            <path
              fill="currentColor"
              d="M107.7,8.07A105.15,105.15,0,0,0,82.3,0a72.06,72.06,0,0,0-3.36,6.83A97.68,97.68,0,0,0,48.84,6.83,72.37,72.37,0,0,0,45.48,0,105.89,105.89,0,0,0,20.05,8.09C4.26,31.44-2,54.23,1.3,76.72A105.73,105.73,0,0,0,33.71,96.36c2.69-3.25,5.11-6.71,7.2-10.34a68.42,68.42,0,0,1-10.49-5c.88-.63,1.74-1.3,2.57-2a75.57,75.57,0,0,0,65,0c.83.71,1.69,1.38,2.57,2a68.68,68.68,0,0,1-10.51,5c2.09,3.63,4.5,7.09,7.2,10.34A105.25,105.25,0,0,0,125.84,76.72C129.54,51,122.09,28.29,107.7,8.07ZM42.45,65.69c-6.36,0-11.61-5.83-11.61-13s5.08-13,11.61-13S54.16,45.42,54,52.67,48.81,65.69,42.45,65.69Zm42.24,0c-6.36,0-11.61-5.83-11.61-13s5.08-13,11.61-13,11.72,5.79,11.61,13S91.05,65.69,84.69,65.69Z"
            />
          </svg>
          Se connecter
        </button>
      </div>

    </nav>
  </header>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { redirectToDiscordAuth, redirectToBlizzardAuth, logoutUser } from '@/services/auth'
import { useUserStore } from '@/stores/userStore'
import { getMyGuild } from '@/services/gameGuild.service'
import type { GameGuild } from '@/interfaces/GameGuild.interface'
import { resolvePostLoginTarget, DEFAULT_POST_LOGIN_REDIRECT } from '@/utils/redirect'

const router = useRouter()
const userStore = useUserStore()
const gameGuilds = ref<GameGuild[]>([])
const showGuildDropdown = ref(false)
const showAddGuildMenu = ref(false)
const showUserMenu = ref(false)
const loading = ref(true)
const error = ref<string | null>(null)

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

function closeGuildDropdown() {
  showGuildDropdown.value = false
  showAddGuildMenu.value = false
}

function closeAddGuildMenu() {
  showAddGuildMenu.value = false
}

function closeUserMenu() {
  showUserMenu.value = false
}

function toggleAddGuildMenu() {
  showAddGuildMenu.value = !showAddGuildMenu.value
}

function handleClaimGuild() {
  closeGuildDropdown()
  router.push({ name: 'guildClaim' })
}

function handleCreateGuild() {
  closeGuildDropdown()
  router.push({ name: 'guildCreate' })
}

function handleDiscordLogin() {
  const redirectParam = router.currentRoute.value.query?.redirect as string | undefined
  const fallback = router.currentRoute.value.fullPath || DEFAULT_POST_LOGIN_REDIRECT
  const target = resolvePostLoginTarget(redirectParam, fallback)
  redirectToDiscordAuth(target)
}

function linkBlizzard() {
  closeUserMenu()
  redirectToBlizzardAuth()
}

async function logoutWithDiscord() {
  closeUserMenu()
  const result = await logoutUser()
  if (result.success) {
    userStore.logout()
    await router.push({ name: 'home', query: { stay: 'true' } })
  }
}
</script>

<style scoped>
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

.custom-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: rgba(99, 102, 241, 0.3) transparent;
}
</style>
