import {
  ref,
  computed,
  readonly,
  type Ref,
  type ComputedRef
} from 'vue';

import {
  Role,
  type GameClass,
  type GameSpec,
  type ClassOption,
  type SpecOption,
  type SelectOption,
  type ValidationResult,
  type FormErrors
} from '../interfaces/game.interface';

import {
  GAME_CLASSES,
  getSpecsByClass,
  getRoleByClassAndSpec,
  getClassesByRole,
  isValidClassName,
  isValidClassSpec,
  validateClassSpecSelection,
  getClassOptions,
  getSpecOptions,
  getRoleOptions
} from '../data/gameData';

interface UseGameDataReturn {
  selectedClass: Ref<string>;
  selectedSpec: Ref<string>;
  selectedRole: Ref<Role | ''>;

  gameClasses: ComputedRef<readonly GameClass[]>;

  availableSpecs: ComputedRef<readonly GameSpec[]>;
  currentSpecRole: ComputedRef<Role | undefined>;
  classesByRole: ComputedRef<readonly GameClass[]>;

  classOptions: ComputedRef<readonly ClassOption[]>;
  specOptions: ComputedRef<readonly SpecOption[]>;
  roleOptions: ComputedRef<readonly SelectOption[]>;

  hasSelectedClass: ComputedRef<boolean>;
  hasSelectedSpec: ComputedRef<boolean>;
  canSelectSpec: ComputedRef<boolean>;
  isValidSelection: ComputedRef<boolean>;

  validationResult: ComputedRef<ValidationResult>;
  validationErrors: ComputedRef<FormErrors>;

  setClass: (className: string) => boolean;
  setSpec: (specName: string) => boolean;
  setRole: (role: Role | '') => void;
  resetClass: () => void;
  resetSpec: () => void;
  resetAll: () => void;
  validateSelection: () => ValidationResult;
}

export function useGameData(): UseGameDataReturn {

  const selectedClass = ref<string>('');
  const selectedSpec = ref<string>('');
  const selectedRole = ref<Role | ''>('');

  const gameClasses = computed((): readonly GameClass[] =>
    readonly(GAME_CLASSES)
  );

  const availableSpecs = computed((): readonly GameSpec[] => {
    if (!selectedClass.value) return [];
    return getSpecsByClass(selectedClass.value);
  });

  const currentSpecRole = computed((): Role | undefined => {
    if (!selectedClass.value || !selectedSpec.value) return undefined;
    return getRoleByClassAndSpec(selectedClass.value, selectedSpec.value);
  });

  const classesByRole = computed((): readonly GameClass[] => {
    if (!selectedRole.value) return gameClasses.value;
    return getClassesByRole(selectedRole.value as Role);
  });

  const classOptions = computed((): readonly ClassOption[] => {
    return getClassOptions();
  });

  const specOptions = computed((): readonly SpecOption[] => {
    if (!selectedClass.value) return [];
    return getSpecOptions(selectedClass.value);
  });

  const roleOptions = computed((): readonly SelectOption[] => {
    return getRoleOptions();
  });

  const hasSelectedClass = computed((): boolean => {
    return selectedClass.value !== '';
  });

  const hasSelectedSpec = computed((): boolean => {
    return selectedSpec.value !== '';
  });

  const canSelectSpec = computed((): boolean => {
    return hasSelectedClass.value && availableSpecs.value.length > 0;
  });

  const isValidSelection = computed((): boolean => {
    const validation = validateClassSpecSelection(
      selectedClass.value || undefined,
      selectedSpec.value || undefined
    );
    return validation.valid;
  });

  const validationResult = computed((): ValidationResult => {
    return validateClassSpecSelection(
      selectedClass.value || undefined,
      selectedSpec.value || undefined
    );
  });

  const validationErrors = computed((): FormErrors => {
    const result = validationResult.value;
    const errors: FormErrors = {};

    for (const error of result.errors) {
      if (error.field === 'class') {
        errors.class = error.message;
      } else if (error.field === 'spec') {
        errors.spec = error.message;
      } else {
        errors.general = error.message;
      }
    }

    return errors;
  });

  const setClass = (className: string): boolean => {
    if (!isValidClassName(className)) {
      console.warn(`[useGameData] Invalid class: ${className}`);
      return false;
    }

    selectedClass.value = className;
    selectedSpec.value = '';

    return true;
  };

  const setSpec = (specName: string): boolean => {
    if (!selectedClass.value) {
      console.warn('[useGameData] No class selected');
      return false;
    }

    if (!isValidClassSpec(selectedClass.value, specName)) {
      console.warn(`[useGameData] Invalid specialization: ${specName} for ${selectedClass.value}`);
      return false;
    }

    selectedSpec.value = specName;
    return true;
  };

  const setRole = (role: Role | ''): void => {
    selectedRole.value = role;
  };

  const resetClass = (): void => {
    selectedClass.value = '';
    selectedSpec.value = '';
  };

  const resetSpec = (): void => {
    selectedSpec.value = '';
  };

  const resetAll = (): void => {
    selectedClass.value = '';
    selectedSpec.value = '';
    selectedRole.value = '';
  };

  
  const validateSelection = (): ValidationResult => {
    return validationResult.value;
  };

  return {
    selectedClass,
    selectedSpec,
    selectedRole,

    gameClasses,

    availableSpecs,
    currentSpecRole,
    classesByRole,
    classOptions,
    specOptions,
    roleOptions,
    hasSelectedClass,
    hasSelectedSpec,
    canSelectSpec,
    isValidSelection,

    validationResult,
    validationErrors,

    setClass,
    setSpec,
    setRole,
    resetClass,
    resetSpec,
    resetAll,
    validateSelection
  } as const;
}

export default useGameData;
