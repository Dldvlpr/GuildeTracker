<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/userStore'
import { redirectToBlizzardAuth, redirectToDiscordAuth } from '@/services/auth'
import { checkGuildExists, getMyGuild, joinGuild } from '@/services/gameGuild.service'
import {
  claimGuild as claimGuildApi,
  getBlizzardCharacterDetails,
  getBlizzardCharactersCached,
  getBlizzardCharacterGuild,
} from '@/services/blizzard.service'
import BaseModal from '@/components/ui/BaseModal.vue'
import { matchesWowFilter, type WowFilter } from '@/utils/onboarding'

const router = useRouter()
const userStore = useUserStore()
const guilds = ref<any[]>([])
const loadingGuilds = ref(false)
const shouldAutoRedirect = ref(true)
const joinOpen = ref(false)
const joinLoading = ref(false)
const joinError = ref('')
const charsLoading = ref(false)
type MyChar = { name: string; level?: number; realm: string; wowType?: string, guildName?: string, rank?: number|null }
const myCharacters = ref<Array<MyChar>>(
  [],
)
const selectedIndex = ref<number | null>(null)
const bestIndex = ref<number | null>(null)
const loadingByIndex = ref<Record<number, boolean>>({})
const MAX_CONCURRENCY = 3
const selectedGuildCheck = ref<{ exists: boolean; id?: string; name?: string } | null>(null)
const showOnlyWithGuild = ref(false)
const wowFilter = ref<WowFilter>('All')
const realmFilter = ref<string>('All')
const realmOptions = computed(() => {
  const s = new Set<string>()
  for (const c of myCharacters.value) {
    if (c.realm) s.add(c.realm)
  }
  return Array.from(s).sort((a, b) => a.localeCompare(b))
})

const isAuthenticated = computed(() => userStore.isAuthenticated)
const isUserLoading = computed(() => userStore.isLoading)
const user = computed(() => userStore.user)
const hasBlizzardLinked = computed(() => user.value?.blizzardId)
const hasGuilds = computed(() => guilds.value.length > 0)

async function fetchGuilds() {
  loadingGuilds.value = true
  try {
    const res = await getMyGuild()
    if (res.ok) {
      guilds.value = res.data || []
      if (guilds.value.length > 0 && shouldAutoRedirect.value) {
        const firstGuild = guilds.value[0]
        router.replace({ name: 'guildDetails', params: { id: firstGuild.id } })
      }
    }
  } catch (e) {
    console.error('Failed to load guilds:', e)
  } finally {
    loadingGuilds.value = false
  }
}

onMounted(async () => {
  const urlParams = new URLSearchParams(window.location.search)
  if (urlParams.get('stay') === 'true') {
    shouldAutoRedirect.value = false
    window.history.replaceState({}, '', '/')
  }

  if (isAuthenticated.value) {
    await fetchGuilds()

    try {
      const justLinked = localStorage.getItem('justLinkedBlizzard') === '1'
      if (justLinked && !hasGuilds.value && hasBlizzardLinked.value) {
        await openJoinModal()
        localStorage.removeItem('justLinkedBlizzard')
      }
    } catch {}
  }
})

watch(isAuthenticated, async (v) => {
  if (v) {
    await fetchGuilds()

    try {
      const justLinked = localStorage.getItem('justLinkedBlizzard') === '1'
      if (justLinked && !hasGuilds.value && hasBlizzardLinked.value) {
        await openJoinModal()
        localStorage.removeItem('justLinkedBlizzard')
      }
    } catch {}
  } else {
    guilds.value = []
  }
})

function handleDiscordLogin() {
  try {
    const r = (router.currentRoute.value.query.redirect as string) || '/app'
    localStorage.setItem('postLoginRedirect', r)
  } catch {}
  redirectToDiscordAuth()
}

function handleBlizzardLink() {
  try {
    localStorage.setItem('postLoginRedirect', '/guild/claim')
  } catch {}
  redirectToBlizzardAuth()
}

function handleClaimGuild() {
  router.push({ name: 'guildClaim' })
}

function handleCreateGuild() {
  router.push({ name: 'guildCreate' })
}

function handleSelectGuild(guildId: string) {
  router.push({ name: 'guildDetails', params: { id: guildId } })
}

async function openJoinModal() {
  joinOpen.value = true
  joinError.value = ''
  myCharacters.value = []
  selectedIndex.value = null
  selectedGuildCheck.value = null
  try {
    charsLoading.value = true
    const res = await getBlizzardCharactersCached()
    const chars = Array.isArray(res.characters) ? res.characters : []
    myCharacters.value = chars
      .map((c: any) => ({
        name: c.name,
        level: c.level,
        realm: c.realm?.name || c.realm?.slug || '',
        wowType: c.wow_type || 'Retail',
      }))
      .filter((c: any) => c.name && c.realm)


    async function enrichOne(i: number) {
      const c = myCharacters.value[i]
      if (!c) return
      loadingByIndex.value[i] = true
      try {
        const info = await getBlizzardCharacterGuild(c.realm, c.name, c.wowType)
        const guildName = info?.guild?.guild?.name || info?.guild?.name || null
        const rank = typeof info?.rank === 'number' ? info.rank : null
        myCharacters.value[i] = { ...c, guildName: guildName || undefined, rank }
      } catch {
        myCharacters.value[i] = { ...c, guildName: undefined, rank: null }
      } finally {
        loadingByIndex.value[i] = false
      }
    }

    const indexes = myCharacters.value.map((_, i) => i)
    let cursor = 0
    const workers: Promise<void>[] = []
    for (let w = 0; w < Math.min(MAX_CONCURRENCY, indexes.length); w++) {
      workers.push((async function run() {
        while (cursor < indexes.length) {
          const i = cursor++
          await enrichOne(i)
        }
      })())
    }
    await Promise.all(workers)


    function weight(c: MyChar) {
      if (c.rank === 0) return 0
      if (c.rank === 1) return 1
      if (c.guildName && (c.rank ?? 99) >= 2) return 2
      return 3
    }
    myCharacters.value = myCharacters.value
      .slice()
      .sort((a, b) => {
        const wa = weight(a), wb = weight(b)
        if (wa !== wb) return wa - wb
        return a.name.localeCompare(b.name)
      })

    const candidates = myCharacters.value
      .filter(c => c.guildName && (c.rank ?? 99) < 99)
      .sort((a, b) => (a.rank ?? 99) - (b.rank ?? 99))
    if (candidates.length) {
      const best = candidates[0]
      const idx = myCharacters.value.findIndex(c => c.name === best.name && c.realm === best.realm && c.wowType === best.wowType)
      if (idx >= 0) {
        bestIndex.value = idx
        await checkAndPrepare(idx)
      }
    } else {
      bestIndex.value = null
    }
  } catch (e: any) {
    joinError.value = e?.message || 'Failed to load characters'
  }
  finally {
    charsLoading.value = false
  }
}

async function checkAndPrepare(i: number) {
  selectedIndex.value = i
  selectedGuildCheck.value = null
  joinError.value = ''
  try {
    const c = myCharacters.value[i]
    if (!c) return
    if (!c.guildName) {

      loadingByIndex.value[i] = true
      try {
        const info = await getBlizzardCharacterGuild(c.realm, c.name, c.wowType)
        const g = info?.guild
        const name = g?.guild?.name || g?.name
        if (name) {
          myCharacters.value[i].guildName = name
          myCharacters.value[i].rank = typeof info?.rank === 'number' ? info.rank : null
        }
      } finally {
        loadingByIndex.value[i] = false
      }
    }
    if (!myCharacters.value[i].guildName) {
      joinError.value = 'This character is not in a guild.'
      return
    }
    selectedGuildCheck.value = await checkGuildExists(c.realm, myCharacters.value[i].guildName!)
  } catch (e: any) {
    joinError.value = e?.message || 'Failed to resolve character guild'
  }
}

function rankLabel(rank: number | null | undefined) {
  if (rank === 0) return 'GM'
  if (rank === 1) return 'Officer'
  if (rank == null) return 'Unknown'
  return 'Member'
}

function rankClass(rank: number | null | undefined) {
  if (rank === 0) return 'bg-emerald-500/15 text-emerald-300 ring-emerald-400/30'
  if (rank === 1) return 'bg-indigo-500/15 text-indigo-300 ring-indigo-400/30'
  if (rank == null) return 'bg-white/5 text-slate-400 ring-white/10'
  return 'bg-white/10 text-slate-200 ring-white/15'
}

async function refreshInfo(i: number) {
  const c = myCharacters.value[i]
  if (!c) return
  loadingByIndex.value[i] = true
  try {
    const info = await getBlizzardCharacterGuild(c.realm, c.name, c.wowType)
    const g = info?.guild
    const name = g?.guild?.name || g?.name
    myCharacters.value[i].guildName = name || undefined
    myCharacters.value[i].rank = typeof info?.rank === 'number' ? info.rank : null
    window.dispatchEvent(new CustomEvent('app:toast', { detail: { type: 'success', message: 'Character info refreshed' } }))
  } catch (e: any) {
    window.dispatchEvent(new CustomEvent('app:toast', { detail: { type: 'error', message: e?.message || 'Failed to refresh info' } }))
  } finally {
    loadingByIndex.value[i] = false
  }
}

async function proceedJoinOrImport() {
  if (selectedIndex.value == null) return
  joinLoading.value = true
  joinError.value = ''
  try {
    const c = myCharacters.value[selectedIndex.value]
    if (!c) return
    const details = await getBlizzardCharacterDetails(c.realm, c.name, c.wowType)
    const g = details?.guild
    if (!g?.name) {
      joinError.value = 'This character is not in a guild.'
      return
    }
    if (selectedGuildCheck.value?.exists && selectedGuildCheck.value.id) {
      await joinGuild(selectedGuildCheck.value.id, c.realm, c.name, c.wowType)
      joinOpen.value = false
      await fetchGuilds()
      window.dispatchEvent(new CustomEvent('app:toast', { detail: { type: 'success', message: `Joined guild ${selectedGuildCheck.value.name}` } }))
      return
    }
    const res = await claimGuildApi(c.realm, c.name, c.wowType)
    joinOpen.value = false
    await fetchGuilds()
    if (res?.guild?.id) {
      window.dispatchEvent(new CustomEvent('app:toast', { detail: { type: 'success', message: `Guild ${res.guild.name} imported` } }))
    }
  } catch (e: any) {
    joinError.value = e?.message || 'Operation failed'
  } finally {
    joinLoading.value = false
  }
}
</script>

<template>
  <main class="min-h-screen">
    <section class="relative overflow-hidden">
      <div
        class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(60rem_60rem_at_50%_-10%,rgba(99,102,241,0.20),transparent_60%)]"
      ></div>

      <div class="mx-auto max-w-6xl px-4 py-16 md:py-24">
        <div v-if="isUserLoading" class="text-center text-slate-400 py-16">Loading…</div>

        <div v-else-if="!isAuthenticated" class="grid gap-12 md:grid-cols-2 md:items-center">
          <div class="space-y-6">
            <div
              class="inline-flex items-center gap-2 rounded-full bg-indigo-500/10 px-4 py-2 text-sm ring-1 ring-inset ring-indigo-500/20"
            >
              <span class="relative flex h-2 w-2">
                <span
                  class="absolute inline-flex h-full w-full animate-ping rounded-full bg-indigo-400 opacity-75"
                ></span>
                <span class="relative inline-flex h-2 w-2 rounded-full bg-indigo-500"></span>
              </span>
              <span class="text-indigo-300">World of Warcraft Guild Manager</span>
            </div>

            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold tracking-tight">
              Manage your
              <span
                class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-400 to-cyan-400"
                >guild</span
              >
              like a pro
            </h1>

            <p class="text-lg text-slate-300/90 leading-relaxed">
              Track members, manage DKP, plan raids, and sync directly with Blizzard API. Everything
              you need in one powerful platform.
            </p>

            <div class="flex flex-col sm:flex-row gap-4">
              <button
                @click="handleDiscordLogin"
                class="group relative inline-flex items-center justify-center gap-3 px-6 py-4 rounded-xl font-semibold bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-600 text-white shadow-lg shadow-indigo-600/50 transition-all cursor-pointer"
              >
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
                  <path
                    d="M20.317 4.37a19.791 19.791 0 0 0-4.885-1.515a.074.074 0 0 0-.079.037c-.21.375-.444.864-.608 1.25a18.27 18.27 0 0 0-5.487 0a12.64 12.64 0 0 0-.617-1.25a.077.077 0 0 0-.079-.037A19.736 19.736 0 0 0 3.677 4.37a.07.07 0 0 0-.032.027C.533 9.046-.32 13.58.099 18.057a.082.082 0 0 0 .031.057a19.9 19.9 0 0 0 5.993 3.03a.078.078 0 0 0 .084-.028a14.09 14.09 0 0 0 1.226-1.994a.076.076 0 0 0-.041-.106a13.107 13.107 0 0 1-1.872-.892a.077.077 0 0 1-.008-.128a10.2 10.2 0 0 0 .372-.292a.074.074 0 0 1 .077-.01c3.928 1.793 8.18 1.793 12.062 0a.074.074 0 0 1 .078.01c.12.098.246.198.373.292a.077.077 0 0 1-.006.127a12.299 12.299 0 0 1-1.873.892a.077.077 0 0 0-.041.107c.36.698.772 1.362 1.225 1.993a.076.076 0 0 0 .084.028a19.839 19.839 0 0 0 6.002-3.03a.077.077 0 0 0 .032-.054c.5-5.177-.838-9.674-3.549-13.66a.061.061 0 0 0-.031-.03zM8.02 15.33c-1.183 0-2.157-1.085-2.157-2.419c0-1.333.956-2.419 2.157-2.419c1.21 0 2.176 1.096 2.157 2.42c0 1.333-.956 2.418-2.157 2.418zm7.975 0c-1.183 0-2.157-1.085-2.157-2.419c0-1.333.955-2.419 2.157-2.419c1.21 0 2.176 1.096 2.157 2.42c0 1.333-.946 2.418-2.157 2.418z"
                  />
                </svg>
                Sign in with Discord
              </button>

              <button
                @click="$router.push({ name: 'features' })"
                class="inline-flex items-center justify-center gap-2 px-6 py-4 rounded-xl font-semibold text-slate-200 ring-1 ring-inset ring-white/10 hover:bg-white/5 hover:text-white transition-all cursor-pointer"
              >
                View Features
                <svg
                  class="w-4 h-4"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                  stroke-width="2.5"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M13 7l5 5m0 0l-5 5m5-5H6"
                  />
                </svg>
              </button>
            </div>
          </div>

          <div class="relative">
            <div
              class="absolute -inset-4 -z-10 rounded-3xl bg-gradient-to-tr from-indigo-600/30 via-fuchsia-500/20 to-cyan-400/20 blur-2xl"
            ></div>
            <div
              class="rounded-2xl border border-white/10 bg-slate-900/60 p-6 shadow-2xl backdrop-blur"
            >
              <div class="flex items-center justify-between mb-4">
                <div class="font-semibold text-lg">Guild Dashboard</div>
                <div class="text-xs text-slate-400 bg-white/5 px-2 py-1 rounded-md">Preview</div>
              </div>
              <div class="grid grid-cols-2 gap-3 mb-4">
                <div
                  class="rounded-xl bg-gradient-to-br from-indigo-500/20 to-transparent border border-indigo-500/20 p-4"
                >
                  <div class="text-3xl font-bold text-indigo-300">128</div>
                  <div class="text-xs text-slate-400 mt-1">Active Members</div>
                </div>
                <div
                  class="rounded-xl bg-gradient-to-br from-cyan-500/20 to-transparent border border-cyan-500/20 p-4"
                >
                  <div class="text-3xl font-bold text-cyan-300">24</div>
                  <div class="text-xs text-slate-400 mt-1">Raids/Week</div>
                </div>
              </div>
              <div
                class="h-32 rounded-xl bg-gradient-to-r from-indigo-600/20 via-white/5 to-transparent border border-white/5"
              ></div>
            </div>
          </div>
        </div>

        <div v-else class="space-y-8">
          <div class="text-center space-y-4">
            <h1 class="text-3xl md:text-4xl font-bold">
              Welcome to
              <span
                class="bg-gradient-to-r from-orange-400 via-yellow-300 to-orange-500 bg-clip-text text-transparent"
                >GuildForge</span
              >, <span class="text-indigo-400">{{ user?.username }}</span
              >!
            </h1>
            <p v-if="loadingGuilds" class="text-slate-400">Loading your guilds…</p>
            <p v-else-if="hasGuilds" class="text-slate-400">Select a guild or create a new one</p>
            <p v-else class="text-slate-400">Let's get your guild set up in just a few steps</p>
          </div>

          <div v-if="loadingGuilds" class="max-w-3xl mx-auto">
            <div
              class="rounded-xl border border-white/10 bg-white/5 p-6 text-center text-slate-400"
            >
              Loading…
            </div>
          </div>

          <div v-else-if="hasGuilds" class="max-w-3xl mx-auto">
            <div class="rounded-xl border border-white/10 bg-white/5 p-6 backdrop-blur">
              <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-semibold">Your Guilds</h2>
                <div class="relative group">
                  <button
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-sm font-medium transition cursor-pointer"
                  >
                    <svg
                      class="w-4 h-4"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                      stroke-width="2.5"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Add Guild
                  </button>
                  <div
                    class="absolute right-0 top-12 w-72 rounded-xl border border-white/10 bg-slate-900/95 backdrop-blur shadow-2xl ring-1 ring-black/10 z-50 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all"
                  >
                    <div class="p-2 space-y-1">
                      <button
                        @click="handleClaimGuild"
                        class="w-full flex items-start gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-left hover:bg-white/5 transition group/item"
                      >
                        <svg
                          class="w-5 h-5 text-indigo-400 flex-shrink-0"
                          fill="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path
                            d="M11.93 1.93c-.51-.51-1.35-.51-1.86 0L2.21 9.79c-.59.59-.59 1.54 0 2.12l7.86 7.86c.51.51 1.35.51 1.86 0l7.86-7.86c.59-.59.59-1.54 0-2.12l-7.86-7.86zm.07 3.44l4.68 4.68-4.68 4.68-4.68-4.68L12 5.37z"
                          />
                        </svg>
                        <div class="flex-1">
                          <div class="text-white group-hover/item:text-indigo-300 transition">
                            Import from Blizzard
                          </div>
                          <div class="text-xs text-slate-400">Create new guild (any rank)</div>
                        </div>
                      </button>
                      <button
                        v-if="hasBlizzardLinked"
                        @click="openJoinModal"
                        class="w-full flex items-start gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-left hover:bg-white/5 transition group/item"
                      >
                        <svg
                          class="w-5 h-5 text-emerald-400 flex-shrink-0"
                          fill="none"
                          viewBox="0 0 24 24"
                          stroke="currentColor"
                          stroke-width="2"
                        >
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                          />
                        </svg>
                        <div class="flex-1">
                          <div class="text-white group-hover/item:text-emerald-300 transition">
                            Find & Join Guild
                          </div>
                          <div class="text-xs text-slate-400">Join existing guild (any rank)</div>
                        </div>
                      </button>
                    </div>
                  </div>
                </div>
              </div>
              <div class="space-y-3">
                <button
                  v-for="guild in guilds"
                  :key="guild.id"
                  @click="handleSelectGuild(guild.id)"
                  class="w-full flex items-center gap-4 p-4 rounded-xl border border-white/10 bg-white/5 hover:border-indigo-500/50 hover:bg-white/10 transition-all group cursor-pointer"
                >
                  <div
                    class="h-12 w-12 rounded-lg bg-gradient-to-br from-indigo-500 to-indigo-600 grid place-items-center font-bold text-lg"
                  >
                    {{ guild.name.substring(0, 2).toUpperCase() }}
                  </div>
                  <div class="flex-1 text-left">
                    <h3 class="font-semibold text-lg group-hover:text-indigo-300 transition">
                      {{ guild.name }}
                    </h3>
                    <p class="text-sm text-slate-400">
                      {{ guild.faction || 'Unknown' }} • {{ guild.nbrGuildMembers || 0 }} members
                    </p>
                  </div>
                  <svg
                    class="w-5 h-5 text-slate-400 group-hover:text-indigo-300 transition"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M13 7l5 5m0 0l-5 5m5-5H6"
                    />
                  </svg>
                </button>
              </div>
            </div>
          </div>

          <div v-else class="max-w-3xl mx-auto space-y-6">
            <div class="rounded-xl border border-white/10 bg-white/5 p-6 backdrop-blur">
              <div class="flex items-start gap-4">
                <div
                  class="flex-shrink-0 h-10 w-10 rounded-lg grid place-items-center font-bold text-lg"
                  :class="
                    hasBlizzardLinked ? 'bg-emerald-500 text-white' : 'bg-white/10 text-slate-400'
                  "
                >
                  {{ hasBlizzardLinked ? '✓' : '1' }}
                </div>
                <div class="flex-1">
                  <h3 class="text-lg font-semibold mb-2">Connect Battle.net</h3>
                  <p class="text-sm text-slate-400 mb-4">
                    Link your Battle.net account to automatically import your guild, characters, and
                    members directly from Blizzard.
                  </p>
                  <button
                    v-if="!hasBlizzardLinked"
                    @click="handleBlizzardLink"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 font-medium transition cursor-pointer"
                  >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                      <path
                        d="M11.93 1.93c-.51-.51-1.35-.51-1.86 0L2.21 9.79c-.59.59-.59 1.54 0 2.12l7.86 7.86c.51.51 1.35.51 1.86 0l7.86-7.86c.59-.59.59-1.54 0-2.12l-7.86-7.86zm.07 3.44l4.68 4.68-4.68 4.68-4.68-4.68L12 5.37z"
                      />
                    </svg>
                    Link Battle.net Account
                  </button>
                  <div
                    v-else
                    class="inline-flex items-center gap-2 text-emerald-400 text-sm font-medium"
                  >
                    <svg
                      class="w-5 h-5"
                      fill="none"
                      viewBox="0 0 24 24"
                      stroke="currentColor"
                      stroke-width="2.5"
                    >
                      <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    Battle.net Connected
                  </div>
                </div>
              </div>
            </div>

            <div
              class="rounded-xl border p-6 backdrop-blur transition-all"
              :class="
                hasBlizzardLinked
                  ? 'border-white/10 bg-white/5'
                  : 'border-white/5 bg-white/[0.02] opacity-50'
              "
            >
              <div class="flex items-start gap-4">
                <div
                  class="flex-shrink-0 h-10 w-10 rounded-lg grid place-items-center font-bold text-lg"
                  :class="
                    hasBlizzardLinked ? 'bg-white/10 text-white' : 'bg-white/5 text-slate-500'
                  "
                >
                  2
                </div>
                <div class="flex-1">
                  <h3 class="text-lg font-semibold mb-2">Add Your Guild</h3>
                  <p class="text-sm text-slate-400 mb-4">
                    Choose how you want to add your guild to GuildForge:
                  </p>
                  <div class="space-y-3">
                    
                    <div class="rounded-lg border border-indigo-500/20 bg-indigo-500/5 p-3">
                      <div class="flex items-start gap-3 mb-2">
                        <svg
                          class="w-5 h-5 text-indigo-400 flex-shrink-0 mt-0.5"
                          fill="none"
                          viewBox="0 0 24 24"
                          stroke="currentColor"
                          stroke-width="2"
                        >
                          <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        <div class="flex-1">
                          <h4 class="text-sm font-semibold text-white">
                            Import New Guild from Blizzard
                          </h4>
                          <p class="text-xs text-slate-400 mt-1">
                            Use this if your guild is NOT yet in GuildForge. Works for any rank, and
                            your role will match your in-game rank.
                          </p>
                        </div>
                      </div>
                      <button
                        @click="handleClaimGuild"
                        :disabled="!hasBlizzardLinked"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-700 font-medium text-sm transition disabled:opacity-50 disabled:cursor-not-allowed"
                      >
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                          <path
                            d="M11.93 1.93c-.51-.51-1.35-.51-1.86 0L2.21 9.79c-.59.59-.59 1.54 0 2.12l7.86 7.86c.51.51 1.35.51 1.86 0l7.86-7.86c.59-.59.59-1.54 0-2.12l-7.86-7.86zm.07 3.44l4.68 4.68-4.68 4.68-4.68-4.68L12 5.37z"
                          />
                        </svg>
                        Import from Blizzard
                      </button>
                    </div>

                    
                    <div class="rounded-lg border border-white/10 bg-white/5 p-3">
                      <div class="flex items-start gap-3 mb-2">
                        <svg
                          class="w-5 h-5 text-emerald-400 flex-shrink-0 mt-0.5"
                          fill="none"
                          viewBox="0 0 24 24"
                          stroke="currentColor"
                          stroke-width="2"
                        >
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"
                          />
                        </svg>
                        <div class="flex-1">
                          <h4 class="text-sm font-semibold text-white">Join Existing Guild</h4>
                          <p class="text-xs text-slate-400 mt-1">
                            Use this if your guild is ALREADY in GuildForge. Works for any rank.
                          </p>
                        </div>
                      </div>
                      <button
                        v-if="hasBlizzardLinked"
                        @click="openJoinModal"
                        class="w-full inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg ring-1 ring-inset ring-white/10 hover:bg-white/5 font-medium text-sm transition"
                      >
                        <svg
                          class="w-4 h-4"
                          fill="none"
                          viewBox="0 0 24 24"
                          stroke="currentColor"
                          stroke-width="2"
                        >
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                          />
                        </svg>
                        Find & Join Guild
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section v-if="!isAuthenticated" class="border-t border-white/5 py-16 md:py-24">
      <div class="mx-auto max-w-6xl px-4">
        <div class="text-center mb-12">
          <h2 class="text-2xl md:text-3xl font-bold mb-3">Powerful Features for Guild Masters</h2>
          <p class="text-slate-400">Everything you need to manage your WoW guild efficiently</p>
        </div>

        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
          <article
            class="rounded-xl border border-white/10 bg-white/5 p-6 backdrop-blur-sm hover:border-indigo-500/30 transition"
          >
            <div
              class="h-12 w-12 rounded-lg bg-gradient-to-br from-indigo-500 to-indigo-600 grid place-items-center text-2xl mb-4"
            >
              🎮
            </div>
            <h3 class="font-semibold text-lg mb-2">Blizzard API Sync</h3>
            <p class="text-sm text-slate-400">
              Real-time sync with Battle.net for accurate character and guild data.
            </p>
          </article>

          <article
            class="rounded-xl border border-white/10 bg-white/5 p-6 backdrop-blur-sm hover:border-indigo-500/30 transition"
          >
            <div
              class="h-12 w-12 rounded-lg bg-gradient-to-br from-cyan-500 to-cyan-600 grid place-items-center text-2xl mb-4"
            >
              👥
            </div>
            <h3 class="font-semibold text-lg mb-2">Member Management</h3>
            <p class="text-sm text-slate-400">
              Track members, roles, ranks, and activity across all your characters.
            </p>
          </article>

          <article
            class="rounded-xl border border-white/10 bg-white/5 p-6 backdrop-blur-sm hover:border-indigo-500/30 transition"
          >
            <div
              class="h-12 w-12 rounded-lg bg-gradient-to-br from-fuchsia-500 to-fuchsia-600 grid place-items-center text-2xl mb-4"
            >
              💎
            </div>
            <h3 class="font-semibold text-lg mb-2">DKP System</h3>
            <p class="text-sm text-slate-400">
              Automated DKP tracking for raids, events, and loot distribution.
            </p>
          </article>

          <article
            class="rounded-xl border border-white/10 bg-white/5 p-6 backdrop-blur-sm hover:border-indigo-500/30 transition"
          >
            <div
              class="h-12 w-12 rounded-lg bg-gradient-to-br from-orange-500 to-orange-600 grid place-items-center text-2xl mb-4"
            >
              📊
            </div>
            <h3 class="font-semibold text-lg mb-2">Raid Planning</h3>
            <p class="text-sm text-slate-400">
              Schedule raids, track attendance, and optimize your raid composition.
            </p>
          </article>
        </div>
      </div>
    </section>

    <BaseModal v-model="joinOpen" title="Join a Guild via Your Character" size="lg">
      <div class="space-y-4">
        <p class="text-sm text-slate-400">
          Select one of your characters. We will detect the guild and either join it if it exists or
          import it if not.
        </p>

        <div
          v-if="joinError"
          class="rounded-xl bg-red-500/10 border border-red-500/20 p-3 text-sm text-red-300"
        >
          {{ joinError }}
        </div>

        <div class="flex items-center justify-between mb-2">
          <div class="flex items-center gap-1 text-xs">
            <button
              v-for="tab in ['All','Retail','Classic']"
              :key="tab"
              class="px-3 py-1.5 rounded-lg ring-1 ring-inset transition"
              :class="wowFilter === tab ? 'bg-white/10 text-white ring-white/20' : 'bg-white/5 text-slate-300 ring-white/10 hover:bg-white/10'"
              @click="wowFilter = tab as WowFilter"
            >{{ tab }}</button>
          </div>
          <div class="flex items-center gap-3 text-xs">
            <select v-model="realmFilter" class="bg-white/5 text-slate-200 ring-1 ring-inset ring-white/10 rounded-lg px-2 py-1">
              <option value="All">All realms</option>
              <option v-for="r in realmOptions" :key="r" :value="r">{{ r }}</option>
            </select>
            <label class="inline-flex items-center gap-2 select-none cursor-pointer">
              <input type="checkbox" v-model="showOnlyWithGuild" class="rounded border-white/20 bg-white/5 text-indigo-500"/>
              <span class="text-slate-300">Show only characters with a guild</span>
            </label>
          </div>
        </div>

        <div class="max-h-96 overflow-y-auto pr-2 custom-scrollbar">
          <div class="space-y-2">
            <template v-if="charsLoading">
              <div v-for="n in 6" :key="n" class="flex items-center justify-between rounded-lg border border-white/10 bg-white/5 px-3 py-2">
                <div class="w-1/2 h-3 rounded skeleton"></div>
                <div class="w-24 h-6 rounded skeleton"></div>
              </div>
            </template>
            <template v-else>
            <div
              v-for="(c, i) in myCharacters"
              :key="`${c.realm}-${c.name}-${i}`"
              v-show="
                (!showOnlyWithGuild || !!c.guildName)
                && matchesWowFilter(c.wowType, wowFilter)
                && (realmFilter === 'All' || c.realm === realmFilter)
              "
              class="flex items-center justify-between rounded-lg border border-white/10 bg-white/5 px-3 py-2"
            >
              <div class="text-sm text-slate-200">
                <div class="flex items-center gap-2">
                  <span class="font-medium">{{ c.name }}</span>
                  <span class="text-slate-400">— {{ c.realm }}</span>
                  <span v-if="bestIndex === i" class="ml-1 text-amber-300 text-xs font-semibold">Best</span>
                </div>
                <div class="text-xs mt-1">
                  <template v-if="loadingByIndex[i]">
                    <span class="inline-flex items-center gap-1 text-slate-400">
                      <svg class="w-3.5 h-3.5 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                      </svg>
                      Fetching guild & rank…
                    </span>
                  </template>
                  <template v-else>
                    <span v-if="c.guildName" class="text-slate-300">{{ c.guildName }}</span>
                    <span v-else class="text-slate-500">No guild</span>
                    <span class="mx-1 text-slate-500">•</span>
                    <span
                      :title="`Detected rank: ${rankLabel(c.rank)}`"
                      class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold ring-1 ring-inset"
                      :class="rankClass(c.rank)"
                    >
                      {{ rankLabel(c.rank) }}
                    </span>

                    <span class="mx-1 text-slate-500">•</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10px] font-semibold ring-1 ring-inset bg-white/5 text-slate-300 ring-white/10 tip" :data-tip="c.wowType || 'Retail'">
                      {{ (c.wowType || 'Retail').replace('Classic Anniversary','Classic') }}
                    </span>
                  </template>
                </div>
              </div>
              <div class="flex items-center gap-2">
                <button
                  class="text-xs rounded-lg px-2 py-1 ring-1 ring-inset ring-white/10 hover:bg-white/5 disabled:opacity-50"
                  :disabled="loadingByIndex[i]"
                  @click="checkAndPrepare(i)"
                >
                  <span v-if="selectedIndex === i">Selected</span>
                  <span v-else>Select</span>
                </button>
                <button
                  class="text-xs rounded-lg px-2 py-1 ring-1 ring-inset ring-white/10 hover:bg-white/5 disabled:opacity-50"
                  :disabled="loadingByIndex[i]"
                  @click="refreshInfo(i)"
                  title="Refresh guild and rank"
                >
                  Refresh
                </button>
              </div>
            </div>
            </template>
          </div>
        </div>

        <div
          v-if="selectedIndex !== null && selectedGuildCheck"
          class="rounded-lg border border-white/10 bg-white/5 p-3 text-sm"
        >
          <div v-if="selectedGuildCheck.exists" class="text-slate-300">
            Guild found in database:
            <span class="font-semibold text-white">{{ selectedGuildCheck.name }}</span>
          </div>
          <div v-else class="text-slate-300">Guild not found in database. You can import it.</div>
        </div>
      </div>

      <template #footer>
        <div class="flex items-center justify-end gap-2">
          <button
            class="px-4 py-2 text-sm rounded-lg ring-1 ring-inset ring-white/10 hover:ring-white/20"
            @click="joinOpen = false"
            :disabled="joinLoading"
          >
            Close
          </button>
          <button
            class="px-4 py-2 text-sm rounded-lg bg-indigo-500/20 text-indigo-200 ring-1 ring-inset ring-indigo-500/30 hover:bg-indigo-500/30 disabled:opacity-60"
            :disabled="selectedIndex === null || joinLoading || !selectedGuildCheck"
            @click="proceedJoinOrImport"
          >
            <span v-if="!joinLoading">Proceed</span>
            <span v-else>Working…</span>
          </button>
        </div>
      </template>
    </BaseModal>
  </main>
</template>

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


.skeleton {
  background: linear-gradient(90deg, rgba(255,255,255,0.06) 25%, rgba(255,255,255,0.15) 37%, rgba(255,255,255,0.06) 63%);
  background-size: 400% 100%;
  animation: shimmer 1.2s ease-in-out infinite;
}

@keyframes shimmer {
  0% { background-position: 100% 0; }
  100% { background-position: 0 0; }
}


.tip {
  position: relative;
}
.tip[data-tip]:hover::after {
  content: attr(data-tip);
  position: absolute;
  bottom: 120%;
  left: 50%;
  transform: translateX(-50%);
  white-space: nowrap;
  background: rgba(15, 23, 42, 0.95);
  color: #e2e8f0;
  padding: 6px 8px;
  border-radius: 6px;
  font-size: 11px;
  line-height: 1;
  border: 1px solid rgba(255,255,255,0.08);
  pointer-events: none;
  z-index: 20;
}
.tip[data-tip]:hover::before {
  content: '';
  position: absolute;
  bottom: calc(120% - 6px);
  left: 50%;
  transform: translateX(-50%);
  width: 8px;
  height: 8px;
  background: rgba(15, 23, 42, 0.95);
  border-left: 1px solid rgba(255,255,255,0.08);
  border-top: 1px solid rgba(255,255,255,0.08);
  transform-origin: center;
  transform: translateX(-50%) rotate(45deg);
}
</style>
