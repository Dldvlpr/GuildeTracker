<template>
  <div class="character-form">
    <div class="form-header">
      <h2>{{ formTitle }}</h2>
      <p v-if="formDescription" class="form-description">
        {{ formDescription }}
      </p>
    </div>

    <form @submit.prevent="handleSubmit" class="form" novalidate>

      <div class="form-group">
        <label for="character-name" class="form-label required">
          Player name
        </label>
        <input
          id="character-name"
          v-model="characterName"
          type="text"
          class="form-input"
          :class="{ 'is-invalid': fieldErrors.name }"
          placeholder="Enter your character's name"
          required
          maxlength="50"
          @blur="validateCharacterName"
          @input="clearFieldError('name')"
        />
        <div v-if="fieldErrors.name" class="field-error">
          {{ fieldErrors.name }}
        </div>
        <div v-else class="field-hint">
          50 chars maximum
        </div>
      </div>

      <div class="form-group">
        <label for="class-select" class="form-label required">
          Class
        </label>
        <select
          id="class-select"
          v-model="selectedClass"
          class="form-select"
          :class="{ 'is-invalid': fieldErrors.class }"
          required
          @change="handleClassChange"
        >
          <option value="">-- Select a class --</option>
          <option
            v-for="option in classOptions"
            :key="option.value"
            :value="option.value"
            :title="getClassDescription(option.value)"
          >
            {{ option.label }} ({{ option.specCount }} spécialisations)
          </option>
        </select>
        <div v-if="fieldErrors.class" class="field-error">
          {{ fieldErrors.class }}
        </div>
      </div>

      <div
        v-if="canSelectSpec"
        class="form-group"
        :class="{ 'form-group--disabled': !hasSelectedClass }"
      >
        <label for="spec-select" class="form-label required">
          Class spec
        </label>
        <select
          id="spec-select"
          v-model="selectedSpec"
          class="form-select"
          :class="{ 'is-invalid': fieldErrors.spec }"
          :disabled="!hasSelectedClass"
          required
          @change="handleSpecChange"
        >
          <option value="">-- Select a specialisation --</option>
          <option
            v-for="spec in specOptions"
            :key="spec.value"
            :value="spec.value"
            :title="`Rôle: ${spec.role}`"
          >
            {{ spec.label }} ({{ spec.role }})
          </option>
        </select>
        <div v-if="fieldErrors.spec" class="field-error">
          {{ fieldErrors.spec }}
        </div>
      </div>

      <div v-if="hasSelectedClass" class="form-section">
        <h3 class="section-title">Summary</h3>

        <div class="summary">
          <div class="summary-grid">
            <div class="summary-item">
              <span class="summary-label">Name :</span>
              <span class="summary-value">{{ characterName || 'Not defined' }}</span>
            </div>
            <div class="summary-item">
              <span class="summary-label">Classe :</span>
              <span class="summary-value">{{ selectedClass }}</span>
            </div>
            <div v-if="hasSelectedSpec" class="summary-item">
              <span class="summary-label">Spécialisation :</span>
              <span class="summary-value">{{ selectedSpec }}</span>
            </div>
            <div v-if="currentSpecRole" class="summary-item">
              <span class="summary-label">Role :</span>
              <span class="summary-value role-badge" :data-role="currentSpecRole">
                {{ getRoleDisplay(currentSpecRole) }}
              </span>
          </div>
          </div>

          <div class="validation-status">
            <div
              class="status-indicator"
              :class="{
                'status-indicator--valid': isValidForm,
                'status-indicator--invalid': hasValidationErrors && hasTriedSubmit,
                'status-indicator--pending': !hasSelectedClass
              }"
            >
              <span class="status-icon">
                <template v-if="isValidForm">✓</template>
                <template v-else-if="hasValidationErrors && hasTriedSubmit">✗</template>
                <template v-else>⏳</template>
              </span>
              <span class="status-text">
                <template v-if="isValidForm">Valid form</template>
                <template v-else-if="hasValidationErrors && hasTriedSubmit">Invalid form</template>
                <template v-else>Being entered</template>
              </span>
            </div>
          </div>
        </div>
      </div>

      <div v-if="hasGeneralErrors" class="general-errors">
        <div class="error-list">
          <div v-for="error in generalErrors" :key="error" class="error-item">
            {{ error }}
          </div>
        </div>
      </div>

      <div class="form-actions">
        <button
          type="button"
          @click="handleReset"
          class="btn btn-secondary"
        >
          Reset
        </button>

        <button
          type="submit"
          class="btn btn-primary"
          :disabled="!isValidForm"
        >
          Create the character
        </button>
      </div>
    </form>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { useGameData } from '../composables/useGameData.ts';
import type {
  Role,
  Character,
  FormSubmitEvent,
  ClassChangeEvent,
  SpecChangeEvent,
  FormErrors,
  CharacterStatus
} from '../interfaces/game.interface';
import { ROLE_ICONS } from '../interfaces/game.interface';

interface Props {
  readonly formTitle?: string;
  readonly formDescription?: string;
  readonly enableAutoValidation?: boolean;
}

interface Emits {
  (event: 'submit', data: FormSubmitEvent): void;
  (event: 'classChange', data: ClassChangeEvent): void;
  (event: 'specChange', data: SpecChangeEvent): void;
  (event: 'error', errors: FormErrors): void;
}

const props = withDefaults(defineProps<Props>(), {
  formTitle: 'Character creation',
  formDescription: 'Select your class and specialisation',
  enableAutoValidation: true
});

const emit = defineEmits<Emits>();

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
  resetAll
} = useGameData();

const characterName = ref<string>('');
const characterLevel = ref<number>();
const fieldErrors = ref<FormErrors>({});
const generalErrors = ref<string[]>([]);
const hasTriedSubmit = ref<boolean>(false);

const isValidForm = computed((): boolean => {
  return (
    isValidSelection.value &&
    characterName.value.trim() !== '' &&
    !hasValidationErrors.value
  );
});

const hasValidationErrors = computed((): boolean =>
  Object.keys(fieldErrors.value).length > 0 ||
  Object.keys(validationErrors.value).length > 0
);

const hasGeneralErrors = computed((): boolean =>
  generalErrors.value.length > 0
);

const getClassDescription = (className: string): string => {
  const gameClass = classOptions.value.find(opt => opt.value === className);
  return gameClass ? `${gameClass.specCount} available specialisations` : '';
};
const getRoleDisplay = (role: Role): string => {
  return `${ROLE_ICONS[role]} ${role}`;
};

const clearFieldError = (field: keyof FormErrors): void => {
  delete fieldErrors.value[field];
};

const clearAllErrors = (): void => {
  fieldErrors.value = {};
  generalErrors.value = [];
};

const validateCharacterName = (): boolean => {
  const name = characterName.value.trim();

  if (!name) {
    fieldErrors.value.name = 'Name is required';
    return false;
  }

  if (name.length < 2) {
    fieldErrors.value.name = 'The name must contain at least 2 characters.';
    return false;
  }

  if (name.length > 50) {
    fieldErrors.value.name = 'The name cannot exceed 50 characters.';
    return false;
  }

  if (!/^[a-zA-Z0-9\s\-_]+$/u.test(name)) {
    fieldErrors.value.name = 'The name contains invalid characters.';
    return false;
  }

  clearFieldError('name');
  return true;
};

const validateForm = (): boolean => {
  const nameValid = validateCharacterName();
  const selectionValid = isValidSelection.value;

  if (!selectionValid && validationErrors.value.class) {
    fieldErrors.value.class = validationErrors.value.class;
  }

  if (!selectionValid && validationErrors.value.spec) {
    fieldErrors.value.spec = validationErrors.value.spec;
  }

  return nameValid && selectionValid;
};

const handleClassChange = (): void => {
  clearFieldError('class');
  clearFieldError('spec');

  emit('classChange', {
    className: selectedClass.value,
    availableSpecs: availableSpecs.value
  });
};

const handleSpecChange = (): void => {
  clearFieldError('spec');

  if (hasSelectedSpec.value && currentSpecRole.value) {
    emit('specChange', {
      className: selectedClass.value,
      specName: selectedSpec.value,
      role: currentSpecRole.value
    });
  }
};
const handleReset = (): void => {
  characterName.value = '';
  characterLevel.value = undefined;
  resetAll();
  clearAllErrors();
  hasTriedSubmit.value = false;
};

const handleSubmit = (): void => {
  hasTriedSubmit.value = true;

  if (!validateForm()) {
    emit('error', { ...fieldErrors.value, ...validationErrors.value });
    return;
  }

  const characterData: Omit<Character, 'id' | 'createdAt'> = {
    name: characterName.value.trim(),
    class: selectedClass.value,
    spec: selectedSpec.value || undefined,
    role: currentSpecRole.value || undefined,
    status: 'active' as CharacterStatus,
    level: characterLevel.value
  };

  const formData: FormSubmitEvent = {
    character: characterData,
    isValid: true
  };

  emit('submit', formData);

};

if (props.enableAutoValidation) {
  watch([characterName, characterLevel], () => {
    if (hasTriedSubmit.value) {
      validateForm();
    }
  });
}
</script>

<style scoped>
.character-form {
  max-width: 700px;
  margin: 0 auto;
  padding: 2rem;
  background: #ffffff;
  border-radius: 12px;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  font-family: 'Inter', 'Segoe UI', sans-serif;
}

.form-header {
  text-align: center;
  margin-bottom: 2rem;
}

.form-header h2 {
  font-size: 1.75rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0 0 0.5rem 0;
}

.form-description {
  color: #64748b;
  margin: 0;
}

.form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-section {
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 1.5rem;
  background: #f8fafc;
}

.section-title {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0 0 1rem 0;
}

.subsection-title {
  font-size: 1rem;
  font-weight: 600;
  color: #374151;
  margin: 0 0 1rem 0;
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
  color: #374151;
}

.form-label.required::after {
  content: ' *';
  color: #ef4444;
}

.form-input,
.form-select {
  padding: 0.75rem;
  border: 2px solid #e2e8f0;
  border-radius: 6px;
  font-size: 1rem;
  background: #ffffff;
  transition: border-color 0.2s ease;
  color: black;
}

.form-input:focus,
.form-select:focus {
  outline: none;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.form-input.is-invalid,
.form-select.is-invalid {
  border-color: #ef4444;
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

.role-classes {
  margin-top: 1rem;
}

.class-cards {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.class-card {
  background: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 6px;
  padding: 1rem;
  transition: all 0.2s ease;
}

.class-card:hover {
  border-color: #3b82f6;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.class-card--selected {
  border-color: #3b82f6;
  background: #eff6ff;
}

.class-card__title {
  margin: 0 0 0.75rem 0;
  font-size: 1rem;
  font-weight: 600;
}

.class-button {
  background: none;
  border: none;
  color: #3b82f6;
  cursor: pointer;
  text-decoration: underline;
  font-weight: inherit;
  font-size: inherit;
  padding: 0;
}

.class-button:hover {
  color: #2563eb;
}

.class-button.active {
  color: #1d4ed8;
  font-weight: 700;
}

.class-card__specs {
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.spec-tag--selected .spec-button {
  background: #3b82f6;
  color: white;
}

.spec-button {
  background: #f1f5f9;
  border: 1px solid #e2e8f0;
  border-radius: 4px;
  padding: 0.25rem 0.5rem;
  font-size: 0.875rem;
  cursor: pointer;
  transition: all 0.2s ease;
}

.spec-button:hover {
  background: #3b82f6;
  color: white;
  border-color: #3b82f6;
}

.summary {
  background: #ffffff;
  border-radius: 6px;
  padding: 1.5rem;
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
  margin-bottom: 1.5rem;
}

.summary-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 0.5rem;
  background: #f8fafc;
  border-radius: 4px;
}

.summary-label {
  font-weight: 600;
  color: #374151;
}

.summary-value {
  font-weight: 500;
  color: black;
}

.role-badge {
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  font-size: 0.875rem;
  font-weight: 600;
}

.role-badge[data-role="Tanks"] {
  background: #dbeafe;
  color: #1e40af;
}

.role-badge[data-role="Healers"] {
  background: #dcfce7;
  color: #166534;
}

.role-badge[data-role="Melee"] {
  background: #fee2e2;
  color: #991b1b;
}

.role-badge[data-role="Ranged"] {
  background: #fed7aa;
  color: #9a3412;
}

.validation-status {
  text-align: center;
}

.status-indicator {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1rem;
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.875rem;
}

.status-indicator--valid {
  background: #dcfce7;
  color: #166534;
}

.status-indicator--invalid {
  background: #fee2e2;
  color: #991b1b;
}

.status-indicator--pending {
  background: #fef3c7;
  color: #92400e;
}

.status-icon {
  font-size: 1.125rem;
}

.general-errors {
  background: #fef2f2;
  border: 1px solid #fecaca;
  border-radius: 6px;
  padding: 1rem;
}

.error-list {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.error-item {
  color: #ef4444;
  font-size: 0.875rem;
  font-weight: 500;
}

.form-actions {
  display: flex;
  gap: 1rem;
  justify-content: flex-end;
  padding-top: 1rem;
  border-top: 1px solid #e2e8f0;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 6px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none !important;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover:not(:disabled) {
  background: #2563eb;
  transform: translateY(-1px);
}

.btn-secondary {
  background: #6b7280;
  color: white;
}

.btn-secondary:hover:not(:disabled) {
  background: #4b5563;
  transform: translateY(-1px);
}

@media (max-width: 768px) {
  .character-form {
    padding: 1rem;
    margin: 1rem;
  }

  .class-cards {
    grid-template-columns: 1fr;
  }

  .summary-grid {
    grid-template-columns: 1fr;
  }

  .form-actions {
    flex-direction: column;
  }
}
</style>
