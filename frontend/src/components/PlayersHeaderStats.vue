<template>
  <div
    class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between border-b border-white/10 px-4 md:px-6 py-4"
    role="region"
    aria-label="Players header and stats"
  >
    <h2 class="text-xl md:text-2xl font-bold text-white">Players</h2>
    <div class="flex flex-wrap gap-2">
      <span class="inline-flex items-center rounded-full bg-white/5 px-3 py-1 text-xs font-semibold text-slate-200 ring-1 ring-inset ring-white/10">Total: {{ total }}</span>
      <span class="inline-flex items-center rounded-full bg-blue-500/15 px-3 py-1 text-xs font-semibold text-blue-100 ring-1 ring-inset ring-blue-400/20">Tanks: {{ tanks }}</span>
      <span class="inline-flex items-center rounded-full bg-emerald-500/15 px-3 py-1 text-xs font-semibold text-emerald-100 ring-1 ring-inset ring-emerald-400/20">Healers: {{ healers }}</span>
      <span class="inline-flex items-center rounded-full bg-rose-500/15 px-3 py-1 text-xs font-semibold text-rose-100 ring-1 ring-inset ring-rose-400/20">DPS: {{ dps }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  total: number
  tanks: number
  healers: number
  dps: number
}>()
</script>
<style scoped>
</style>
