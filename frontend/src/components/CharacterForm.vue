<template>
  <div class="character-form" role="form" aria-labelledby="character-form-title">
    <form @submit.prevent="handleSubmit" class="form" novalidate>
      <div class="form-header">
        <h2 id="character-form-title">Create a Character</h2>
        <p class="form-description" id="character-form-desc">
          Fill in the fields below and select a class and specialization.
        </p>
        <p>OR</p>
        <p>Import Json data from raid helper</p>
      </div>

      <div class="form-group">
        <label for="character-name" class="form-label required"> Player name </label>
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
        <label for="class-select" class="form-label required"> Class </label>
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
        <label for="spec-select" class="form-label required"> Class spec </label>
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
          <div v-for="error in generalErrors" :key="error" class="error-item">
            {{ error }}
          </div>
        </div>
      </div>

      <div class="form-actions form-actions--center">
        <button type="button" @click="handleReset" class="btn btn-secondary">Reset</button>

        <button type="submit" class="btn btn-primary" :disabled="!isValidForm">
          Create the character
        </button>
      </div>
    </form>
    <button class="btn btn-primary" >Json</button>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { useGameData } from '../composables/useGameData.ts'
import type {
  Role,
  Character,
  FormSubmitEvent,
  ClassChangeEvent,
  SpecChangeEvent,
  FormErrors,
  CharacterStatus,
} from '../interfaces/game.interface'
import { ROLE_ICONS } from '../interfaces/game.interface'

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
  setClass,
  setSpec,
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

const getRoleDisplay = (role: Role): string => `${ROLE_ICONS[role]} ${role}`

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
</script>

<style scoped>
.character-form {
  max-width: 700px;
  margin: 0 auto;
  padding: 2rem;
  background: #ffffff;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.08);
  font-family:
    'Inter',
    'Segoe UI',
    -apple-system,
    system-ui,
    sans-serif;
}

.form-header {
  text-align: center;
  margin-bottom: 1.5rem;
}

.form-header h2 {
  font-size: 1.6rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 0.4rem 0;
}

.form-description {
  color: #64748b;
  margin: 0;
}

.form {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-group--disabled {
  opacity: 0.6;
  pointer-events: none;
}

.form-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #334155;
}

.form-label.required::after {
  content: ' *';
  color: #ef4444;
}

.form-input,
.form-select {
  padding: 0.75rem;
  border: 2px solid #e2e8f0;
  border-radius: 8px;
  font-size: 1rem;
  background: #ffffff;
  transition:
    border-color 0.2s ease,
    box-shadow 0.2s ease;
  color: #0f172a;
}

.form-input:focus,
.form-select:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
}

.form-input.is-invalid,
.form-select.is-invalid {
  border-color: #ef4444;
  box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.12);
}

.form-input:disabled,
.form-select:disabled {
  background-color: #f1f5f9;
  cursor: not-allowed;
}

.field-error {
  color: #ef4444;
  font-size: 0.875rem;
  font-weight: 500;
}

.field-hint {
  color: #64748b;
  font-size: 0.875rem;
}

.general-errors {
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 8px;
  padding: 1rem;
}

.error-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.error-item {
  color: #ef4444;
  font-size: 0.9rem;
  font-weight: 600;
}

.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: center;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
}
.form-actions--center {
  justify-content: center;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.8rem 1.4rem;
  border: none;
  border-radius: 10px;
  font-size: 1rem;
  font-weight: 700;
  cursor: pointer;
  transition:
    transform 0.15s ease,
    background 0.15s ease,
    box-shadow 0.15s ease;
  will-change: transform;
}

.btn:disabled {
  opacity: 0.55;
  cursor: not-allowed;
  transform: none !important;
}

.btn-primary {
  background: #3b82f6;
  color: white;
  box-shadow: 0 6px 14px rgba(59, 130, 246, 0.25);
}
.btn-primary:hover:not(:disabled),
.btn-primary:focus-visible:not(:disabled) {
  background: #2563eb;
  transform: translateY(-1px);
}

.btn-secondary {
  background: #64748b;
  color: white;
  box-shadow: 0 6px 14px rgba(100, 116, 139, 0.22);
}
.btn-secondary:hover:not(:disabled),
.btn-secondary:focus-visible:not(:disabled) {
  background: #475569;
  transform: translateY(-1px);
}

@media (max-width: 768px) {
  .character-form {
    padding: 1rem;
    margin: 1rem;
  }

  .form-actions {
    flex-direction: column;
  }

  .btn {
    width: 100%;
    justify-content: center;
  }
}
</style>
