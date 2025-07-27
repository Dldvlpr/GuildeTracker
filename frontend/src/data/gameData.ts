import {
  Role,
  type GameClass,
  type GameSpec,
  type ClassOption,
  type SpecOption,
  type SelectOption,
  type ValidationResult,
  type ValidationError,
  ROLE_ICONS
} from '../interfaces/game.interface.ts';

export const GAME_CLASSES = [
  {
    name: 'Warrior',
    specs: [
      { name: 'Arms', role: Role.MELEE },
      { name: 'Fury', role: Role.MELEE },
      { name: 'Protection', role: Role.TANKS }
    ],
  },
  {
    name: 'Druid',
    specs: [
      { name: 'Balance', role: Role.RANGED },
      { name: 'Dreamstate', role: Role.HEALERS },
      { name: 'Feral', role: Role.MELEE },
      { name: 'Restoration', role: Role.HEALERS },
      { name: 'Guardian', role: Role.TANKS }
    ],
  },
  {
    name: 'Paladin',
    specs: [
      { name: 'Holy', role: Role.HEALERS },
      { name: 'Protection', role: Role.TANKS },
      { name: 'Retribution', role: Role.MELEE }
    ],
  },
  {
    name: 'Rogue',
    specs: [
      { name: 'Assassination', role: Role.MELEE },
      { name: 'Combat', role: Role.MELEE },
      { name: 'Subtlety', role: Role.MELEE }
    ],
  },
  {
    name: 'Hunter',
    specs: [
      { name: 'Beastmastery', role: Role.RANGED },
      { name: 'Marksmanship', role: Role.RANGED },
      { name: 'Survival', role: Role.RANGED }
    ],
  },
  {
    name: 'Priest',
    specs: [
      { name: 'Discipline', role: Role.HEALERS },
      { name: 'Holy', role: Role.HEALERS },
      { name: 'Shadow', role: Role.RANGED },
      { name: 'Smite', role: Role.RANGED }
    ],
  },
  {
    name: 'Mage',
    specs: [
      { name: 'Arcane', role: Role.RANGED },
      { name: 'Fire', role: Role.RANGED },
      { name: 'Frost', role: Role.RANGED }
    ],
  },
  {
    name: 'Warlock',
    specs: [
      { name: 'Affliction', role: Role.RANGED },
      { name: 'Demonology', role: Role.RANGED },
      { name: 'Destruction', role: Role.RANGED }
    ],
  },
  {
    name: 'Shaman',
    specs: [
      { name: 'Elemental', role: Role.RANGED },
      { name: 'Enhancement', role: Role.MELEE },
      { name: 'Restoration', role: Role.HEALERS }
    ],
  }
] as const satisfies readonly GameClass[];

export function getClassByName(name: string): GameClass | undefined {
  return GAME_CLASSES.find((gameClass): gameClass is GameClass =>
    gameClass.name === name
  );
}

export function getSpecsByClass(className: string): readonly GameSpec[] {
  const gameClass = getClassByName(className);
  return gameClass?.specs ?? [];
}

export function getSpecByName(
  className: string,
  specName: string
): GameSpec | undefined {
  const specs = getSpecsByClass(className);
  return specs.find((spec): spec is GameSpec => spec.name === specName);
}

export function getRoleByClassAndSpec(
  className: string,
  specName: string
): Role | undefined {
  const spec = getSpecByName(className, specName);
  return spec?.role;
}

export function getClassesByRole(role: Role): readonly GameClass[] {
  return GAME_CLASSES.filter((gameClass): gameClass is GameClass =>
    gameClass.specs.some((spec): boolean => spec.role === role)
  );
}

export function isValidClassName(className: string): boolean {
  return GAME_CLASSES.some((gameClass): boolean => gameClass.name === className);
}

export function isValidClassSpec(className: string, specName: string): boolean {
  const gameClass = getClassByName(className);
  if (!gameClass) return false;

  return gameClass.specs.some((spec): boolean => spec.name === specName);
}

export function isValidRole(role: string): role is Role {
  return Object.values(Role).includes(role as Role);
}

export function validateClassSpecSelection(
  className?: string,
  specName?: string
): ValidationResult {
  const errors: ValidationError[] = [];

  if (!className) {
    errors.push({
      field: 'class',
      message: 'Une classe doit être sélectionnée',
      code: 'REQUIRED'
    });
  } else if (!isValidClassName(className)) {
    errors.push({
      field: 'class',
      message: `Classe invalide: ${className}`,
      value: className,
      code: 'INVALID_CLASS'
    });
  }

  if (className && specName) {
    if (!isValidClassSpec(className, specName)) {
      errors.push({
        field: 'spec',
        message: `Spécialisation "${specName}" invalide pour la classe "${className}"`,
        value: { className, specName },
        code: 'INVALID_SPEC'
      });
    }
  }

  return {
    valid: errors.length === 0,
    errors
  };
}

export function getClassOptions(): readonly ClassOption[] {
  return GAME_CLASSES.map((gameClass): ClassOption => ({
    value: gameClass.name,
    label: gameClass.name,
    specCount: gameClass.specs.length
  }));
}

export function getSpecOptions(className: string): readonly SpecOption[] {
  const specs = getSpecsByClass(className);

  return specs.map((spec): SpecOption => ({
    value: spec.name,
    label: spec.name,
    role: spec.role
  }));
}

export function getRoleOptions(): readonly SelectOption[] {
  return Object.values(Role).map((role): SelectOption => ({
    value: role,
    label: `${ROLE_ICONS[role]} ${role}`
  }));
}

export function isGameClass(obj: unknown): obj is GameClass {
  if (typeof obj !== 'object' || obj === null) {
    return false;
  }

  const candidate = obj as Record<string, unknown>;

  return (
    'name' in candidate &&
    'specs' in candidate &&
    typeof candidate.name === 'string' &&
    Array.isArray(candidate.specs) &&
    candidate.specs.every((spec): boolean => isGameSpec(spec))
  );
}

export function isGameSpec(obj: unknown): obj is GameSpec {
  if (typeof obj !== 'object' || obj === null) {
    return false;
  }

  const candidate = obj as Record<string, unknown>;

  return (
    'name' in candidate &&
    'role' in candidate &&
    typeof candidate.name === 'string' &&
    typeof candidate.role === 'string' &&
    isValidRole(candidate.role)
  );
}

export default {
  GAME_CLASSES,
  getClassByName,
  getSpecsByClass,
  getSpecByName,
  getRoleByClassAndSpec,
  getClassesByRole,
  isValidClassName,
  isValidClassSpec,
  isValidRole,
  validateClassSpecSelection,
  getClassOptions,
  getSpecOptions,
  getRoleOptions,
  isGameClass,
  isGameSpec
} as const;
