<template>
  <section class="mx-auto max-w-6xl px-4 py-8">
    <div v-if="loading" class="text-center text-slate-400 py-12">Loading...</div>

    <div v-else-if="error" class="text-center text-red-400 py-12">
      {{ error }}
    </div>

    <div v-else>
      <header class="flex items-center justify-between mb-8">
        <div>
          <h1 class="text-2xl md:text-3xl font-bold text-white mb-2">💰 DKP/Points System</h1>
          <p class="text-slate-300">Manage fair loot distribution for {{ guild?.name }}</p>
        </div>
        <RouterLink
          :to="`/guild/${guildId}`"
          class="text-sm px-4 py-2 rounded-lg ring-1 ring-inset ring-white/10 hover:ring-white/20 text-slate-200 hover:text-white transition"
        >
          ← Back to features
        </RouterLink>
      </header>

      <div class="rounded-2xl border border-white/10 bg-white/5 p-8 text-center">
        <div class="text-6xl mb-4">🔧</div>
        <h2 class="text-xl font-semibold text-white mb-2">Feature in development</h2>
        <p class="text-slate-300">The DKP/Points system will be available soon.</p>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { getGameGuild } from '@/services/gameGuild.service'
import type { GameGuild } from '@/interfaces/GameGuild.interface'

defineOptions({ name: 'DkpSystemView' })

const route = useRoute()
const guildId = ref<string | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)
const guild = ref<GameGuild | null>(null)

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
</script>

<style scoped>
</style>
