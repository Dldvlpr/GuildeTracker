<template>
  <section class="mx-auto max-w-6xl flex flex-col gap-4">
    <header class="flex items-center justify-between">
      <h1 class="text-xl font-semibold">{{ guild?.name || 'Guild' }}</h1>
      <RouterLink to="/" class="text-sm rounded-lg px-3 py-1.5 ring-1 ring-inset ring-white/10 hover:ring-white/20">← Back</RouterLink>
    </header>

    <div v-if="loading" class="text-slate-400">Loading…</div>
    <div v-else-if="error" class="text-red-400">{{ error }}</div>

    <div v-else class="grid gap-4 sm:grid-cols-2">
      <div class="rounded-xl border border-white/10 bg-white/5 p-4">
        <div class="text-sm text-slate-400">Faction</div>
        <div class="text-lg font-semibold">{{ guild?.faction }}</div>
      </div>
      <div class="rounded-xl border border-white/10 bg-white/5 p-4">
        <div class="text-sm text-slate-400">Members</div>
        <div class="text-lg font-semibold">{{ guild?.nbrGuildMembers ?? '—' }}</div>
      </div>
      <div class="rounded-xl border border-white/10 bg-white/5 p-4">
        <div class="text-sm text-slate-400">Characters</div>
        <div class="text-lg font-semibold">{{ guild?.nbrCharacters ?? '—' }}</div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { getGameGuild } from '@/services/gameGuild.service'
import type { GameGuild } from '@/interfaces/GameGuild.interface'

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
  if (res.ok) guild.value = res.data
  else error.value = res.error
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

