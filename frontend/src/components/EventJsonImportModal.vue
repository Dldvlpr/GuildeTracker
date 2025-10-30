<template>
  <BaseModal
    :model-value="modelValue"
    @update:model-value="(v) => emit('update:modelValue', v)"
    title="Import players from event JSON"
    size="lg"
  >
    <div class="space-y-4">
      <p class="text-sm text-slate-400">
        Paste your event JSON (must contain a <code class="px-1.5 py-0.5 rounded bg-slate-800 text-indigo-300 text-xs">signUps</code> array). We will extract players and map them to characters.
      </p>

      <textarea
        v-model="text"
        class="w-full min-h-[280px] px-4 py-3 rounded-xl border border-white/10 bg-slate-950/50 text-slate-100 font-mono text-sm placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-y"
        :placeholder="placeholder"
        spellcheck="false"
      />

      <div v-if="error" class="rounded-xl bg-red-500/10 border border-red-500/20 p-4" role="alert">
        <p class="text-sm text-red-300">{{ error }}</p>
      </div>

      <details class="rounded-xl bg-white/5 border border-white/10 px-4 py-3 cursor-pointer">
        <summary class="text-sm font-medium text-slate-300">View required JSON shape</summary>
        <pre class="mt-3 text-xs text-slate-400 font-mono overflow-x-auto">{
  "date": "1-9-2025",
  "signUps": [
    { "name": "shaqx", "className": "Druid", "specName": "Feral", "roleName": "Melee" }
  ]
}</pre>
      </details>

      <div class="space-y-2">
        <label class="flex items-center gap-2 text-sm text-slate-300 cursor-pointer">
          <input
            type="checkbox"
            v-model="includeNonPlayableClasses"
            class="w-4 h-4 rounded border-white/10 bg-slate-950/50 text-indigo-600 focus:ring-2 focus:ring-indigo-500"
          />
          Include non-playable classes (Bench, Absence, Tentative, Late)
        </label>
        <label class="flex items-center gap-2 text-sm text-slate-300 cursor-pointer">
          <input
            type="checkbox"
            v-model="preferSpecRole"
            class="w-4 h-4 rounded border-white/10 bg-slate-950/50 text-indigo-600 focus:ring-2 focus:ring-indigo-500"
          />
          If spec has a role, prefer it over roleName
        </label>
      </div>
    </div>

    <template #footer>
      <div class="flex flex-col sm:flex-row gap-3 w-full">
        <button
          @click="close"
          class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-semibold text-slate-300 ring-1 ring-inset ring-white/10 hover:bg-white/5 hover:text-white transition"
        >
          Cancel
        </button>
        <button
          @click="confirm"
          :disabled="!text.trim()"
          class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-semibold bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-600 text-white shadow-lg shadow-indigo-600/50 transition disabled:opacity-50 disabled:cursor-not-allowed"
        >
          Import
        </button>
      </div>
    </template>
  </BaseModal>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import BaseModal from './ui/BaseModal.vue'

type RoleName = 'Tanks' | 'Healers' | 'Melee' | 'Ranged'

type Signup = {
  name: string
  className?: string
  specName?: string
  roleName?: RoleName
  level?: number
}

type EventJson = {
  date?: string
  signUps: Signup[]
}

type Character = {
  id?: string
  name: string
  class: string
  spec?: string
  role?: RoleName
  level?: number
  createdAt?: string
  status?: 'active' | 'inactive'
}

const NON_PLAYABLE = new Set(['Bench', 'Absence', 'Tentative', 'Late'])

defineProps<{
  modelValue: boolean
}>()
const emit = defineEmits<{
  (e: 'update:modelValue', v: boolean): void
  (e: 'confirm', payload: Character[]): void
}>()

const text = ref('')
const error = ref('')
const includeNonPlayableClasses = ref(false)
const preferSpecRole = ref(true)

const placeholder = `{
  "date": "1-9-2025",
  "signUps": [
    {
      "name": "shaqx",
      "className": "Druid",
      "specName": "Feral",
      "roleName": "Melee",
      "level": 60
    }
  ]
}`

const close = () => {
  text.value = ''
  error.value = ''
  emit('update:modelValue', false)
}

function toCharacter(s: Signup): Character | null {
  if (!s || typeof s.name !== 'string' || !s.name.trim()) return null
  const cls = (s.className ?? '').trim()
  if (!cls) return null

  if (!includeNonPlayableClasses.value && NON_PLAYABLE.has(cls)) return null

  const role = s.roleName as RoleName | undefined

  return {
    name: s.name.trim(),
    class: cls,
    spec: s.specName?.trim() || undefined,
    role: role,
    level: typeof s.level === 'number' ? s.level : undefined,
    status: 'active',
  }
}

const confirm = () => {
  error.value = ''
  try {
    const parsed = JSON.parse(text.value) as EventJson
    if (!parsed || !Array.isArray(parsed.signUps)) {
      error.value = 'Invalid JSON: missing "signUps" array.'
      return
    }

    const mapped = parsed.signUps.map(toCharacter).filter((c): c is Character => c !== null)

    if (mapped.length === 0) {
      error.value = 'No valid players to import after filtering.'
      return
    }

    const seen = new Set<string>()
    const dedup = mapped.filter((c) => {
      const key = c.name.toLowerCase()
      if (seen.has(key)) return false
      seen.add(key)
      return true
    })

    emit('confirm', dedup)
    close()
  } catch (e) {
    error.value = (e as Error).message || 'Invalid JSON.'
  }
}
</script>

<style scoped>
</style>

