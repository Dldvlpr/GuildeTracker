<template>
  <div
    v-if="modelValue"
    class="fixed inset-0 z-[1000] grid place-items-center bg-black/50 backdrop-blur-sm"
    @click.self="close"
    role="dialog"
    aria-modal="true"
    aria-labelledby="import-title"
  >
    <div
      class="w-[min(900px,94vw)] overflow-hidden rounded-2xl border border-white/10 bg-slate-900/80 text-slate-100 shadow-2xl backdrop-blur"
    >
      <header class="flex items-center justify-between gap-2 border-b border-white/10 px-4 py-3">
        <h3 id="import-title" class="text-base font-semibold">Import players from event JSON</h3>
        <button
          class="inline-flex items-center justify-center rounded-lg px-2.5 py-1.5 text-sm text-slate-200 ring-1 ring-inset ring-white/10 hover:bg-white/5"
          @click="close"
          aria-label="Close"
        >
          ✖
        </button>
      </header>

      <section class="grid gap-3 px-4 py-4">
        <p class="m-0 text-sm text-slate-400">
          Paste your event JSON (must contain a <code>signUps</code> array). We will extract players
          and map them to characters.
        </p>

        <textarea
          v-model="text"
          class="min-h-[260px] w-full resize-y rounded-xl bg-slate-950/60 px-3 py-2 text-sm text-slate-100 ring-1 ring-inset ring-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-300 font-mono"
          :placeholder="placeholder"
          rows="14"
          spellcheck="false"
        />

        <div v-if="error" class="text-sm font-semibold text-rose-400" role="alert">{{ error }}</div>

        <details class="rounded-xl bg-white/5 px-3 py-2">
          <summary class="cursor-pointer text-sm">Required shape</summary>
          <pre class="m-0 mt-1 whitespace-pre-wrap text-xs text-slate-300">{
  "date": "1-9-2025",
  "signUps": [
    { "name": "shaqx", "className": "Druid", "specName": "Feral", "roleName": "Melee" }
  ]
}</pre>
        </details>

        <div class="grid gap-1">
          <label class="inline-flex items-center gap-2 text-sm text-slate-300">
            <input type="checkbox" v-model="includeNonPlayableClasses" />
            Include non-playable classes (Bench, Absence, Tentative, Late)
          </label>
          <label class="inline-flex items-center gap-2 text-sm text-slate-300">
            <input type="checkbox" v-model="preferSpecRole" />
            If spec has a role, prefer it over roleName
          </label>
        </div>
      </section>

      <footer class="flex items-center justify-end gap-2 border-t border-white/10 px-4 py-3">
        <button
          class="inline-flex items-center gap-2 rounded-xl bg-white/0 px-3 py-2 text-sm font-semibold text-slate-200 ring-1 ring-inset ring-white/10 transition hover:bg-white/5 hover:text-white"
          @click="close"
        >
          Cancel
        </button>
        <button
          class="inline-flex items-center gap-2 rounded-xl bg-indigo-600/90 px-3 py-2 text-sm font-semibold text-white shadow-lg shadow-indigo-600/30 transition hover:bg-indigo-500 disabled:opacity-50"
          @click="confirm"
          :disabled="!text.trim()"
        >
          Import
        </button>
      </footer>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'

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

