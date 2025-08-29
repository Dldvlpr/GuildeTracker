<template>
  <div class="character-form" role="form" aria-labelledby="character-form-title">
    <form @submit.prevent="handleSubmit" class="form" novalidate>
      <div class="form-header">
        <h2 id="character-form-title">Create a Character</h2>
        <p class="form-description" id="character-form-desc">
          Fill in the fields below and select a class and specialization.
        </p>
        <p>OR</p>
        <p>Import Json data from Raid-Helper</p>
      </div>

      <div class="form-group">
        <label for="character-name" class="form-label required">Player name</label>
        <input
          id="character-name"
          v-model="characterName"
          type="text"
          class="form-input"
          :class="{ 'is-invalid': fieldErrors.name }"
          placeholder="Enter your character's name"
          required
          maxlength="50"
          inputmode="text"
          autocomplete="off"
          autocapitalize="none"
          spellcheck="false"
          :aria-invalid="!!fieldErrors.name"
          :aria-describedby="fieldErrors.name ? 'character-name-error' : 'character-name-hint'"
          @blur="validateCharacterName"
          @input="clearFieldError('name')"
        />
        <div v-if="fieldErrors.name" class="field-error" id="character-name-error" role="alert">
          {{ fieldErrors.name }}
        </div>
        <div v-else class="field-hint" id="character-name-hint">50 characters maximum</div>
      </div>

      <div class="form-group">
        <label for="class-select" class="form-label required">Class</label>
        <select
          id="class-select"
          v-model="selectedClass"
          class="form-select"
          :class="{ 'is-invalid': fieldErrors.class }"
          required
          :aria-invalid="!!fieldErrors.class"
          :aria-describedby="fieldErrors.class ? 'class-error' : undefined"
          @change="handleClassChange"
        >
          <option value="">— Select a class —</option>
          <option
            v-for="option in classOptions"
            :key="option.value"
            :value="option.value"
            :title="getClassDescription(option.value)"
          >
            {{ option.label }} ({{ option.specCount }} specializations)
          </option>
        </select>
        <div v-if="fieldErrors.class" class="field-error" id="class-error" role="alert">
          {{ fieldErrors.class }}
        </div>
      </div>

      <div
        v-if="canSelectSpec"
        class="form-group"
        :class="{ 'form-group--disabled': !hasSelectedClass }"
      >
        <label for="spec-select" class="form-label required">Class spec</label>
        <select
          id="spec-select"
          v-model="selectedSpec"
          class="form-select"
          :class="{ 'is-invalid': fieldErrors.spec }"
          :disabled="!hasSelectedClass"
          required
          :aria-invalid="!!fieldErrors.spec"
          :aria-describedby="fieldErrors.spec ? 'spec-error' : undefined"
          @change="handleSpecChange"
        >
          <option value="">— Select a specialization —</option>
          <option
            v-for="spec in specOptions"
            :key="spec.value"
            :value="spec.value"
            :title="`Role: ${spec.role}`"
          >
            {{ spec.label }} ({{ spec.role }})
          </option>
        </select>
        <div v-if="fieldErrors.spec" class="field-error" id="spec-error" role="alert">
          {{ fieldErrors.spec }}
        </div>
      </div>

      <div v-if="hasGeneralErrors" class="general-errors" role="alert" aria-live="assertive">
        <div class="error-list">
          <div v-for="error in generalErrors" :key="error" class="error-item">{{ error }}</div>
        </div>
      </div>

      <div class="form-actions form-actions--center">
        <button type="button" @click="handleReset" class="btn btn-secondary">Reset</button>

        <button type="submit" class="btn btn-primary" :disabled="!isValidForm">
          Create the character
        </button>

        <button type="button" class="btn btn-outline" @click="showImport = true">
          Import JSON
        </button>
      </div>
    </form>

    <div
      v-if="showImport"
      class="backdrop"
      @click.self="closeImport"
      role="dialog"
      aria-modal="true"
      aria-labelledby="import-title"
    >
      <div class="modal">
        <header class="modal__header">
          <h3 id="import-title">Import from Raid-Helper JSON</h3>
          <button class="icon-btn" @click="closeImport" aria-label="Close">✖</button>
        </header>

        <section class="modal__body">
          <p class="hint">
            Paste the event JSON (must contain a <code>signUps</code> array). Select which players
            to import, or prefill the form from one.
          </p>

          <textarea
            v-model="importText"
            class="textarea"
            rows="12"
            placeholder='{"date":"1-9-2025","signUps":[{"name":"shaqx","className":"Druid","specName":"Feral","roleName":"Melee"}]}'
            spellcheck="false"
          ></textarea>

          <div class="options">
            <label class="opt">
              <input type="checkbox" v-model="includeNonPlayableClasses" />
              Include non-playable classes (Bench, Absence, Tentative, Late)
            </label>
          </div>

          <div v-if="importError" class="error" role="alert">{{ importError }}</div>

          <div v-if="selectablePlayers.length" class="select-list">
            <div class="select-list__toolbar">
              <label class="opt">
                <input type="checkbox" :checked="allSelected" @change="toggleAll($event)" />
                Select all ({{ selectablePlayers.length }})
              </label>
              <span class="muted">Already existing names are disabled.</span>
            </div>

            <ul class="list">
              <li v-for="(row, idx) in selectablePlayers" :key="idx" class="row">
                <label class="row__label">
                  <input type="checkbox" v-model="row.selected" :disabled="row.duplicate" />
                  <span>
                    <strong>{{ row.name }}</strong>
                    — {{ row.class }}<span v-if="row.spec"> ({{ row.spec }})</span>
                    <span v-if="row.duplicate" class="badge-dup">duplicate</span>
                  </span>
                </label>
              </li>
            </ul>

            <div class="inline-actions">
              <button
                class="btn btn-outline"
                @click="prefillFirstSelected"
                :disabled="!anySelected"
              >
                Prefill
              </button>
              <button class="btn btn-primary" @click="importSelected" :disabled="!anySelected">
                Import selected
              </button>
            </div>
          </div>
        </section>

        <footer class="modal__footer">
          <button class="btn btn-outline" @click="closeImport">Cancel</button>
        </footer>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useGameData } from '../composables/useGameData.ts'
import type {
  Character,
  FormSubmitEvent,
  ClassChangeEvent,
  SpecChangeEvent,
  FormErrors,
  CharacterStatus,
} from '../interfaces/game.interface'

interface Props {
  readonly formTitle?: string
  readonly formDescription?: string
  readonly enableAutoValidation?: boolean
  readonly existingNames?: string[]
}
interface Emits {
  (event: 'submit', data: FormSubmitEvent): void
  (event: 'bulkImport', data: Omit<Character, 'id' | 'createdAt'>[]): void
  (event: 'classChange', data: ClassChangeEvent): void
  (event: 'specChange', data: SpecChangeEvent): void
  (event: 'error', errors: FormErrors): void
  (
    event: 'notify',
    payload: { type: 'success' | 'error' | 'warning' | 'info'; message: string },
  ): void
}
const props = withDefaults(defineProps<Props>(), {
  enableAutoValidation: true,
  existingNames: () => [],
})
const emit = defineEmits<Emits>()

const {
  selectedClass,
  selectedSpec,
  availableSpecs,
  currentSpecRole,
  classOptions,
  specOptions,
  hasSelectedClass,
  hasSelectedSpec,
  canSelectSpec,
  isValidSelection,
  validationErrors,
  resetAll,
} = useGameData()

const characterName = ref<string>('')
const characterLevel = ref<number>()
const fieldErrors = ref<FormErrors>({})
const generalErrors = ref<string[]>([])
const hasTriedSubmit = ref<boolean>(false)

const isValidForm = computed(
  (): boolean =>
    isValidSelection.value && characterName.value.trim() !== '' && !hasValidationErrors.value,
)
const hasValidationErrors = computed(
  (): boolean =>
    Object.keys(fieldErrors.value).length > 0 || Object.keys(validationErrors.value).length > 0,
)
const hasGeneralErrors = computed((): boolean => generalErrors.value.length > 0)

const getClassDescription = (className: string): string => {
  const gameClass = classOptions.value.find((opt) => opt.value === className)
  return gameClass ? `${gameClass.specCount} available specializations` : ''
}
const clearFieldError = (field: keyof FormErrors): void => {
  delete fieldErrors.value[field]
}
const clearAllErrors = (): void => {
  fieldErrors.value = {}
  generalErrors.value = []
}

const validateCharacterName = (): boolean => {
  const name = characterName.value.trim()
  if (!name) {
    fieldErrors.value.name = 'Name is required.'
    return false
  }
  if (name.length < 2) {
    fieldErrors.value.name = 'The name must contain at least 2 characters.'
    return false
  }
  if (name.length > 50) {
    fieldErrors.value.name = 'The name cannot exceed 50 characters.'
    return false
  }
  if (!/^[a-zA-Z0-9\s\-_]+$/u.test(name)) {
    fieldErrors.value.name = 'The name contains invalid characters.'
    return false
  }
  clearFieldError('name')
  return true
}
const validateForm = (): boolean => {
  const nameValid = validateCharacterName()
  const selectionValid = isValidSelection.value
  if (!selectionValid && validationErrors.value.class)
    fieldErrors.value.class = validationErrors.value.class
  if (!selectionValid && validationErrors.value.spec)
    fieldErrors.value.spec = validationErrors.value.spec
  return nameValid && selectionValid
}

const handleClassChange = (): void => {
  clearFieldError('class')
  clearFieldError('spec')
  emit('classChange', { className: selectedClass.value, availableSpecs: availableSpecs.value })
}
const handleSpecChange = (): void => {
  clearFieldError('spec')
  if (hasSelectedSpec.value && currentSpecRole.value) {
    emit('specChange', {
      className: selectedClass.value,
      specName: selectedSpec.value,
      role: currentSpecRole.value,
    })
  }
}
const handleReset = (): void => {
  characterName.value = ''
  characterLevel.value = undefined
  resetAll()
  clearAllErrors()
  hasTriedSubmit.value = false
}
const handleSubmit = (): void => {
  hasTriedSubmit.value = true
  if (!validateForm()) {
    emit('error', { ...fieldErrors.value, ...validationErrors.value })
    return
  }
  const characterData: Omit<Character, 'id' | 'createdAt'> = {
    name: characterName.value.trim(),
    class: selectedClass.value,
    spec: selectedSpec.value || undefined,
    role: currentSpecRole.value || undefined,
    status: 'active' as CharacterStatus,
    level: characterLevel.value,
  }
  emit('submit', { character: characterData, isValid: true })
}

if (props.enableAutoValidation) {
  watch([characterName, characterLevel, selectedClass, selectedSpec], () => {
    if (hasTriedSubmit.value) validateForm()
  })
}

type RoleName = 'Tanks' | 'Healers' | 'Melee' | 'Ranged'
type Signup = {
  name: string
  className?: string
  specName?: string
  roleName?: RoleName
  level?: number
}
type EventJson = { date?: string; signUps: Signup[] }
type ParsedPlayer = {
  name: string
  class: string
  spec?: string
  role?: RoleName
  level?: number
  duplicate: boolean
  selected: boolean
}

const NON_PLAYABLE = new Set(['Bench', 'Absence', 'Tentative', 'Late'])
const existingSet = computed(() => new Set(props.existingNames.map((n) => n.toLowerCase())))

const showImport = ref(false)
const importText = ref('')
const importError = ref('')

const includeNonPlayableClasses = ref(false)
const selectablePlayers = ref<ParsedPlayer[]>([])

const closeImport = () => {
  showImport.value = false
  importText.value = ''
  importError.value = ''
  selectablePlayers.value = []
}

watch([importText, includeNonPlayableClasses], () => parseJson())

function parseJson() {
  importError.value = ''
  selectablePlayers.value = []
  const txt = importText.value.trim()
  if (!txt) return
  try {
    const parsed = JSON.parse(txt) as EventJson | { signUps?: Signup[] }
    const signUps = Array.isArray((parsed as any).signUps)
      ? ((parsed as any).signUps as Signup[])
      : []
    if (!signUps.length) {
      importError.value = 'Invalid JSON: missing or empty "signUps" array.'
      return
    }

    const rows = signUps
      .map((s) => {
        const name = (s.name ?? '').trim()
        const cls = (s.className ?? '').trim()
        if (!name || !cls) return null
        if (!includeNonPlayableClasses.value && NON_PLAYABLE.has(cls)) return null
        const key = name.toLowerCase()
        const dup = existingSet.value.has(key)
        return {
          name,
          class: cls,
          spec: s.specName?.trim() || undefined,
          role: s.roleName as RoleName | undefined,
          level: typeof s.level === 'number' ? s.level : undefined,
          duplicate: dup,
          selected: !dup,
        } as ParsedPlayer
      })
      .filter((x): x is ParsedPlayer => !!x)

    const seen = new Set<string>()
    const dedup = rows.filter((r) => {
      const k = r.name.toLowerCase()
      if (seen.has(k)) return false
      seen.add(k)
      return true
    })

    if (!dedup.length) {
      importError.value = 'No valid players after filtering.'
      return
    }
    selectablePlayers.value = dedup
  } catch (e) {
    importError.value = (e as Error).message || 'Invalid JSON.'
  }
}

const anySelected = computed(() => selectablePlayers.value.some((r) => r.selected && !r.duplicate))
const allSelected = computed(() =>
  selectablePlayers.value.every((r) => (r.duplicate ? true : r.selected)),
)

function toggleAll(ev: Event) {
  const checked = (ev.target as HTMLInputElement).checked
  selectablePlayers.value.forEach((r) => {
    if (!r.duplicate) r.selected = checked
  })
}

function prefillFirstSelected() {
  const candidate = selectablePlayers.value.find((r) => r.selected && !r.duplicate)
  if (!candidate) return
  characterName.value = candidate.name
  selectedClass.value = candidate.class
  const specInOptions = specOptions.value.some((s) => s.value === candidate.spec)
  selectedSpec.value = specInOptions ? (candidate.spec as string) : ''
  clearAllErrors()
  showImport.value = false
  emit('notify', { type: 'info', message: `Prefilled with ${candidate.name}.` })
}

function importSelected() {
  const selected = selectablePlayers.value.filter((r) => r.selected && !r.duplicate)
  if (!selected.length) {
    emit('notify', { type: 'warning', message: 'Nothing to import.' })
    return
  }

  const payload: Omit<Character, 'id' | 'createdAt'>[] = selected.map((r) => ({
    name: r.name,
    class: r.class,
    spec: r.spec,
    role: r.role,
    status: 'active' as CharacterStatus,
    level: r.level,
  }))

  emit('bulkImport', payload)

  showImport.value = false
  emit('notify', { type: 'success', message: `Imported ${payload.length} character(s).` })
}
</script>

<style scoped>
.character-form {
  --bg: #ffffff;
  --fg: #0f172a;
  --muted: #64748b;
  --border: #e2e8f0;
  --ring: #3b82f6;
  --error: #ef4444;
  --success: #16a34a;
  --warning: #f59e0b;

  --radius: 12px;
  --radius-sm: 10px;
  --shadow-1: 0 1px 3px rgba(2,6,23,.06), 0 1px 2px rgba(2,6,23,.08);
  --shadow-2: 0 10px 30px rgba(2,6,23,.12);
  --gap: 1.1rem;
  --gap-lg: 1.5rem;

  color: var(--fg);
  background: var(--bg);
  border-radius: var(--radius);
  box-shadow: var(--shadow-1);
}

@media (prefers-color-scheme: dark) {
  .character-form {
    --bg: #0b1020;
    --fg: #e5e7eb;
    --muted: #a3a9b6;
    --border: #253046;
    --ring: #60a5fa;
    --error: #f87171;
    --success: #34d399;
    --warning: #fbbf24;
    box-shadow: none;
  }
}

.character-form {
  max-width: 720px;
  margin: clamp(12px, 2.2vw, 24px) auto;
  padding: clamp(16px, 2.5vw, 28px);
}

.form-header {
  text-align: center;
  margin-bottom: var(--gap-lg);
}
.form-header h2 {
  margin: 0 0 .35rem 0;
  font-weight: 800;
  letter-spacing: .2px;
  font-size: clamp(1.25rem, 2.2vw, 1.7rem);
}
.form-description,
.hint,
.muted {
  color: var(--muted);
}

.form {
  display: grid;
  gap: var(--gap-lg);
}

.form-group {
  display: grid;
  gap: .55rem;
}

.form-group--disabled {
  opacity: .7;
  pointer-events: none;
  filter: grayscale(.1);
}
.form-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--fg);
}
.form-label.required::after {
  content: ' *';
  color: var(--error);
  font-weight: 800;
}

.form-input,
.form-select,
.textarea,
select,
input[type="text"] {
  width: 100%;
  padding: .8rem .9rem;
  border: 2px solid var(--border);
  border-radius: var(--radius-sm);
  background: var(--bg);
  color: var(--fg);
  font-size: 1rem;
  line-height: 1.15;
  transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
  outline: none;
}

.form-input:hover,
.form-select:hover,
.textarea:hover {
  border-color: color-mix(in srgb, var(--ring) 35%, var(--border));
}
.form-input:focus,
.form-select:focus,
.textarea:focus {
  border-color: var(--ring);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--ring) 25%, transparent);
}

.form-input.is-invalid,
.form-select.is-invalid {
  border-color: var(--error);
  box-shadow: 0 0 0 3px color-mix(in srgb, var(--error) 20%, transparent);
}

.field-hint {
  color: var(--muted);
  font-size: .9rem;
}
.field-error,
.error {
  color: var(--error);
  font-size: .92rem;
  font-weight: 650;
}

.general-errors {
  border: 1px solid color-mix(in srgb, var(--error) 50%, var(--border));
  background: color-mix(in srgb, var(--error) 7%, var(--bg));
  border-radius: var(--radius-sm);
  padding: .85rem 1rem;
  box-shadow: var(--shadow-1);
}
.error-list { display: grid; gap: .4rem; }
.error-item::before {
  content: '⛔ ';
  margin-right: .2rem;
}

.form-actions {
  display: flex;
  flex-wrap: wrap;
  gap: .75rem;
  justify-content: center;
  align-items: center;
  padding-top: 1rem;
  border-top: 1px solid var(--border);
}

.btn {
  --btn-bg: #111827;
  --btn-fg: #ffffff;
  --btn-border: transparent;

  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: .5rem;
  padding: .8rem 1.2rem;
  min-height: 44px;
  border-radius: 10px;
  border: 1px solid var(--btn-border);
  background: var(--btn-bg);
  color: var(--btn-fg);
  font-weight: 800;
  letter-spacing: .25px;
  cursor: pointer;
  transition: transform .12s ease, box-shadow .12s ease, background .12s ease, border-color .12s ease;
  box-shadow: 0 6px 14px rgba(2,6,23,.08);
}
.btn:active { transform: translateY(0); }

.btn-primary {
  --btn-bg: var(--ring);
  --btn-fg: #fff;
}
.btn-primary:hover { filter: brightness(1.05); transform: translateY(-1px); }
.btn-primary:focus-visible { box-shadow: 0 0 0 3px color-mix(in srgb, var(--ring) 30%, transparent); }

.btn-secondary {
  --btn-bg: #64748b;
  --btn-fg: #fff;
}
.btn-secondary:hover { filter: brightness(1.05); transform: translateY(-1px); }

.btn-outline {
  --btn-bg: transparent;
  --btn-fg: var(--fg);
  --btn-border: var(--border);
  box-shadow: none;
}
.btn-outline:hover {
  background: color-mix(in srgb, var(--ring) 8%, transparent);
  border-color: color-mix(in srgb, var(--ring) 40%, var(--border));
  transform: translateY(-1px);
}

.btn:disabled {
  opacity: .55;
  cursor: not-allowed;
  transform: none !important;
  box-shadow: none;
}

.backdrop {
  position: fixed; inset: 0;
  background: rgba(2, 6, 23, .55);
  display: grid; place-items: center;
  z-index: 1000;
  padding: 1rem;
}
.modal {
  width: min(880px, 96vw);
  max-height: 92vh;
  background: var(--bg);
  color: var(--fg);
  border-radius: var(--radius);
  box-shadow: var(--shadow-2);
  display: grid;
  grid-template-rows: auto 1fr auto;
  overflow: hidden;
}
.modal__header,
.modal__footer {
  padding: .9rem 1rem;
  display: flex; align-items: center; justify-content: space-between;
  gap: .75rem;
  border-bottom: 1px solid var(--border);
}
.modal__footer {
  border-top: 1px solid var(--border);
  border-bottom: 0;
  justify-content: flex-end;
}
.modal__body {
  padding: 1rem;
  overflow: auto;
  display: grid;
  gap: .9rem;
}

.icon-btn {
  background: transparent;
  border: none;
  color: var(--muted);
  font-size: 1.05rem;
  cursor: pointer;
  padding: .35rem .45rem;
  border-radius: 8px;
}
.icon-btn:hover { background: color-mix(in srgb, var(--ring) 10%, transparent); color: var(--fg); }

.textarea { min-height: 220px; font-family: ui-monospace, SFMono-Regular, Menlo, monospace; }

.options { display: grid; gap: .35rem; }
.opt { display: inline-flex; align-items: center; gap: .5rem; color: var(--fg); }
.opt input { width: 18px; height: 18px; }

.select-list__toolbar {
  display: flex; align-items: center; justify-content: space-between;
  gap: .75rem; padding: .25rem 0;
}
.muted { color: var(--muted); font-size: .92rem; }

.list {
  list-style: none; margin: 0; padding: 0;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  max-height: 280px; overflow: auto;
}
.row { padding: .65rem .85rem; border-bottom: 1px solid var(--border); }
.row:last-child { border-bottom: 0; }
.row__label { display: flex; align-items: center; gap: .6rem; }
.row input[type="checkbox"] { width: 18px; height: 18px; }
.badge-dup {
  margin-left: .5rem;
  padding: .1rem .45rem;
  border-radius: 9999px;
  font-size: .78rem;
  background: #fee2e2;
  color: #991b1b;
  border: 1px solid #fecaca;
}

.inline-actions { display: flex; gap: .5rem; justify-content: flex-end; }

:focus-visible {
  outline: 3px solid color-mix(in srgb, var(--ring) 40%, transparent);
  outline-offset: 2px;
  border-radius: 8px;
}

@media (prefers-reduced-motion: reduce) {
  .btn, .form-input, .form-select, .textarea, .modal, .row {
    transition: none !important;
  }
}

@media (max-width: 768px) {
  .character-form { padding: clamp(14px, 3.4vw, 20px); }
  .form-actions { flex-direction: column; align-items: stretch; }
  .btn { width: 100%; }
  .modal { width: 96vw; max-height: 92vh; }
}

@media (max-width: 380px) {
  .form-header h2 { font-size: 1.2rem; }
  .form-input, .form-select { font-size: .98rem; }
}

:host(.hc) .btn-primary,
.character-form.hc .btn-primary {
  outline: 2px solid #000;
}
</style>
