<template>
  <div
    class="relative rounded-2xl border border-white/10 bg-white/5 p-5 transition hover:bg-white/10 hover:shadow-lg hover:shadow-black/20"
    :aria-label="`Character card for ${character.name}`"
  >
    <div class="flex items-center justify-between gap-3">
      <h3 class="text-base md:text-lg font-bold text-white truncate">{{ character.name }}</h3>
      <span
        v-if="character.level"
        class="inline-flex items-center rounded-full bg-white/5 px-2 py-1 text-xs font-semibold text-slate-300 ring-1 ring-inset ring-white/10"
      >
        Lv. {{ character.level }}
      </span>
    </div>

    <div class="mt-3 space-y-2">
      <div class="text-sm text-slate-200">
        <strong class="font-semibold">{{ character.class }}</strong>
        <span v-if="character.spec" class="text-slate-400"> — {{ character.spec }}</span>
      </div>
      <div v-if="character.role">
        <span
          class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-semibold ring-1 ring-inset"
          :class="roleBadgeClasses"
        >
          {{ roleLabel }}
        </span>
      </div>
    </div>

    <div class="mt-4 text-xs text-slate-400">
      <span>Created {{ prettyDate }}</span>
    </div>

    <div class="absolute top-2 right-2 flex gap-2" aria-label="Character actions">
      <button
        class="inline-flex items-center justify-center rounded-lg bg-white/5 px-2.5 py-1.5 text-sm ring-1 ring-inset ring-white/10 hover:bg-white/10 transition"
        title="Edit"
        @click="$emit('edit', character)"
      >
        ✏️
      </button>
      <button
        class="inline-flex items-center justify-center rounded-lg bg-white/5 px-2.5 py-1.5 text-sm ring-1 ring-inset ring-white/10 hover:bg-rose-500/10 hover:ring-rose-400/30 transition"
        title="Delete"
        @click="$emit('delete', character.id)"
      >
        🗑️
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Character, Role } from '@/interfaces/game.interface'
import { ROLE_ICONS } from '@/interfaces/game.interface'

const props = defineProps<{ character: Character }>()

const prettyDate = computed(() =>
  new Date(props.character.createdAt).toLocaleDateString('en-GB', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }),
)

const roleLabel = computed(() => {
  const role = props.character.role as Role | undefined
  return role ? `${ROLE_ICONS[role]} ${role}` : ''
})

const roleBadgeClasses = computed(() => {
  const role = props.character.role as Role | undefined
  switch (role) {
    case 'Tanks':
      return 'bg-blue-500/15 text-blue-200 ring-blue-400/20'
    case 'Healers':
      return 'bg-emerald-500/15 text-emerald-200 ring-emerald-400/20'
    case 'Melee':
      return 'bg-rose-500/15 text-rose-200 ring-rose-400/20'
    case 'Ranged':
      return 'bg-amber-500/15 text-amber-200 ring-amber-400/20'
    default:
      return 'bg-white/10 text-slate-200 ring-white/10'
  }
})
</script>

<style scoped>
</style>
