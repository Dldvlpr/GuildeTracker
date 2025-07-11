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
          Nom du personnage
        </label>
        <input
          id="character-name"
          v-model="characterName"
          type="text"
          class="form-input"
          :class="{ 'is-invalid': fieldErrors.name }"
          placeholder="Entrez le nom de votre personnage"
          required
          maxlength="50"
          @blur="validateCharacterName"
          @input="clearFieldError('name')"
        />
        <div v-if="fieldErrors.name" class="field-error">
          {{ fieldErrors.name }}
        </div>
        <div v-else class="field-hint">
          Maximum 50 caractères, lettres et chiffres uniquement
        </div>
      </div>

      <div class="form-group">
        <label for="class-select" class="form-label required">
          Classe
        </label>
        <select
          id="class-select"
          v-model="selectedClass"
          class="form-select"
          :class="{ 'is-invalid': fieldErrors.class }"
          required
          @change="handleClassChange"
        >
          <option value="">-- Sélectionnez une classe --</option>
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

      <!-- Sélection de spécialisation -->
      <div
        v-if="canSelectSpec"
        class="form-group"
        :class="{ 'form-group--disabled': !hasSelectedClass }"
      >
        <label for="spec-select" class="form-label required">
          Spécialisation
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
          <option value="">-- Sélectionnez une spécialisation --</option>
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

      <!-- Niveau (optionnel) -->
      <div class="form-group">
        <label for="character-level" class="form-label">
          Niveau (optionnel)
        </label>
        <input
          id="character-level"
          v-model.number="characterLevel"
          type="number"
          class="form-input"
          :class="{ 'is-invalid': fieldErrors.level }"
          min="1"
          max="80"
          placeholder="1-80"
          @blur="validateLevel"
          @input="clearFieldError('level')"
        />
        <div v-if="fieldErrors.level" class="field-error">
          {{ fieldErrors.level }}
        </div>
        <div v-else class="field-hint">
          Niveau entre 1 et 80
        </div>
      </div>

      <div class="form-section">
        <h3 class="section-title">Exploration par rôle</h3>

        <div class="form-group">
          <label for="role-filter" class="form-label">
            Filtrer par rôle
          </label>
          <select
            id="role-filter"
            v-model="selectedRole"
            class="form-select"
            @change="handleRoleChange"
          >
            <option value="">-- Tous les rôles --</option>
            <option
              v-for="role in roleOptions"
              :key="role.value"
              :value="role.value"
            >
              {{ role.label }}
            </option>
          </select>
        </div>

        <div v-if="selectedRole" class="role-classes">
          <h4 class="subsection-title">
            Classes disponibles pour {{ selectedRole }}
          </h4>
          <div class="class-cards">
            <div
              v-for="gameClass in classesByRole"
              :key="gameClass.name"
              class="class-card"
              :class="{ 'class-card--selected': selectedClass === gameClass.name }"
            >
              <h5 class="class-card__title">
                <button
                  type="button"
                  @click="selectClassFromRole(gameClass.name)"
                  class="class-button"
                  :class="{ 'active': selectedClass === gameClass.name }"
                >
                  {{ gameClass.name }}
                </button>
              </h5>
              <div class="class-card__specs">
                <span
                  v-for="spec in getSpecsForRole(gameClass)"
                  :key="spec.name"
                  class="spec-tag"
                  :class="{ 'spec-tag--selected': selectedSpec === spec.name }"
                >
                  <button
                    type="button"
                    @click="selectSpecFromRole(gameClass.name, spec.name)"
                    class="spec-button"
                  >
                    {{ spec.name }}
                  </button>
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div v-if="hasSelectedClass" class="form-section">
        <h3 class="section-title">Récapitulatif</h3>

        <div class="summary">
          <div class="summary-grid">
            <div class="summary-item">
              <span class="summary-label">Nom :</span>
              <span class="summary-value">{{ characterName || 'Non défini' }}</span>
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
              <span class="summary-label">Rôle :</span>
              <span class="summary-value role-badge" :data-role="currentSpecRole">
                {{ getRoleDisplay(currentSpecRole) }}
              </span>
            </div>
            <div v-if="characterLevel" class="summary-item">
              <span class="summary-label">Niveau :</span>
              <span class="summary-value">{{ characterLevel }}</span>
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
                <template v-if="isValidForm">Formulaire valide</template>
                <template v-else-if="hasValidationErrors && hasTriedSubmit">Formulaire invalide</template>
                <template v-else>En cours de saisie</template>
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
          Réinitialiser
        </button>

        <button
          type="submit"
          class="btn btn-primary"
          :disabled="!isValidForm"
        >
          Créer le personnage
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
  GameClass,
  GameSpec,
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
  formTitle: 'Création de personnage',
  formDescription: 'Sélectionnez votre classe et spécialisation',
  enableAutoValidation: true
});

const emit = defineEmits<Emits>();

const {
  selectedClass,
  selectedSpec,
  selectedRole,
  availableSpecs,
  classesByRole,
  currentSpecRole,
  classOptions,
  specOptions,
  roleOptions,
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
  return gameClass ? `${gameClass.specCount} spécialisations disponibles` : '';
};

const getSpecsForRole = (gameClass: GameClass): GameSpec[] => {
  if (!selectedRole.value) return [...gameClass.specs];

  return gameClass.specs.filter(spec =>
    spec.role === selectedRole.value
  );
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
    fieldErrors.value.name = 'Le nom est requis';
    return false;
  }

  if (name.length < 2) {
    fieldErrors.value.name = 'Le nom doit contenir au moins 2 caractères';
    return false;
  }

  if (name.length > 50) {
    fieldErrors.value.name = 'Le nom ne peut pas dépasser 50 caractères';
    return false;
  }

  if (!/^[a-zA-Z0-9\s\-_àáâãäåçèéêëìíîïñòóôõöùúûüýÿ]+$/u.test(name)) {
    fieldErrors.value.name = 'Le nom contient des caractères invalides';
    return false;
  }

  clearFieldError('name');
  return true;
};

const validateLevel = (): boolean => {
  if (characterLevel.value === undefined) {
    clearFieldError('level');
    return true;
  }

  if (!Number.isInteger(characterLevel.value)) {
    fieldErrors.value.level = 'Le niveau doit être un nombre entier';
    return false;
  }

  if (characterLevel.value < 1 || characterLevel.value > 80) {
    fieldErrors.value.level = 'Le niveau doit être entre 1 et 80';
    return false;
  }

  clearFieldError('level');
  return true;
};

const validateForm = (): boolean => {
  const nameValid = validateCharacterName();
  const levelValid = validateLevel();
  const selectionValid = isValidSelection.value;

  if (!selectionValid && validationErrors.value.class) {
    fieldErrors.value.class = validationErrors.value.class;
  }

  if (!selectionValid && validationErrors.value.spec) {
    fieldErrors.value.spec = validationErrors.value.spec;
  }

  return nameValid && levelValid && selectionValid;
};

/**
 * GESTIONNAIRES D'ÉVÉNEMENTS
 */
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

const handleRoleChange = (): void => {
};

const selectClassFromRole = (className: string): void => {
  if (setClass(className)) {
    clearAllErrors();
  }
};

const selectSpecFromRole = (className: string, specName: string): void => {
  if (setClass(className) && setSpec(specName)) {
    clearAllErrors();
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

  // handleReset();
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
