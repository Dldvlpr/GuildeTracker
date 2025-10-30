<template>
  <div role="form" aria-labelledby="character-form-title">
    <form @submit.prevent="handleSubmit" class="space-y-6" novalidate>
        <div class="text-center border-b border-white/10 pb-6">
          <h2 id="character-form-title" class="text-2xl font-bold text-white mb-2">Create a Character</h2>
          <p class="text-slate-400 text-sm mb-3" id="character-form-desc">
            Fill in the fields below and select a class and specialization.
          </p>
          <p class="text-slate-500 text-xs">OR</p>
          <button
            type="button"
            @click="showImport = true"
            class="mt-3 inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium ring-1 ring-inset ring-white/10 hover:bg-white/5 text-slate-300 hover:text-white transition"
          >
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
            </svg>
            Import JSON from Raid-Helper
          </button>
        </div>

        <!-- Character Name -->
        <div class="space-y-2">
          <label for="character-name" class="block text-sm font-semibold text-slate-200">
            Player name <span class="text-red-400">*</span>
          </label>
          <input
            id="character-name"
            v-model="characterName"
            type="text"
            class="w-full px-4 py-3 rounded-xl border bg-slate-950/50 text-slate-100 placeholder-slate-500 transition focus:outline-none focus:ring-2 focus:ring-indigo-500"
            :class="fieldErrors.name ? 'border-red-500/50 ring-2 ring-red-500/20' : 'border-white/10 hover:border-white/20'"
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
          <p v-if="fieldErrors.name" class="text-sm text-red-400" id="character-name-error" role="alert">
            {{ fieldErrors.name }}
          </p>
          <p v-else class="text-xs text-slate-500" id="character-name-hint">50 characters maximum</p>
        </div>

        <!-- Class Select -->
        <div class="space-y-2">
          <label for="class-select" class="block text-sm font-semibold text-slate-200">
            Class <span class="text-red-400">*</span>
          </label>
          <select
            id="class-select"
            v-model="selectedClass"
            class="w-full px-4 py-3 rounded-xl border bg-slate-950/50 text-slate-100 transition focus:outline-none focus:ring-2 focus:ring-indigo-500"
            :class="fieldErrors.class ? 'border-red-500/50 ring-2 ring-red-500/20' : 'border-white/10 hover:border-white/20'"
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
          <p v-if="fieldErrors.class" class="text-sm text-red-400" id="class-error" role="alert">
            {{ fieldErrors.class }}
          </p>
        </div>

        <!-- Spec Select -->
        <div
          v-if="canSelectSpec"
          class="space-y-2"
          :class="{ 'opacity-50 pointer-events-none': !hasSelectedClass }"
        >
          <label for="spec-select" class="block text-sm font-semibold text-slate-200">
            Class spec <span class="text-red-400">*</span>
          </label>
          <select
            id="spec-select"
            v-model="selectedSpec"
            class="w-full px-4 py-3 rounded-xl border bg-slate-950/50 text-slate-100 transition focus:outline-none focus:ring-2 focus:ring-indigo-500"
            :class="fieldErrors.spec ? 'border-red-500/50 ring-2 ring-red-500/20' : 'border-white/10 hover:border-white/20'"
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
          <p v-if="fieldErrors.spec" class="text-sm text-red-400" id="spec-error" role="alert">
            {{ fieldErrors.spec }}
          </p>
        </div>

        <!-- General Errors -->
        <div v-if="hasGeneralErrors" class="rounded-xl bg-red-500/10 border border-red-500/20 p-4" role="alert" aria-live="assertive">
          <div class="space-y-1">
            <p v-for="error in generalErrors" :key="error" class="text-sm text-red-300">
              {{ error }}
            </p>
          </div>
        </div>

        <!-- Form Actions -->
        <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-white/10">
          <button
            type="button"
            @click="handleReset"
            class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-semibold text-slate-300 ring-1 ring-inset ring-white/10 hover:bg-white/5 hover:text-white transition"
          >
            Reset
          </button>

          <button
            type="submit"
            :disabled="!isValidForm"
            class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-semibold bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-600 text-white shadow-lg shadow-indigo-600/50 transition disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none"
          >
            Create the character
          </button>
        </div>
      </form>

    <!-- Import Modal -->
    <BaseModal
      v-model="showImport"
      title="Import from Raid-Helper JSON"
      size="lg"
    >
      <div class="space-y-4">
        <p class="text-sm text-slate-400">
          Paste the event JSON (must contain a <code class="px-1.5 py-0.5 rounded bg-slate-800 text-indigo-300 text-xs">signUps</code> array). Then select a player to prefill this form.
        </p>

        <textarea
          v-model="importText"
          class="w-full min-h-[280px] px-4 py-3 rounded-xl border border-white/10 bg-slate-950/50 text-slate-100 font-mono text-sm placeholder-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-y"
          placeholder='{"date":"1-9-2025","signUps":[{"name":"shaqx","className":"Druid","specName":"Feral","roleName":"Melee"}]}'
          spellcheck="false"
        ></textarea>

        <label class="flex items-center gap-2 text-sm text-slate-300 cursor-pointer">
          <input
            type="checkbox"
            v-model="includeNonPlayableClasses"
            class="w-4 h-4 rounded border-white/10 bg-slate-950/50 text-indigo-600 focus:ring-2 focus:ring-indigo-500"
          />
          Include non-playable classes (Bench, Absence, Tentative, Late)
        </label>

        <div v-if="importError" class="rounded-xl bg-red-500/10 border border-red-500/20 p-4" role="alert">
          <p class="text-sm text-red-300">{{ importError }}</p>
        </div>

        <div v-if="parsedPlayers.length" class="space-y-2">
          <label for="player-select" class="block text-sm font-semibold text-slate-200">
            Detected players ({{ parsedPlayers.length }})
          </label>
          <select
            id="player-select"
            v-model="selectedPlayerIndex"
            class="w-full px-4 py-3 rounded-xl border border-white/10 bg-slate-950/50 text-slate-100 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          >
            <option v-for="(p, idx) in parsedPlayers" :key="idx" :value="idx">
              {{ p.name }} — {{ p.class }}{{ p.spec ? ` (${p.spec})` : '' }}
            </option>
          </select>
        </div>
      </div>

      <template #footer>
        <div class="flex flex-col sm:flex-row gap-3 w-full">
          <button
            @click="closeImport"
            class="inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-semibold text-slate-300 ring-1 ring-inset ring-white/10 hover:bg-white/5 hover:text-white transition"
          >
            Cancel
          </button>
          <button
            :disabled="!parsedPlayers.length"
            @click="applySelectedPlayer"
            class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-semibold bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-600 text-white shadow-lg shadow-indigo-600/50 transition disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Use selection
          </button>
          <button
            :disabled="!parsedPlayers.length"
            @click="importAllPlayers"
            class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3 rounded-xl font-semibold bg-gradient-to-r from-cyan-600 to-cyan-500 hover:from-cyan-500 hover:to-cyan-600 text-white shadow-lg shadow-cyan-600/50 transition disabled:opacity-50 disabled:cursor-not-allowed"
          >
            Import all
          </button>
        </div>
      </template>
    </BaseModal>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import BaseModal from './ui/BaseModal.vue'
import { useGameData } from '../composables/useGameData.ts'
import type {
  Character,
  FormSubmitEvent,
  ClassChangeEvent,
  SpecChangeEvent,
  FormErrors,
  CharacterStatus,
  Role,
} from '../interfaces/game.interface'

interface Props {
  readonly formTitle?: string
  readonly formDescription?: string
  readonly enableAutoValidation?: boolean
}

interface Emits {
  (event: 'submit', data: FormSubmitEvent): void
  (event: 'classChange', data: ClassChangeEvent): void
  (event: 'specChange', data: SpecChangeEvent): void
  (event: 'error', errors: FormErrors): void
  (event: 'bulkImport', data: Omit<Character, 'id' | 'createdAt'>[]): void
}

const props = withDefaults(defineProps<Props>(), {
  enableAutoValidation: true,
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

const isValidForm = computed((): boolean => {
  return isValidSelection.value && characterName.value.trim() !== '' && !hasValidationErrors.value
})

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
    fieldErrors.value = { ...fieldErrors.value, name: 'Name is required.' }
    return false
  }
  if (name.length < 2) {
    fieldErrors.value = { ...fieldErrors.value, name: 'The name must contain at least 2 characters.' }
    return false
  }
  if (name.length > 50) {
    fieldErrors.value = { ...fieldErrors.value, name: 'The name cannot exceed 50 characters.' }
    return false
  }
  if (!/^[a-zA-Z0-9\s\-_]+$/u.test(name)) {
    fieldErrors.value = { ...fieldErrors.value, name: 'The name contains invalid characters.' }
    return false
  }

  clearFieldError('name')
  return true
}

const validateForm = (): boolean => {
  const nameValid = validateCharacterName()
  const selectionValid = isValidSelection.value

  if (!selectionValid && validationErrors.value.class) {
    fieldErrors.value.class = validationErrors.value.class
  }
  if (!selectionValid && validationErrors.value.spec) {
    fieldErrors.value.spec = validationErrors.value.spec
  }

  return nameValid && selectionValid
}

const handleClassChange = (): void => {
  clearFieldError('class')
  clearFieldError('spec')

  emit('classChange', {
    className: selectedClass.value,
    availableSpecs: availableSpecs.value,
  })
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

  const formData: FormSubmitEvent = {
    character: characterData,
    isValid: true,
  }

  emit('submit', formData)
}

if (props.enableAutoValidation) {
  watch([characterName, characterLevel, selectedClass, selectedSpec], () => {
    if (hasTriedSubmit.value) {
      validateForm()
    }
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

const NON_PLAYABLE = new Set(['Bench', 'Absence', 'Tentative', 'Late'])

const showImport = ref(false)
const importText = ref('')
const importError = ref('')
const includeNonPlayableClasses = ref(false)

type ParsedPlayer = { name: string; class: string; spec?: string; role?: RoleName; level?: number }
const parsedPlayers = ref<ParsedPlayer[]>([])
const selectedPlayerIndex = ref<number>(0)

const closeImport = () => {
  showImport.value = false
  importText.value = ''
  importError.value = ''
  parsedPlayers.value = []
  selectedPlayerIndex.value = 0
}

watch([importText, includeNonPlayableClasses], () => {
  parseJson()
})

function parseJson() {
  importError.value = ''
  parsedPlayers.value = []
  selectedPlayerIndex.value = 0

  const txt = importText.value.trim()
  if (!txt) return

  try {
    const parsed = JSON.parse(txt) as { signUps?: unknown }
    const signUps = Array.isArray(parsed.signUps)
      ? (parsed.signUps as Signup[])
      : []

    if (!signUps.length) {
      importError.value = 'Invalid JSON: missing or empty "signUps" array.'
      return
    }

    const players = signUps
      .map((s) => {
        const cls = (s.className ?? '').trim()
        const name = (s.name ?? '').trim()
        if (!name || !cls) return null
        if (!includeNonPlayableClasses.value && NON_PLAYABLE.has(cls)) return null
        return {
          name,
          class: cls,
          spec: s.specName?.trim() || undefined,
          role: s.roleName as RoleName | undefined,
          level: typeof s.level === 'number' ? s.level : undefined,
        } as ParsedPlayer
      })
      .filter((x): x is ParsedPlayer => x !== null)

    const seen = new Set<string>()
    const dedup = players.filter((p) => {
      const key = p.name.toLowerCase()
      if (seen.has(key)) return false
      seen.add(key)
      return true
    })

    if (!dedup.length) {
      importError.value = 'No valid players after filtering.'
      return
    }

    parsedPlayers.value = dedup
  } catch (e) {
    importError.value = (e as Error).message || 'Invalid JSON.'
  }
}

function applySelectedPlayer() {
  const p = parsedPlayers.value[selectedPlayerIndex.value]
  if (!p) return

  characterName.value = p.name
  selectedClass.value = p.class
  const hasSpec = specOptions.value.some((s) => s.value === p.spec)
  selectedSpec.value = hasSpec ? (p.spec as string) : ''

  clearAllErrors()
  showImport.value = false
}

function importAllPlayers() {
  if (!parsedPlayers.value.length) return

  const items: Omit<Character, 'id' | 'createdAt'>[] = parsedPlayers.value.map((p) => ({
    name: p.name,
    class: p.class,
    spec: p.spec,
    role: p.role as Role | undefined,
    level: p.level,
    status: 'active' as CharacterStatus,
  }))

  emit('bulkImport', items)
  closeImport()
}
</script>
