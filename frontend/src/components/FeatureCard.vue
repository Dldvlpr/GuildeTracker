<template>
  <RouterLink
    v-if="available"
    :to="route"
    class="block group"
  >
    <div
      class="relative rounded-2xl border border-white/10 bg-white/5 p-6 transition hover:bg-white/10 hover:shadow-lg hover:shadow-black/20 hover:border-white/20 cursor-pointer"
      :class="priorityClasses"
    >
      <div class="flex items-start justify-between">
        <div class="text-xs font-bold text-slate-400 mb-4 px-2 py-1 bg-slate-700 rounded">{{ icon }}</div>
        <div v-if="available" class="flex items-center gap-1 text-xs text-emerald-400">
          <div class="w-2 h-2 rounded-full bg-emerald-400"></div>
          Disponible
        </div>
        <div v-else class="flex items-center gap-1 text-xs text-slate-500">
          <div class="w-2 h-2 rounded-full bg-slate-500"></div>
          Bientôt
        </div>
      </div>

      <h3 class="text-lg font-semibold text-white mb-2 group-hover:text-white transition">
        {{ title }}
      </h3>

      <p class="text-sm text-slate-300 leading-relaxed">
        {{ description }}
      </p>

      <div class="mt-4 flex items-center text-sm text-indigo-400 group-hover:text-indigo-300 transition">
        <span>Accéder</span>
        <svg class="ml-1 w-4 h-4 transform group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
        </svg>
      </div>
    </div>
  </RouterLink>

  <div
    v-else
    class="relative rounded-2xl border border-white/5 bg-white/[0.02] p-6 opacity-60 cursor-not-allowed"
    :class="priorityClasses"
  >
    <div class="flex items-start justify-between">
      <div class="text-xs font-bold text-slate-500 mb-4 px-2 py-1 bg-slate-800 rounded grayscale">{{ icon }}</div>
      <div class="flex items-center gap-1 text-xs text-slate-500">
        <div class="w-2 h-2 rounded-full bg-slate-500"></div>
        Bientôt
      </div>
    </div>

    <h3 class="text-lg font-semibold text-slate-400 mb-2">
      {{ title }}
    </h3>

    <p class="text-sm text-slate-500 leading-relaxed">
      {{ description }}
    </p>

    <div class="mt-4 flex items-center text-sm text-slate-500">
      <span>En développement</span>
      <svg class="ml-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
      </svg>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'

interface Props {
  title: string
  description: string
  icon: string
  available: boolean
  route: string
  priority: 'high' | 'medium' | 'low'
}

const props = defineProps<Props>()

const priorityClasses = computed(() => {
  if (!props.available) return ''

  switch (props.priority) {
    case 'high':
      return 'ring-1 ring-red-500/10 hover:ring-red-400/20'
    case 'medium':
      return 'ring-1 ring-indigo-500/10 hover:ring-indigo-400/20'
    case 'low':
      return 'ring-1 ring-emerald-500/10 hover:ring-emerald-400/20'
    default:
      return ''
  }
})
</script>

<style scoped>
</style>