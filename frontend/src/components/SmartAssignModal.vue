<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue'
import BaseModal from './ui/BaseModal.vue'
import type { RaidPlanBlock } from '@/interfaces/raidPlan.interface'
import type { Character } from '@/interfaces/game.interface'
import { getRoleByClassAndSpec, getSpecOptions, isValidRole } from '@/data/gameData'
import type { Role } from '@/interfaces/game.interface'

type TargetKind = 'ROLE_MATRIX' | 'GROUPS_GRID'

interface Props {
  modelValue: boolean
  blocks: RaidPlanBlock[]
  characters: Character[]
  selectedBlockId?: string | null
}

const props = defineProps<Props>()
const emit = defineEmits<{
  (e: 'update:modelValue', v: boolean): void
  (e: 'apply', block: RaidPlanBlock): void
}>()

const open = computed({
  get: () => props.modelValue,
  set: (v: boolean) => emit('update:modelValue', v)
})

const targetId = ref<string | null>(null)
const options = reactive({
  preferPrimary: true,
  avoidDuplicates: true,
  benchExclusive: true,
})

// Role targets for role matrix
const targets = reactive<{ Tanks: number; Healers: number; Melee: number; Ranged: number }>({ Tanks: 2, Healers: 4, Melee: 7, Ranged: 7 })

watch(() => props.modelValue, (v) => {
  if (v) initDefaults()
})

function initDefaults() {
  // default target: selected block if suitable, else first GROUPS or ROLE_MATRIX
  const pre = props.blocks.find(b => b.id === props.selectedBlockId && (b.type === 'ROLE_MATRIX' || b.type === 'GROUPS_GRID'))
  const fallback = props.blocks.find(b => b.type === 'GROUPS_GRID') || props.blocks.find(b => b.type === 'ROLE_MATRIX')
  targetId.value = pre?.id || fallback?.id || null
  // default targets based on groups size when applicable
  const gg = pre?.type === 'GROUPS_GRID' ? pre : (fallback?.type === 'GROUPS_GRID' ? fallback : null)
  if (gg) {
    const total = Number((gg.data as any)?.groupCount || 0) * Number((gg.data as any)?.playersPerGroup || 0)
    const dps = Math.max(0, total - (targets.Tanks + targets.Healers))
    targets.Melee = Math.floor(dps / 2)
    targets.Ranged = dps - targets.Melee
  }
}

const targetableBlocks = computed(() => props.blocks.filter(b => b.type === 'ROLE_MATRIX' || b.type === 'GROUPS_GRID'))
const targetBlock = computed<RaidPlanBlock | null>(() => props.blocks.find(b => b.id === targetId.value) || null)
const targetKind = computed<TargetKind | null>(() => (targetBlock.value && (targetBlock.value.type === 'ROLE_MATRIX' || targetBlock.value.type === 'GROUPS_GRID')) ? targetBlock.value.type as TargetKind : null)

// Helpers
function benchSet(): Set<string> {
  const set = new Set<string>()
  for (const b of props.blocks) {
    if (b.type !== 'BENCH_ROSTER') continue
    for (const id of ((b.data?.bench ?? []) as string[])) set.add(String(id))
  }
  return set
}

function deriveRole(c: Character, preferPrimary: boolean): Role | null {
  const pri = c.spec || ''
  const sec = (c as any)?.specSecondary as string | undefined
  const trySpec = (sp?: string | null): Role | null => {
    if (!sp || !c.class) return null
    try { return getRoleByClassAndSpec(c.class, sp) as Role } catch { return null }
  }
  const fromSpec = preferPrimary ? (trySpec(pri) || trySpec(sec)) : (trySpec(sec) || trySpec(pri))
  // Fallback to existing role field if available (normalize variants like "Tank" -> "Tanks")
  const norm = normalizeRole((c as any).role)
  return fromSpec || norm
}

function normalizeRole(r: unknown): Role | null {
  if (!r || typeof r !== 'string') return null
  const s = r.toLowerCase()
  if (s === 'tanks' || s === 'tank') return 'Tanks'
  if (s === 'healers' || s === 'healer') return 'Healers'
  if (s === 'melee') return 'Melee'
  if (s === 'ranged') return 'Ranged'
  // Accept exact Role values
  if (isValidRole(r)) return r as Role
  return null
}

function roleMatrixAssignments(block: RaidPlanBlock): Record<Role, string[]> {
  const ra = (block.data?.roleAssignments ?? {}) as Record<Role, string[]>
  return {
    Tanks: [...(ra.Tanks || [])],
    Healers: [...(ra.Healers || [])],
    Melee: [...(ra.Melee || [])],
    Ranged: [...(ra.Ranged || [])],
  } as Record<Role, string[]>
}

function roleByCharAcrossPlan(): Map<string, Role> {
  const map = new Map<string, Role>()
  // Prefer explicit Role Matrix blocks
  for (const b of props.blocks) {
    if (b.type === 'ROLE_MATRIX') {
      const ra = roleMatrixAssignments(b)
      ;(['Tanks','Healers','Melee','Ranged'] as Role[]).forEach((r) => (ra[r] || []).forEach(id => map.set(String(id), r)))
    }
  }
  // Derive for others missing
  for (const c of props.characters) {
    if (map.has(c.id)) continue
    const r = deriveRole(c, true)
    if (r) map.set(c.id, r)
  }
  return map
}

// Preview state
const preview = ref<{ summary: string; matrix?: Record<Role, string[]>; groups?: { id: string; title: string; members: string[] }[] } | null>(null)
const warn = ref<string | null>(null)
const lastBefore = ref<RaidPlanBlock | null>(null)
const lastAfter = ref<RaidPlanBlock | null>(null)

function computePreview() {
  warn.value = null
  const tgt = targetBlock.value
  if (!tgt) { preview.value = null; return }
  const benched = benchSet()
  const eligible = props.characters.filter(c => !options.benchExclusive || !benched.has(c.id))
  if (targetKind.value === 'ROLE_MATRIX') {
    // Fill missing counts per role
    const ra = roleMatrixAssignments(tgt)
    const already = new Set<string>([...ra.Tanks, ...ra.Healers, ...ra.Melee, ...ra.Ranged].map(String))
    const need: Record<Role, number> = {
      Tanks: Math.max(0, targets.Tanks - ra.Tanks.length),
      Healers: Math.max(0, targets.Healers - ra.Healers.length),
      Melee: Math.max(0, targets.Melee - ra.Melee.length),
      Ranged: Math.max(0, targets.Ranged - ra.Ranged.length),
    }
    const roleBuckets: Record<Role, Character[]> = { Tanks: [], Healers: [], Melee: [], Ranged: [] } as any
    for (const c of eligible) {
      if (options.avoidDuplicates && already.has(c.id)) continue
      const r = deriveRole(c, options.preferPrimary)
      if (r && roleBuckets[r]) roleBuckets[r].push(c)
    }
    const byName = (a: Character, b: Character) => (a.name || '').localeCompare(b.name || '')
    ;(['Tanks','Healers','Melee','Ranged'] as Role[]).forEach((r) => roleBuckets[r].sort(byName))
    let added = 0
    for (const r of (['Tanks','Healers','Melee','Ranged'] as Role[])) {
      const take = roleBuckets[r].slice(0, need[r]).map(c => c.id)
      ra[r] = [...ra[r], ...take]
      added += take.length
    }
    preview.value = { summary: `Will add ${added} assignment(s)`, matrix: ra }
    if (added === 0) warn.value = 'No eligible players to add for requested targets.'
    return
  }
  if (targetKind.value === 'GROUPS_GRID') {
    // Fill groups using role mapping, round-robin by role then by name
    const groups = ((tgt.data?.groups ?? []) as { id: string; title: string; members: string[] }[]).map(g => ({ ...g, members: [...(g.members || [])] }))
    const groupSize = Number((tgt.data as any)?.playersPerGroup || 5)
    const roleMap = roleByCharAcrossPlan()
    const already = new Set<string>()
    for (const g of groups) for (const id of (g.members || [])) already.add(String(id))
    const pool = eligible.filter(c => !already.has(c.id))
    const byName = (a: Character, b: Character) => (a.name || '').localeCompare(b.name || '')
    const Tanks = pool.filter(c => roleMap.get(c.id) === 'Tanks').sort(byName)
    const Healers = pool.filter(c => roleMap.get(c.id) === 'Healers').sort(byName)
    const DPS = pool.filter(c => ['Melee','Ranged'].includes(roleMap.get(c.id) as any)).sort(byName)
    // Distribute: 1 pass for Tanks, 1 pass for Healers, then fill with DPS
    let added = 0
    const takeFrom = (arr: Character[] | null, g: any) => {
      if (!arr || arr.length === 0) return false
      if (g.members.length >= groupSize) return false
      const c = arr.shift()!
      g.members.push(c.id)
      added++
      return true
    }
    // Tanks pass
    for (const g of groups) takeFrom(Tanks, g)
    // Healers pass
    for (const g of groups) takeFrom(Healers, g)
    // Fill remaining with DPS round-robin
    let gi = 0
    while (DPS.length) {
      const g = groups[gi % groups.length]
      if (!takeFrom(DPS, g)) break
      gi++
      // stop if all full
      if (groups.every(x => x.members.length >= groupSize)) break
    }
    preview.value = { summary: `Will assign ${added} player(s) into groups`, groups }
    if (added === 0) warn.value = 'No eligible players to assign or groups already full.'
    return
  }
  preview.value = null
}

watch([targetId, () => options.preferPrimary, () => options.avoidDuplicates, () => options.benchExclusive, () => targets.Tanks, () => targets.Healers, () => targets.Melee, () => targets.Ranged], () => computePreview())
watch(() => props.blocks, () => computePreview(), { deep: true })

function applyChanges() {
  const tgt = targetBlock.value
  if (!tgt || !preview.value) return
  if (targetKind.value === 'ROLE_MATRIX' && preview.value.matrix) {
    const next: RaidPlanBlock = { ...tgt, data: { ...(tgt.data || {}), roleAssignments: preview.value.matrix } }
    lastBefore.value = JSON.parse(JSON.stringify(tgt))
    lastAfter.value = JSON.parse(JSON.stringify(next))
    emit('apply', next)
    return
  }
  if (targetKind.value === 'GROUPS_GRID' && preview.value.groups) {
    const next: RaidPlanBlock = { ...tgt, data: { ...(tgt.data || {}), groups: preview.value.groups } }
    lastBefore.value = JSON.parse(JSON.stringify(tgt))
    lastAfter.value = JSON.parse(JSON.stringify(next))
    emit('apply', next)
    return
  }
}

function undoApply() {
  if (!lastBefore.value) return
  emit('apply', JSON.parse(JSON.stringify(lastBefore.value)))
}

function redoApply() {
  if (!lastAfter.value) return
  emit('apply', JSON.parse(JSON.stringify(lastAfter.value)))
}

</script>

<template>
  <BaseModal :model-value="open" @update:model-value="(v) => (open = v)" @close="open = false" title="🧠 Smart Assign" size="lg">
    <div class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
          <label class="block text-[11px] font-semibold text-slate-400 mb-1">Target Block</label>
          <select v-model="targetId" class="w-full bg-slate-900/60 border border-slate-700 rounded-md px-2 py-1 text-sm">
            <option v-for="tb in targetableBlocks" :key="tb.id" :value="tb.id">
              {{ tb.title || tb.type }} — {{ tb.type }}
            </option>
          </select>
        </div>
        <div class="grid grid-cols-3 gap-2">
          <label class="inline-flex items-center gap-2 text-xs text-slate-300"><input type="checkbox" v-model="options.preferPrimary"/> Prefer primary spec</label>
          <label class="inline-flex items-center gap-2 text-xs text-slate-300"><input type="checkbox" v-model="options.avoidDuplicates"/> Avoid duplicates</label>
          <label class="inline-flex items-center gap-2 text-xs text-slate-300"><input type="checkbox" v-model="options.benchExclusive"/> Respect bench</label>
        </div>
      </div>

      <div v-if="targetKind === 'ROLE_MATRIX'" class="rounded border border-slate-700 bg-slate-900/50 p-3">
        <div class="text-[11px] text-slate-400 mb-2">Target counts by role</div>
        <div class="grid grid-cols-4 gap-2">
          <label class="text-xs text-slate-300">Tanks <input type="number" min="0" class="w-16 ml-2 bg-slate-900 border border-slate-700 rounded px-1 py-0.5" v-model.number="targets.Tanks"/></label>
          <label class="text-xs text-slate-300">Healers <input type="number" min="0" class="w-16 ml-2 bg-slate-900 border border-slate-700 rounded px-1 py-0.5" v-model.number="targets.Healers"/></label>
          <label class="text-xs text-slate-300">Melee <input type="number" min="0" class="w-16 ml-2 bg-slate-900 border border-slate-700 rounded px-1 py-0.5" v-model.number="targets.Melee"/></label>
          <label class="text-xs text-slate-300">Ranged <input type="number" min="0" class="w-16 ml-2 bg-slate-900 border border-slate-700 rounded px-1 py-0.5" v-model.number="targets.Ranged"/></label>
        </div>
      </div>

      <div v-if="warn" class="rounded border border-amber-600/40 bg-amber-900/20 px-3 py-2 text-xs text-amber-300">⚠️ {{ warn }}</div>

      <div class="space-y-2">
        <div class="text-xs text-slate-400">Preview</div>
        <div v-if="preview && preview.matrix" class="grid grid-cols-4 gap-2 text-xs">
          <div v-for="role in ['Tanks','Healers','Melee','Ranged']" :key="role" class="rounded border border-slate-700 bg-slate-900/50 p-2">
            <div class="font-semibold text-slate-200 mb-1">{{ role }}</div>
            <ul class="space-y-0.5">
              <li v-for="cid in preview.matrix[role as Role]" :key="cid" class="text-slate-300">• {{ characters.find(c => c.id === cid)?.name || cid }}</li>
            </ul>
          </div>
        </div>
        <div v-else-if="preview && preview.groups" class="grid grid-cols-2 md:grid-cols-3 gap-2 text-xs">
          <div v-for="g in preview.groups" :key="g.id" class="rounded border border-slate-700 bg-slate-900/50 p-2">
            <div class="font-semibold text-slate-200 mb-1">{{ g.title }}</div>
            <ul class="space-y-0.5">
              <li v-for="cid in g.members" :key="cid" class="text-slate-300">• {{ characters.find(c => c.id === cid)?.name || cid }}</li>
            </ul>
          </div>
        </div>
        <div v-else class="text-xs text-slate-500">Select a target to preview changes</div>
      </div>
    </div>

    <template #footer>
      <div class="flex items-center justify-between w-full">
        <div class="flex items-center gap-2">
          <div class="text-xs text-slate-400">{{ preview?.summary || 'No changes' }}</div>
          <button class="px-2 py-1 rounded-md border border-slate-700 hover:bg-slate-800 text-xs" :disabled="!lastBefore" @click="undoApply">Undo</button>
          <button class="px-2 py-1 rounded-md border border-slate-700 hover:bg-slate-800 text-xs" :disabled="!lastAfter" @click="redoApply">Redo</button>
        </div>
        <div class="space-x-2">
          <button class="px-3 py-1.5 rounded-md border border-slate-700 hover:bg-slate-800 text-sm" @click="open = false">Cancel</button>
          <button class="px-3 py-1.5 rounded-md bg-emerald-600 hover:bg-emerald-500 text-white text-sm disabled:opacity-50" :disabled="!preview" @click="applyChanges">Apply</button>
        </div>
      </div>
    </template>
  </BaseModal>
</template>
