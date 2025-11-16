<template>
  <section class="mx-auto max-w-6xl px-4 py-8">
    <div v-if="loading" class="text-center text-slate-400 py-12">Loading...</div>

    <div v-else-if="error" class="text-center text-red-400 py-12">
      {{ error }}
    </div>

    <div v-else>
      <header class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl md:text-3xl font-bold text-white mb-2">📋 Guild Dashboard</h1>
          <p class="text-slate-300">Overview with stats and active members of {{ guild?.name }}</p>
        </div>
        <RouterLink
          :to="`/guild/${guildId}`"
          class="text-sm px-4 py-2 rounded-lg ring-1 ring-inset ring-white/10 hover:ring-white/20 text-slate-200 hover:text-white transition"
        >
          ← Back to guild hub
        </RouterLink>
      </header>

      <div v-if="showOnboarding" class="rounded-xl border border-indigo-500/20 bg-indigo-500/10 p-4 mb-6">
        <div class="flex items-center justify-between gap-4">
          <div>
            <h3 class="text-white font-semibold">Welcome! Finalize your setup</h3>
            <p class="text-sm text-indigo-200/80">Sync your guild roster from Blizzard to see members and roles.</p>
          </div>
          <div class="flex items-center gap-2">
            <button
              class="px-4 py-2 text-sm rounded-lg bg-indigo-500/20 text-indigo-200 ring-1 ring-inset ring-indigo-500/40 hover:bg-indigo-500/30 disabled:opacity-60"
              :disabled="syncing"
              @click="syncNow"
            >
              <span v-if="!syncing">Sync now</span>
              <span v-else class="inline-flex items-center gap-2">
                <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                </svg>
                Syncing…
              </span>
            </button>
            <button
              class="px-4 py-2 text-sm rounded-lg bg-white/10 text-white ring-1 ring-inset ring-white/20 hover:bg-white/15 disabled:opacity-60"
              :disabled="relinking"
              @click="relinkMine"
              title="Link all your roster characters in this guild to your account"
            >
              <span v-if="!relinking">Relink my characters</span>
              <span v-else class="inline-flex items-center gap-2">
                <svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24" fill="none">
                  <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                  <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
                </svg>
                Relinking…
              </span>
            </button>
          </div>
        </div>
        <div v-if="syncMessage" class="mt-3 text-sm" :class="syncOk ? 'text-emerald-300' : 'text-red-300'">
          {{ syncMessage }}
        </div>
        <div v-if="relinkMessage" class="mt-2 text-sm" :class="relinkOk ? 'text-emerald-300' : 'text-red-300'">
          {{ relinkMessage }}
        </div>
      </div>

      <div class="rounded-2xl border border-white/10 bg-white/5 p-8 text-center">
        <div class="text-6xl mb-4">🔧</div>
        <h2 class="text-xl font-semibold text-white mb-2">Feature in development</h2>
        <p class="text-slate-300">The guild dashboard will be available soon with detailed statistics.</p>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { onMounted, ref, watch, computed } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { getGameGuild } from '@/services/gameGuild.service'
import { syncGuildRoster, relinkMyCharacters } from '@/services/character.service'
import type { GameGuild } from '@/interfaces/GameGuild.interface'

defineOptions({ name: 'GuildDashboardView' })

const route = useRoute()
const guildId = ref<string | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)
const guild = ref<GameGuild | null>(null)
const syncing = ref(false)
const syncMessage = ref('')
const syncOk = ref<boolean | null>(null)
const showOnboarding = computed(() => route.query.onboarding === '1')
const relinking = ref(false)
const relinkMessage = ref('')
const relinkOk = ref<boolean | null>(null)

const load = async () => {
  if (!guildId.value) return
  loading.value = true
  error.value = null
  guild.value = null
  const res = await getGameGuild(guildId.value)
  if (res.ok) guild.value = res.data
  else error.value = res.error
  loading.value = false
}

onMounted(() => {
  guildId.value = (route.params.id as string) || null
  load()
})

watch(
  () => route.params.id,
  (v) => {
    guildId.value = (v as string) || null
    load()
  },
)

async function syncNow() {
  if (!guildId.value) return
  syncing.value = true
  syncMessage.value = ''
  syncOk.value = null
  const res = await syncGuildRoster(guildId.value)
  syncing.value = false
  if (res.ok) {
    syncOk.value = true
    syncMessage.value = `Roster synchronized. Created ${res.created}, updated ${res.updated}, removed ${res.removed}.`
  } else {
    syncOk.value = false
    syncMessage.value = res.error
  }
}

async function relinkMine() {
  if (!guildId.value) return
  relinking.value = true
  relinkMessage.value = ''
  relinkOk.value = null
  const res = await relinkMyCharacters(guildId.value)
  relinking.value = false
  if (res.ok) {
    relinkOk.value = true
    relinkMessage.value = res.message + (typeof res.linked === 'number' ? ` (linked: ${res.linked})` : '')
  } else {
    relinkOk.value = false
    relinkMessage.value = res.error
  }
}
</script>

<style scoped>
</style>
