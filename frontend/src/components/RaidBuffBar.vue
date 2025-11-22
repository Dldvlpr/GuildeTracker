<script setup lang="ts">
import { computed } from 'vue'
import { spellIconUrl } from '@/utils/wowIcons'

type Buff = {
  key: string
  id: number
  name: string
  class?: string
  category?: string
  description?: string
  iconFile?: string | null
}

const props = defineProps<{
  spells: Buff[]
}>()

const items = computed(() =>
  props.spells.map((s) => ({
    ...s,
    icon: spellIconUrl(s.iconFile),
  })),
)
</script>

<template>
  <div class="mt-4 rounded-md border border-slate-800 bg-slate-900/60 p-3">
    <div class="flex items-center justify-between mb-2">
      <div class="text-[11px] uppercase tracking-wide text-slate-400 font-semibold">Buffs raid</div>
      <div class="text-[11px] text-slate-500">Survole pour la description</div>
    </div>
    <div class="flex flex-wrap gap-2">
      <div
        v-for="buff in items"
        :key="buff.key"
        class="group relative flex items-center gap-2 rounded-md border border-slate-800 bg-slate-950/50 px-2 py-1 text-xs text-slate-200 hover:border-emerald-600/60"
      >
        <img :src="buff.icon" alt="" class="h-8 w-8 rounded-md border border-slate-800 bg-slate-900 object-cover" />
        <div class="leading-tight">
          <div class="font-semibold">{{ buff.name }}</div>
          <div class="text-[10px] text-slate-400">
            {{ buff.class || '—' }}
          </div>
        </div>
        <div
          class="pointer-events-none absolute left-0 top-full z-10 mt-2 w-64 rounded-md border border-slate-800 bg-slate-900/95 p-3 shadow-xl opacity-0 transition group-hover:opacity-100"
        >
          <div class="text-sm font-semibold text-slate-100 mb-1">{{ buff.name }}</div>
          <div class="text-[11px] text-slate-400 mb-1" v-if="buff.category">Catégorie : {{ buff.category }}</div>
          <div class="text-[11px] text-slate-300 whitespace-pre-line">
            {{ buff.description || 'Buff raid' }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
