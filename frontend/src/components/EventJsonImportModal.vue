<template>
  <div
    v-if="modelValue"
    class="backdrop"
    @click.self="close"
    role="dialog"
    aria-modal="true"
    aria-labelledby="import-title"
  >
    <div class="modal">
      <header class="modal__header">
        <h3 id="import-title">Import players from event JSON</h3>
        <button class="icon-btn" @click="close" aria-label="Close">✖</button>
      </header>

      <section class="modal__body">
        <p class="hint">
          Paste your event JSON (must contain a <code>signUps</code> array). We will extract players
          and map them to characters.
        </p>

        <textarea
          v-model="text"
          class="textarea"
          :placeholder="placeholder"
          rows="14"
          spellcheck="false"
        ></textarea>

        <div v-if="error" class="error" role="alert">{{ error }}</div>

        <details class="example">
          <summary>Required shape</summary>
          <pre>
{
  "date": "1-9-2025",
  "signUps": [
    { "name": "shaqx", "className": "Druid", "specName": "Feral", "roleName": "Melee" }
  ]
}</pre
          >
        </details>

        <div class="options">
          <label class="opt">
            <input type="checkbox" v-model="includeNonPlayableClasses" />
            Include non-playable classes (Bench, Absence, Tentative, Late)
          </label>
          <label class="opt">
            <input type="checkbox" v-model="preferSpecRole" />
            If spec has a role, prefer it over roleName
          </label>
        </div>
      </section>

      <footer class="modal__footer">
        <button class="btn btn-outline" @click="close">Cancel</button>
        <button class="btn btn-primary" @click="confirm" :disabled="!text.trim()">Import</button>
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

const props = defineProps<{
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

  // skip non playable classes unless allowed
  if (!includeNonPlayableClasses.value && NON_PLAYABLE.has(cls)) return null

  // prefer role from spec mapping? (we only have incoming text, so we trust roleName; hook provided for future mapping)
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

    // Optional: dedup by name (case-insensitive) within the import batch
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
.backdrop {
  position: fixed;
  inset: 0;
  background: rgba(2, 6, 23, 0.55);
  display: grid;
  place-items: center;
  z-index: 1000;
}
.modal {
  width: min(900px, 94vw);
  background: #fff;
  border-radius: 12px;
  box-shadow: 0 25px 60px rgba(2, 6, 23, 0.25);
  overflow: hidden;
  font-family: 'Inter', 'Segoe UI', sans-serif;
}
.modal__header,
.modal__footer {
  padding: 1rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  border-bottom: 1px solid #e2e8f0;
}
.modal__footer {
  border-top: 1px solid #e2e8f0;
  border-bottom: 0;
  justify-content: flex-end;
}
.modal__body {
  padding: 1rem;
  display: grid;
  gap: 0.75rem;
}
.icon-btn {
  background: transparent;
  border: 0;
  cursor: pointer;
  font-size: 1rem;
}
.hint {
  color: #64748b;
  margin: 0;
}
.textarea {
  width: 100%;
  min-height: 260px;
  resize: vertical;
  padding: 0.85rem;
  border: 2px solid #e2e8f0;
  border-radius: 10px;
  font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
}
.textarea:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}
.error {
  color: #ef4444;
  font-weight: 600;
}
.example {
  background: #f8fafc;
  padding: 0.6rem 0.8rem;
  border-radius: 8px;
}
pre {
  margin: 0.5rem 0 0;
  font-size: 0.85rem;
  white-space: pre-wrap;
}
.options {
  display: grid;
  gap: 0.35rem;
}
.opt {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  color: #334155;
}
.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.7rem 1.2rem;
  border-radius: 10px;
  font-weight: 700;
  cursor: pointer;
  border: 1px solid transparent;
}
.btn-outline {
  background: transparent;
  color: #334155;
  border-color: #cbd5e1;
}
.btn-outline:hover {
  background: #f8fafc;
}
.btn-primary {
  background: #3b82f6;
  color: #fff;
}
.btn-primary:hover {
  background: #2563eb;
}
</style>
