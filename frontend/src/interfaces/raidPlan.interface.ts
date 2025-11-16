export type BlockType =
  | 'BOSS_GRID'
  | 'PHASE_STRATEGY'
  | 'POSITIONING_MAP'
  | 'GROUPS_GRID'
  | 'ROLE_MATRIX'
  | 'BENCH_ROSTER'
  | 'TEXT'
  | 'HEADING'
  | 'DIVIDER'
  | 'CHECKLIST'
  | 'LOOT_PRIORITY'
  | 'COOLDOWN_ROTATION'
  | 'INTERRUPT_ROTATION'
  | 'CUSTOM_SECTION'
  | 'CUSTOM_GRID'
  | 'IMAGE';

export interface RaidPlanBlockBase {
  id: string;
  type: BlockType;
  colStart: number; // 1–12
  colSpan: number;  // 1–12
  row: number;
}

export interface RaidPlanBlock extends RaidPlanBlockBase {
  title?: string;
  data?: {

    textContent?: string;

    headingText?: string;
    headingLevel?: 1 | 2 | 3;

    dividerLabel?: string;
    dividerStyle?: 'solid' | 'dashed';

    checklistItems?: {
      id: string;
      label: string;
      done: boolean;
    }[];

    imageUrl?: string;
    imageCaption?: string;
    imageSize?: 'contain' | 'cover' | 'fill';

    [key: string]: unknown;
  };
}
