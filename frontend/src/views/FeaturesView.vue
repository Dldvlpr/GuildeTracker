<template>
  <section class="mx-auto max-w-6xl px-4 py-8">
    <header class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-2xl md:text-3xl font-bold text-white mb-2">
          {{ guild?.name || 'Guild' }}
        </h1>
        <p class="text-slate-300">
          What would you like to do with this guild?
        </p>
      </div>
      <div class="text-right">
        <RouterLink to="/" class="text-sm px-4 py-2 rounded-lg ring-1 ring-inset ring-white/10 hover:ring-white/20 text-slate-200 hover:text-white transition mb-2 block">
          ← Back to home
        </RouterLink>
        <div class="text-sm text-slate-400">{{ guild?.faction }}</div>
        <div class="text-xs text-slate-500">{{ guild?.nbrGuildMembers }} members</div>
      </div>
    </header>

    <div v-if="loading" class="text-center text-slate-400 py-12">
      Loading guild...
    </div>
    <div v-else-if="error" class="text-center text-red-400 py-12">
      {{ error }}
    </div>

    <div v-else>
      <!-- Warning if guild is invalid (no characters) -->
      <div v-if="!guild?.isValid" class="mb-8 p-4 rounded-lg bg-amber-500/10 border border-amber-500/20">
        <div class="flex items-start gap-3">
          <div class="text-2xl">⚠️</div>
          <div>
            <h3 class="text-amber-400 font-semibold mb-1">Incomplete Guild</h3>
            <p class="text-amber-200/80 text-sm mb-3">
              You must add at least one character to your guild to access features.
            </p>
            <RouterLink
              :to="`/guild/${guild?.id}/characters`"
              class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg transition text-sm font-medium"
            >
              Add my first character →
            </RouterLink>
          </div>
        </div>
      </div>

      <div class="mb-12">
        <div class="flex items-center gap-3 mb-6">
          <div class="flex items-center justify-center w-8 h-8 rounded-full bg-red-500/20 text-red-400">
            P1
          </div>
          <h2 class="text-xl font-semibold text-white">Guild Organization</h2>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <FeatureCard
            title="Member Management"
            description="Manage members and their roles (GM, Officer, Member)"
            icon="MEMBERS"
            :available="!!guild?.id && !!guild?.isValid"
            :route="guild?.id ? `/guild/${guild.id}/members` : '#'"
            priority="high"
          />
          <FeatureCard
            title="Character List"
            description="View and manage all member characters"
            icon="CHARS"
            :available="true"
            :route="`/guild/${guild?.id}/characters`"
            priority="high"
          />
        </div>
      </div>

      <div class="mb-12">
        <div class="flex items-center gap-3 mb-6">
          <div class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-500/20 text-indigo-400">
            P2
          </div>
          <h2 class="text-xl font-semibold text-white">Raids & Events</h2>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <FeatureCard
            title="Event Import"
            description="Import events from JSON files"
            icon="IMPORT"
            :available="!!guild?.isValid"
            :route="`/guild/${guild?.id}/import-events`"
            priority="medium"
          />
          <FeatureCard
            title="DKP/Points System"
            description="Manage fair loot distribution"
            icon="DKP"
            :available="false"
            :route="`/guild/${guild?.id}/dkp-system`"
            priority="medium"
          />
          <FeatureCard
            title="Raid Calendar"
            description="Plan and organize your guild events"
            icon="CAL"
            :available="false"
            :route="`/guild/${guild?.id}/raid-calendar`"
            priority="medium"
          />
        </div>
      </div>

      <div class="mb-8">
        <div class="flex items-center gap-3 mb-6">
          <div class="flex items-center justify-center w-8 h-8 rounded-full bg-emerald-500/20 text-emerald-400">
            P3
          </div>
          <h2 class="text-xl font-semibold text-white">Statistics & Tracking</h2>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <FeatureCard
            title="Raid Stats"
            description="Analyze participation, performance and loot"
            icon="STATS"
            :available="false"
            :route="`/guild/${guild?.id}/raid-stats`"
            priority="low"
          />
          <FeatureCard
            title="Guild Reports"
            description="Track activity and progression"
            icon="REPORTS"
            :available="false"
            :route="`/guild/${guild?.id}/guild-reports`"
            priority="low"
          />
          <FeatureCard
            title="Discord Notifications"
            description="Webhooks for important events"
            icon="NOTIF"
            :available="false"
            :route="`/guild/${guild?.id}/discord-notifications`"
            priority="low"
          />
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { getGameGuild } from '@/services/gameGuild.service'
import type { GameGuild } from '@/interfaces/GameGuild.interface'
import FeatureCard from '@/components/FeatureCard.vue'

const route = useRoute()
const id = ref<string | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)
const guild = ref<GameGuild | null>(null)

const load = async () => {
  if (!id.value) return
  loading.value = true
  error.value = null
  guild.value = null
  const res = await getGameGuild(id.value)
  if (res.ok) {
    guild.value = res.data
  } else {
    error.value = res.error
  }
  loading.value = false
}

onMounted(() => {
  id.value = (route.params.id as string) || null
  load()
})

watch(
  () => route.params.id,
  (v) => {
    id.value = (v as string) || null
    load()
  },
)
</script>

<style scoped>
</style>
