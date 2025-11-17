<script setup lang="ts">
import { computed, ref } from 'vue'
import type { Character, Role } from '@/interfaces/game.interface'
import { getClassColor } from '@/utils/classColors'
import { getRoleByClassAndSpec } from '@/data/gameData'

const props = defineProps<{ characters: Character[]; loading?: boolean; error?: string | null }>()
const emit = defineEmits<{ (e: 'quick-assign', character: Character): void }>()

const search = ref('')
const roleFilter = ref<Role | 'ALL'>('ALL')
const specFilter = ref<string | 'ALL'>('ALL')

const roles: { key: Role | 'ALL'; label: string }[] = [
  { key: 'ALL', label: 'All' },
  { key: 'Tanks' as Role, label: 'Tanks' },
  { key: 'Healers' as Role, label: 'Healers' },
  { key: 'Melee' as Role, label: 'Melee' },
  { key: 'Ranged' as Role, label: 'Ranged' },
]

function charRole(c: Character): Role | undefined {
  const sp = c.spec
  if (sp && c.class) {
    try { return getRoleByClassAndSpec(c.class, sp) as Role } catch {}
  }
  return c.role as Role | undefined
}

const filtered = computed(() => {
  const term = search.value.trim().toLowerCase()
  return props.characters.filter((c) => {
    const r = charRole(c)
    const matchesRole = roleFilter.value === 'ALL' || r === roleFilter.value
    const matchesSpec = specFilter.value === 'ALL' || (c.spec === specFilter.value)
    const matchesText = !term || c.name.toLowerCase().includes(term) || c.class.toLowerCase().includes(term) || (c.spec?.toLowerCase().includes(term) ?? false)
    return matchesRole && matchesSpec && matchesText
  })
})

function onDragStart(e: DragEvent, c: Character) {
  if (!e.dataTransfer) return
  e.dataTransfer.setData('text/plain', c.id)
  e.dataTransfer.effectAllowed = 'copyMove'
}

function quickAssign(c: Character) {
  emit('quick-assign', c)
}

function setRoleFilter(r: Role | 'ALL') {
  roleFilter.value = r
}

const specOptions = computed(() => {
  const s = new Set(props.characters.map(c => c.spec).filter((x): x is string => !!x))
  return Array.from(s).sort()
})

defineExpose({ setRoleFilter })
</script>

<template>
  <div class="flex flex-col gap-3 text-sm">
    <div class="flex items-center gap-2">
      <input
        v-model="search"
        type="text"
        placeholder="Search name/class/spec"
        class="flex-1 bg-slate-900/80 border border-slate-700 rounded-md px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500"
      />
    </div>

    <div class="flex gap-1 text-xs">
      <button
        v-for="r in roles"
        :key="r.key as string"
        class="px-2 py-1 rounded border text-slate-300"
        :class="roleFilter === r.key ? 'bg-emerald-600 border-emerald-500 text-slate-950' : 'border-slate-700 hover:bg-slate-800'"
        @click="roleFilter = r.key"
      >
        {{ r.label }}
      </button>
    </div>

    <div class="flex items-center gap-2 text-xs">
      <label class="text-slate-400">Spec</label>
      <select v-model="specFilter" class="flex-1 bg-slate-900/80 border border-slate-700 rounded-md px-2 py-1">
        <option value="ALL">All</option>
        <option v-for="sp in specOptions" :key="sp" :value="sp">{{ sp }}</option>
      </select>
    </div>

    <div v-if="loading" class="text-slate-400 text-xs">Loading characters...</div>
    <div v-else-if="error" class="text-red-400 text-xs">{{ error }}</div>

    <div class="space-y-1" v-else>
      <div
        v-for="c in filtered"
        :key="c.id"
        class="flex items-center justify-between rounded-md border border-slate-700 bg-slate-900/70 px-2 py-1"
        draggable="true"
        @dragstart="onDragStart($event, c)"
        title="Drag to assign — double‑click to quick assign to selected block"
        @dblclick="quickAssign(c)"
        :style="{ borderLeft: '4px solid ' + getClassColor(c.class) }"
      >
        <div class="flex items-center gap-2 min-w-0">
          <div class="h-6 w-6 rounded grid place-items-center text-[11px] text-slate-900"
               :style="{ backgroundColor: getClassColor(c.class) }">
            {{ (c.name[0] ?? '?').toUpperCase() }}
          </div>
          <div class="leading-tight min-w-0">
            <div class="text-slate-100 truncate">{{ c.name }}</div>
            <div class="text-[11px] text-slate-400 truncate">{{ c.class }}<span v-if="c.spec"> • {{ c.spec }}</span></div>
          </div>
        </div>
        <span class="text-[11px] text-slate-400 ml-2 shrink-0">{{ charRole(c) ?? '—' }}</span>
      </div>
      <div v-if="!filtered.length" class="text-xs text-slate-500 italic py-2 text-center">No characters</div>
    </div>
  </div>
</template>
