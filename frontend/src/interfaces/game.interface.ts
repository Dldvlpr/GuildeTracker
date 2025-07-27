export enum Role {
  TANKS = 'Tanks',
  MELEE = 'Melee',
  RANGED = 'Ranged',
  HEALERS = 'Healers'
}

export enum CharacterStatus {
  ACTIVE = 'active',
  INACTIVE = 'inactive',
  BENCH = 'bench',
  ABSENCE = 'absence'
}

export interface GameSpec {
  readonly name: string;
  readonly role: Role;
}

export interface GameClass {
  readonly name: string;
  readonly specs: readonly GameSpec[];
}

export interface Character {
  readonly id: string;
  readonly name: string;
  readonly class: string;
  readonly spec?: string;
  readonly role?: Role;
  readonly status: CharacterStatus;
  readonly level?: number;
  readonly createdAt: string;
  readonly updatedAt?: string;
}

export interface SelectOption {
  readonly value: string;
  readonly label: string;
}

export interface ClassOption extends SelectOption {
  readonly specCount: number;
}

export interface SpecOption extends SelectOption {
  readonly role: Role;
}

export interface ValidationError {
  readonly field: string;
  readonly message: string;
  readonly value?: unknown;
  readonly code?: string;
}

export interface ValidationResult {
  readonly valid: boolean;
  readonly errors: readonly ValidationError[];
}

export interface FormErrors {
  readonly name?: string;
  class?: string;
  spec?: string;
  general?: string;
}

export interface FormSubmitEvent {
  readonly character: Omit<Character, 'id' | 'createdAt'>;
  readonly isValid: boolean;
}

export interface ClassChangeEvent {
  readonly className: string;
  readonly availableSpecs: readonly GameSpec[];
}

export interface SpecChangeEvent {
  readonly className: string;
  readonly specName: string;
  readonly role: Role;
}

export type Optional<T, K extends keyof T> = Omit<T, K> & Partial<Pick<T, K>>;
export type RequiredFields<T, K extends keyof T> = T & Required<Pick<T, K>>;

export const ROLE_COLORS: Record<Role, string> = {
  [Role.TANKS]: '#1f77b4',
  [Role.HEALERS]: '#2ca02c',
  [Role.MELEE]: '#d62728',
  [Role.RANGED]: '#ff7f0e'
} as const;

export const ROLE_ICONS: Record<Role, string> = {
  [Role.TANKS]: '🛡️',
  [Role.HEALERS]: '💚',
  [Role.MELEE]: '⚔️',
  [Role.RANGED]: '🏹'
} as const;
