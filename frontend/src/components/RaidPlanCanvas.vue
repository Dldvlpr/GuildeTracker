<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted, computed } from 'vue';
import Draggable from 'vuedraggable';
import type { RaidPlanBlock } from '@/interfaces/raidPlan.interface.ts';
import type { Character } from '@/interfaces/game.interface';
import { Role, ROLE_COLORS } from '@/interfaces/game.interface';
import { getSpecOptions, getRoleByClassAndSpec } from '@/data/gameData';
import { getClassColor } from '@/utils/classColors';

const props = defineProps<{
  blocks: RaidPlanBlock[];
  characters?: Character[];
  groupTargets?: { tank: number; healer: number } | null;
}>();

const emit = defineEmits<{
  (e: 'update-block', block: RaidPlanBlock): void;
  (e: 'remove-block', id: string): void;
  (e: 'reorder-blocks', blocks: RaidPlanBlock[]): void;
}>();

const selectedBlockId = ref<string | null>(null);
const allowedSpans = [2, 3, 4, 6, 8, 12];
const hoveredRoleKey = ref<string | null>(null);
const hoveredGroupKey = ref<string | null>(null);
const hoveredPositionId = ref<string | null>(null);
const allowedStarts = [1, 3, 5, 7, 9, 11];
const resizing = ref(false);
const showGridOverlay = ref(false);

function detectCollisions(blocks: RaidPlanBlock[]): Map<string, string[]> {
  const collisions = new Map<string, string[]>();

  for (let i = 0; i < blocks.length; i++) {
    const blockA = blocks[i];
    const rowA = (blockA as any).row || 0;
    if (rowA === 0) continue; // Skip auto-flow blocks

    for (let j = i + 1; j < blocks.length; j++) {
      const blockB = blocks[j];
      const rowB = (blockB as any).row || 0;
      if (rowB === 0) continue; // Skip auto-flow blocks

      if (rowA !== rowB) continue;

      const aStart = blockA.colStart;
      const aEnd = blockA.colStart + blockA.colSpan - 1;
      const bStart = blockB.colStart;
      const bEnd = blockB.colStart + blockB.colSpan - 1;

      if (!(aEnd < bStart || bEnd < aStart)) {

        if (!collisions.has(blockA.id)) collisions.set(blockA.id, []);
        if (!collisions.has(blockB.id)) collisions.set(blockB.id, []);
        collisions.get(blockA.id)!.push(blockB.id);
        collisions.get(blockB.id)!.push(blockA.id);
      }
    }
  }

  return collisions;
}

const collisions = computed(() => detectCollisions(innerBlocks.value));

const innerBlocks = ref<RaidPlanBlock[]>([...props.blocks]);

watch(
  () => props.blocks,
  (newVal) => {
    innerBlocks.value = [...newVal];
  },
  { deep: true }
);

function selectBlock(id: string) {
  selectedBlockId.value = id;
  showGridOverlay.value = true;

  setTimeout(() => {
    if (!resizing.value) showGridOverlay.value = false;
  }, 2000);
}

function remove(id: string) {
  emit('remove-block', id);
  if (selectedBlockId.value === id) {
    selectedBlockId.value = null;
  }
}

function snapSpan(rawSpan: number): number {
  let best = allowedSpans[0];
  let bestDiff = Math.abs(rawSpan - best);
  for (const s of allowedSpans) {
    const diff = Math.abs(rawSpan - s);
    if (diff < bestDiff) {
      best = s;
      bestDiff = diff;
    }
  }
  return best;
}

function updateBlockData(block: RaidPlanBlock, partial: Record<string, unknown>) {
  emit('update-block', {
    ...block,
    data: {
      ...(block.data || {}),
      ...partial,
    },
  });
}


function setBlockSpan(block: RaidPlanBlock, rawSpan: number) {
  const snapped = snapSpan(rawSpan);
  const maxStart = 12 - snapped + 1;
  const safeStart = Math.min(block.colStart, maxStart);

  emit('update-block', {
    ...block,
    colSpan: snapped,
    colStart: safeStart,
  });
}

function setBlockStart(block: RaidPlanBlock, rawStart: number) {
  const start = Math.max(1, Math.min(rawStart, 12));
  const maxStart = 12 - block.colSpan + 1;
  const safeStart = Math.min(start, maxStart);

  emit('update-block', {
    ...block,
    colStart: safeStart,
  });
}

function onStartInput(block: RaidPlanBlock, event: Event) {
  const target = event.target as HTMLInputElement;
  const value = Number(target.value || 1);
  setBlockStart(block, value);
}

function onSpanInput(block: RaidPlanBlock, event: Event) {
  const target = event.target as HTMLInputElement;
  const value = Number(target.value || 1);
  setBlockSpan(block, value);
}

function onResizeLeft(block: RaidPlanBlock, e: MouseEvent) {
  e.preventDefault();
  e.stopPropagation();
  resizing.value = true;
  showGridOverlay.value = true;
  const startX = e.clientX;
  const startColStart = block.colStart;
  const startColSpan = block.colSpan;

  const onMove = (moveEvent: MouseEvent) => {
    const delta = moveEvent.clientX - startX;
    const colWidth = (moveEvent.target as HTMLElement)?.closest('.planner-grid')?.clientWidth || 1200;
    const colChange = Math.round(delta / (colWidth / 12));

    const newStart = Math.max(1, Math.min(startColStart + colChange, 12));
    const newSpan = Math.max(2, startColSpan - colChange);

    if (newStart + newSpan - 1 <= 12) {
      emit('update-block', { ...block, colStart: newStart, colSpan: snapSpan(newSpan) });
    }
  };

  const onUp = () => {
    resizing.value = false;
    document.removeEventListener('mousemove', onMove);
    document.removeEventListener('mouseup', onUp);
  };

  document.addEventListener('mousemove', onMove);
  document.addEventListener('mouseup', onUp);
}

function onResizeRight(block: RaidPlanBlock, e: MouseEvent) {
  e.preventDefault();
  e.stopPropagation();
  resizing.value = true;
  showGridOverlay.value = true;
  const startX = e.clientX;
  const startColSpan = block.colSpan;

  const onMove = (moveEvent: MouseEvent) => {
    const delta = moveEvent.clientX - startX;
    const colWidth = (moveEvent.target as HTMLElement)?.closest('.planner-grid')?.clientWidth || 1200;
    const colChange = Math.round(delta / (colWidth / 12));

    const newSpan = Math.max(2, Math.min(startColSpan + colChange, 12 - block.colStart + 1));
    emit('update-block', { ...block, colSpan: snapSpan(newSpan) });
  };

  const onUp = () => {
    resizing.value = false;
    document.removeEventListener('mousemove', onMove);
    document.removeEventListener('mouseup', onUp);
  };

  document.addEventListener('mousemove', onMove);
  document.addEventListener('mouseup', onUp);
}

function onDragChange() {

  emit('reorder-blocks', innerBlocks.value);
}

function onGlobalKey(e: KeyboardEvent) {
  if (!selectedBlockId.value) return;
  const block = innerBlocks.value.find(b => b.id === selectedBlockId.value);
  if (!block) return;
  const key = e.key.toLowerCase();
  const step = e.shiftKey ? 2 : 1;
  if (key === 'arrowleft') { setBlockStart(block, block.colStart - step); e.preventDefault(); }
  if (key === 'arrowright') { setBlockStart(block, block.colStart + step); e.preventDefault(); }
  if (key === 'arrowup') { const r = (block as any).row || 0; emit('update-block', { ...block, row: Math.max(0, r - step) }); e.preventDefault(); }
  if (key === 'arrowdown') { const r = (block as any).row || 0; emit('update-block', { ...block, row: r + step }); e.preventDefault(); }
}

onMounted(() => {
  window.addEventListener('keydown', onGlobalKey);
});
onUnmounted(() => {
  window.removeEventListener('keydown', onGlobalKey);
});

function addChecklistItem(block: RaidPlanBlock) {
  const items = (block.data?.checklistItems ?? []).slice();
  const newId = String(Date.now()) + '-' + String(Math.random()).slice(2, 6);

  items.push({
    id: newId,
    label: 'New item',
    done: false,
  });

  updateBlockData(block, { checklistItems: items });
}

type CellMap = Record<string, Record<string, string[]>> // rowId -> colId -> [charId]

function ensureCells(block: RaidPlanBlock): CellMap {
  const cells = (block.data?.cells as any) ?? {}
  if (!block.data) (block as any).data = {}
  if (!block.data!.cells) (block.data as any).cells = {}
  return cells as CellMap
}

function getCell(block: RaidPlanBlock, rowId: string, colId: string): string[] {
  const cells = ensureCells(block)
  const row = cells[rowId] ?? (cells[rowId] = {})
  const cur = row[colId]
  if (cur && typeof cur === 'object' && !Array.isArray(cur)) {
    // Convert pair -> list ([from, to]) for simple mode
    const from = (cur as any).from || null
    const to = (cur as any).to || null
    const arr = [from, to].filter(Boolean) as string[]
    row[colId] = arr
    updateBlockData(block, { cells })
    return arr
  }
  const arr = row[colId] ?? (row[colId] = [])
  return arr as string[]
}

function dropToCell(block: RaidPlanBlock, rowId: string, colId: string, e: DragEvent) {
  const charId = e.dataTransfer?.getData('text/plain')
  if (!charId) return
  const cells = ensureCells(block)
  const row = cells[rowId] ?? (cells[rowId] = {})
  const arr = row[colId] ?? (row[colId] = [])
  if (!arr.includes(charId)) arr.push(charId)
  updateBlockData(block, { cells })
}

function removeFromCell(block: RaidPlanBlock, rowId: string, colId: string, charId: string) {
  const cells = ensureCells(block)
  const row = cells[rowId] ?? (cells[rowId] = {})
  const arr = row[colId] ?? (row[colId] = [])
  const next = arr.filter(id => id !== charId)
  row[colId] = next
  updateBlockData(block, { cells })
}

function addRotationColumn(block: RaidPlanBlock) {
  const cols = (block.data?.columns ?? []) as any[]
  const id = 't' + String((cols?.length ?? 0) + 1)
  const next = [...cols, { id, label: `T${cols.length + 1}`, sublabel: '' }]
  updateBlockData(block, { columns: next })
}

function removeRotationColumn(block: RaidPlanBlock, colId: string) {
  const cols = (block.data?.columns ?? []) as any[]
  const nextCols = cols.filter((c: any) => c.id !== colId)

  const cells = ensureCells(block)
  for (const r of Object.keys(cells)) {
    if (cells[r][colId]) delete cells[r][colId]
  }
  updateBlockData(block, { columns: nextCols, cells })
}

function updateColumn(block: RaidPlanBlock, colId: string, patch: Record<string, any>) {
  const cols = (block.data?.columns ?? []) as any[]
  const next = cols.map((c: any) => c.id === colId ? { ...c, ...patch } : c)
  updateBlockData(block, { columns: next })
}

function addRotationRow(block: RaidPlanBlock, label = 'New') {
  const rows = (block.data?.rows ?? []) as any[]
  const id = 'r' + String((rows?.length ?? 0) + 1)
  const next = [...rows, { id, label, type: 'generic', cells: {} }]
  updateBlockData(block, { rows: next })
}

function removeRotationRow(block: RaidPlanBlock, rowId: string) {
  const rows = (block.data?.rows ?? []) as any[]
  const nextRows = rows.filter((r: any) => r.id !== rowId)
  const cells = ensureCells(block)
  delete cells[rowId]
  updateBlockData(block, { rows: nextRows, cells })
}

function updateRow(block: RaidPlanBlock, rowId: string, patch: Record<string, any>) {
  const rows = (block.data?.rows ?? []) as any[]
  const next = rows.map((r: any) => r.id === rowId ? { ...r, ...patch } : r)
  updateBlockData(block, { rows: next })
}

function isPairRow(row: any): boolean {
  const mode = (row?.mode as string) || ''
  if (mode === 'pair') return true
  const t = (row?.type as string) || ''
  const lbl = ((row?.label as string) || '').toLowerCase()
  return t === 'pair' || lbl.includes('infusion') || lbl === 'pi'
}

type PairCell = { from: string | null; to: string | null }
type PairCellMap = Record<string, Record<string, PairCell>>

function ensurePairCells(block: RaidPlanBlock): PairCellMap {
  const cells = (block.data?.cells as any) ?? {}
  if (!block.data) (block as any).data = {}
  if (!block.data!.cells) (block.data as any).cells = {}
  return cells as PairCellMap
}

function getPairCell(block: RaidPlanBlock, rowId: string, colId: string): PairCell {
  const cells = ensurePairCells(block)
  const row = cells[rowId] ?? (cells[rowId] = {})
  const cur = row[colId]
  if (Array.isArray(cur)) {
    // Convert list -> pair ({from,to}) when switching to pair mode
    const from = cur[0] || null
    const to = cur[1] || null
    const pair = { from, to }
    row[colId] = pair as any
    updateBlockData(block, { cells })
    return pair
  }
  const val = row[colId] ?? (row[colId] = { from: null, to: null })
  return val as PairCell
}

function isPriest(charId: string | null): boolean {
  if (!charId) return false
  const c = getCharacterById(charId)
  return (c?.class || '').toLowerCase() === 'priest'
}

function dropToPairCell(block: RaidPlanBlock, rowId: string, colId: string, slot: 'from'|'to', e: DragEvent) {
  const charId = e.dataTransfer?.getData('text/plain') || null
  if (!charId) return
  const rows = (block.data?.rows ?? []) as any[]
  const rowCfg = rows.find((r: any) => r.id === rowId)
  const pair = getPairCell(block, rowId, colId)
  if (slot === 'from') {
    // UX: by default, do not restrict to Priest unless explicitly enabled on the row
    const requirePriest = rowCfg?.requirePriest === true
    if (requirePriest && !isPriest(charId)) return
    pair.from = charId
  } else {
    // Target can be anyone
    pair.to = charId
  }
  const cells = ensurePairCells(block)
  const row = cells[rowId] ?? (cells[rowId] = {})
  row[colId] = pair
  updateBlockData(block, { cells })
}

function clearPair(block: RaidPlanBlock, rowId: string, colId: string, slot: 'from'|'to') {
  const pair = getPairCell(block, rowId, colId)
  pair[slot] = null
  const cells = ensurePairCells(block)
  const row = cells[rowId] ?? (cells[rowId] = {})
  row[colId] = pair
  updateBlockData(block, { cells })
}

function addColumns(block: RaidPlanBlock, labels: string[]) {
  const cols = (block.data?.columns ?? []) as any[]
  const next = [...cols]
  for (const label of labels) {
    const id = 't' + String((next.length || 0) + 1)
    next.push({ id, label, sublabel: '' })
  }
  updateBlockData(block, { columns: next })
}

function addColumnPreset(block: RaidPlanBlock, preset: '30s' | '1m' | '90s') {
  if (preset === '30s') return addColumns(block, ['0:30'])
  if (preset === '1m') return addColumns(block, ['1:00'])
  if (preset === '90s') return addColumns(block, ['1:30'])
}

function addRowPreset(block: RaidPlanBlock, type: 'pi' | 'barrier' | 'rally' | 'amz' | 'slt') {
  const rows = (block.data?.rows ?? []) as any[]
  const id = 'r' + String((rows?.length ?? 0) + 1)
  const label = type === 'pi' ? 'Power Infusion'
    : type === 'barrier' ? 'Barrier'
    : type === 'rally' ? 'Rally'
    : type === 'amz' ? 'AMZ'
    : 'Spirit Link'
  const next = [...rows, { id, label, type }]
  updateBlockData(block, { rows: next })
}

function rowChip(row: any): { text: string, cls: string } | null {
  const t = (row?.type as string) || ''
  const lbl = ((row?.label as string) || '').toLowerCase()
  const is = (needle: string) => t === needle || lbl.includes(needle)
  if (is('infusion') || is('pi')) return { text: 'PI', cls: 'bg-pink-500/15 text-pink-300 ring-pink-400/30' }
  if (is('barrier')) return { text: 'Barrier', cls: 'bg-blue-500/15 text-blue-300 ring-blue-400/30' }
  if (is('rally')) return { text: 'Rally', cls: 'bg-amber-500/15 text-amber-300 ring-amber-400/30' }
  if (is('amz')) return { text: 'AMZ', cls: 'bg-teal-500/15 text-teal-300 ring-teal-400/30' }
  if (is('link') || is('spirit link') || is('slt')) return { text: 'SLT', cls: 'bg-emerald-500/15 text-emerald-300 ring-emerald-400/30' }
  return null
}

function updateChecklistItemLabel(block: RaidPlanBlock, itemId: string, label: string) {
  const items = (block.data?.checklistItems ?? []).map((item) =>
    item.id === itemId ? { ...item, label } : item
  );
  updateBlockData(block, { checklistItems: items });
}

function toggleChecklistItem(block: RaidPlanBlock, itemId: string) {
  const items = (block.data?.checklistItems ?? []).map((item) =>
    item.id === itemId ? { ...item, done: !item.done } : item
  );
  updateBlockData(block, { checklistItems: items });
}

function removeChecklistItem(block: RaidPlanBlock, itemId: string) {
  const items = (block.data?.checklistItems ?? []).filter((item) => item.id !== itemId);
  updateBlockData(block, { checklistItems: items });
}

function getCharacterById(id: string): Character | undefined {
  return props.characters?.find((c) => c.id === id);
}

function charColorById(id: string): string {
  const c = getCharacterById(id);
  return getClassColor(c?.class);
}

function getCharacterClassById(id: string): string | undefined {
  const c = getCharacterById(id);
  return c?.class;
}

function displaySpecFor(block: RaidPlanBlock, charId: string): string | undefined {
  const ov = (block.data?.specOverrides as Record<string, string> | undefined)?.[charId];
  return ov || getCharacterById(charId)?.spec;
}

function specOptionsFor(charId: string): string[] {
  const cls = getCharacterClassById(charId);
  if (!cls) return [];
  const options = getSpecOptions(cls).map(o => o.value as string);
  const c = getCharacterById(charId);
  const pri = c?.spec || '';
  const sec = (c as any)?.specSecondary as string | undefined;
  const order: string[] = [];
  if (sec && options.includes(sec)) order.push(sec);
  if (pri && pri !== sec && options.includes(pri)) order.push(pri);
  const rest = options.filter(v => !order.includes(v));
  return [...order, ...rest];
}

function applySpecOverride(block: RaidPlanBlock, charId: string, spec: string) {
  const overrides = { ...(block.data?.specOverrides || {}) } as Record<string, string>;
  if (spec) overrides[charId] = spec; else delete overrides[charId];

  if (block.type === 'ROLE_MATRIX') {
    const cls = getCharacterClassById(charId);
    const role = cls ? getRoleByClassAndSpec(cls, spec) : undefined;
    if (role) {
      const assignments = (block.data?.roleAssignments ?? {}) as Record<Role, string[]>;
      const newAssignments: Record<Role, string[]> = {
        Tanks: [...(assignments.Tanks ?? [])],
        Healers: [...(assignments.Healers ?? [])],
        Melee: [...(assignments.Melee ?? [])],
        Ranged: [...(assignments.Ranged ?? [])],
      } as any;
      for (const r of Object.keys(newAssignments) as (keyof typeof newAssignments)[]) {
        newAssignments[r] = newAssignments[r].filter((id) => id !== charId);
      }
      if (!newAssignments[role].includes(charId)) newAssignments[role].push(charId);
      updateBlockData(block, { roleAssignments: newAssignments, specOverrides: overrides });
      return;
    }
  }

  updateBlockData(block, { specOverrides: overrides });
}

function hasSpecOverride(block: RaidPlanBlock, charId: string): boolean {
  const overrides = (block.data?.specOverrides as Record<string, string> | undefined) || {};
  return !!overrides[charId];
}

function isSpecCompatibleWithRole(cls: string | undefined, spec: string | undefined, role: Role): boolean {
  if (!cls || !spec) return false;
  try { return (getRoleByClassAndSpec(cls, spec) as Role) === role } catch { return false }
}

function pickSpecForRole(charId: string, role: Role): string | undefined {
  const c = getCharacterById(charId);
  if (!c) return undefined;
  const cls = c.class;
  const options = getSpecOptions(cls || '').map(o => o.value as string);
  const pri = c.spec;
  const sec = (c as any)?.specSecondary as string | undefined;
  if (sec && isSpecCompatibleWithRole(cls, sec, role)) return sec;
  if (pri && isSpecCompatibleWithRole(cls, pri, role)) return pri;
  const first = options.find(sp => isSpecCompatibleWithRole(cls, sp, role));
  return first || pri || options[0];
}

function roleCountInGroup(block: RaidPlanBlock, memberIds: string[], role: Role): number {
  let n = 0;
  for (const id of memberIds) {
    const c = getCharacterById(id);
    if (!c) continue;
    const sp = displaySpecFor(block, id) || c.spec;
    const derived = (sp && c.class) ? (getRoleByClassAndSpec(c.class, sp) as Role | undefined) : undefined;
    const finalRole = derived || (c.role as Role | undefined);
    if (finalRole === role) n++;
  }
  return n;
}

function derivedRoleFor(block: RaidPlanBlock, charId: string): Role | undefined {
  const c = getCharacterById(charId);
  if (!c) return undefined;
  const sp = displaySpecFor(block, charId) || c.spec;
  const derived = (sp && c.class) ? (getRoleByClassAndSpec(c.class, sp) as Role | undefined) : undefined;
  return derived || (c.role as Role | undefined);
}

function roleLabel(role: Role): string {
  return role === 'Tanks' ? 'T' : role === 'Healers' ? 'H' : role === 'Melee' ? 'M' : 'R';
}

function onDropToRole(block: RaidPlanBlock, role: Role, e: DragEvent) {
  const charId = e.dataTransfer?.getData('text/plain');
  if (!charId) return;
  const assignments = (block.data?.roleAssignments ?? {}) as Record<Role, string[]>;
  const newAssignments: Record<Role, string[]> = {
    Tanks: [...(assignments.Tanks ?? [])],
    Healers: [...(assignments.Healers ?? [])],
    Melee: [...(assignments.Melee ?? [])],
    Ranged: [...(assignments.Ranged ?? [])],
  } as any;
  for (const r of Object.keys(newAssignments) as (keyof typeof newAssignments)[]) {
    newAssignments[r] = newAssignments[r].filter((id) => id !== charId);
  }
  if (!newAssignments[role].includes(charId)) newAssignments[role].push(charId);
  updateBlockData(block, { roleAssignments: newAssignments });
  hoveredRoleKey.value = null;

  // Ensure a compatible spec is selected by default for this role
  const chosen = pickSpecForRole(charId, role);
  if (chosen) applySpecOverride(block, charId, chosen);

  // If character was benched, unbench automatically for UX
  removeFromAllBenches(charId);
}

function removeFromRole(block: RaidPlanBlock, role: Role, charId: string) {
  const assignments = (block.data?.roleAssignments ?? {}) as Record<Role, string[]>;
  const newAssignments: Record<Role, string[]> = {
    Tanks: [...(assignments.Tanks ?? [])],
    Healers: [...(assignments.Healers ?? [])],
    Melee: [...(assignments.Melee ?? [])],
    Ranged: [...(assignments.Ranged ?? [])],
  } as any;
  newAssignments[role] = newAssignments[role].filter((id) => id !== charId);
  updateBlockData(block, { roleAssignments: newAssignments });
}

function onDropToGroup(block: RaidPlanBlock, groupId: string, e: DragEvent) {
  const charId = e.dataTransfer?.getData('text/plain');
  if (!charId) return;
  const groups = (block.data?.groups ?? []) as { id: string; title: string; members: string[] }[];
  const cloned = groups.map((g) => ({ ...g, members: [...g.members] }));
  for (const g of cloned) {
    g.members = g.members.filter((id) => id !== charId);
  }
  const target = cloned.find((g) => g.id === groupId);
  const max = (block.data?.playersPerGroup ?? 5) as number;
  if (target && !target.members.includes(charId) && target.members.length < max) {
    target.members.push(charId);
  }
  const overrides = { ...(block.data?.specOverrides || {}) } as Record<string, string>;
  const currentSpec = getCharacterById(charId)?.spec;
  if (currentSpec && !overrides[charId]) overrides[charId] = currentSpec;
  updateBlockData(block, { groups: cloned, specOverrides: overrides });
  hoveredGroupKey.value = null;

  removeFromAllBenches(charId);
}

function removeFromGroup(block: RaidPlanBlock, groupId: string, charId: string) {
  const groups = (block.data?.groups ?? []) as { id: string; title: string; members: string[] }[];
  const cloned = groups.map((g) => ({ ...g, members: g.members.filter((id) => id !== charId) }));
  updateBlockData(block, { groups: cloned });
}

function stripCharacterFromBlock(b: RaidPlanBlock, charId: string): RaidPlanBlock | null {
  let changed = false;
  const next = { ...b, data: { ...(b.data || {}) } } as RaidPlanBlock;
  if (b.type === 'ROLE_MATRIX') {
    const ra = (b.data?.roleAssignments ?? {}) as Record<Role, string[]>;
    const cloned: Record<Role, string[]> = {
      Tanks: [...(ra.Tanks ?? [])],
      Healers: [...(ra.Healers ?? [])],
      Melee: [...(ra.Melee ?? [])],
      Ranged: [...(ra.Ranged ?? [])],
    } as any;
    (Object.keys(cloned) as (keyof typeof cloned)[]).forEach((r) => {
      const before = cloned[r].length; cloned[r] = cloned[r].filter((id) => id !== charId); if (cloned[r].length !== before) changed = true;
    });
    if (changed) (next.data as any).roleAssignments = cloned;
  }
  if (b.type === 'GROUPS_GRID') {
    const groups = (b.data?.groups ?? []) as { id: string; title: string; members: string[] }[];
    const cloned = groups.map((g) => ({ ...g, members: g.members.filter((id) => id !== charId) }));
    if (JSON.stringify(groups) !== JSON.stringify(cloned)) { (next.data as any).groups = cloned; changed = true; }
  }
  if (b.type === 'BOSS_GRID') {
    const assigns = (b.data?.assignments ?? {}) as Record<string, string[]>;
    const cloned: Record<string, string[]> = {};
    for (const k of Object.keys(assigns)) { const arr = (assigns[k] ?? []).filter(id => id !== charId); if (arr.length !== (assigns[k] ?? []).length) changed = true; cloned[k] = arr; }
    if (changed) (next.data as any).assignments = cloned;
  }
  if (b.type === 'COOLDOWN_ROTATION' || b.type === 'INTERRUPT_ROTATION') {
    const rows = ((b.data?.rows ?? []) as any[]).map((r) => ({ ...r, cells: { ...(r.cells || {}) } }));
    let local = false;
    for (const r of rows) {
      for (const key of Object.keys(r.cells || {})) {
        if (r.cells[key] === charId) { r.cells[key] = null; local = true; }
      }
    }
    if (local) { (next.data as any).rows = rows; changed = true; }
  }
  return changed ? next : null;
}

function removeCharacterFromAllAssignments(charId: string) {
  for (const b of innerBlocks.value) {
    const stripped = stripCharacterFromBlock(b, charId);
    if (stripped) emit('update-block', stripped);
  }
}

function removeFromAllBenches(charId: string) {
  for (const b of innerBlocks.value) {
    if (b.type !== 'BENCH_ROSTER') continue;
    const bench = new Set<string>(((b.data?.bench ?? []) as string[]));
    if (bench.delete(charId)) {
      emit('update-block', { ...b, data: { ...(b.data || {}), bench: Array.from(bench) } });
    }
  }
}

function updateGroupsConfig(block: RaidPlanBlock, groupCount: number, playersPerGroup: number) {
  const count = Math.max(1, Math.min(groupCount || 1, 12));
  const size = Math.max(1, Math.min(playersPerGroup || 1, 8));
  const current = (block.data?.groups ?? []) as { id: string; title: string; members: string[] }[];
  const groups = current.map((g) => ({ ...g, members: [...g.members] }));

  if (count > groups.length) {
    const toAdd = count - groups.length;
    const start = groups.length;
    for (let i = 0; i < toAdd; i++) {
      const idx = start + i + 1;
      groups.push({ id: String(idx), title: `Group ${idx}`, members: [] });
    }
  } else if (count < groups.length) {
    groups.splice(count); // trim extras
  }

  updateBlockData(block, { groups, groupCount: count, playersPerGroup: size });
}

function canDropToPosition(_block: RaidPlanBlock, _positionId: string, charId: string | null): boolean {
  // UX request: positions accept everything (no restrictions for now)
  return !!charId;
}

function onDropToPosition(block: RaidPlanBlock, positionId: string, e: DragEvent) {
  const charId = e.dataTransfer?.getData('text/plain') || null;
  if (!charId) return;
  if (!canDropToPosition(block, positionId, charId)) return;

  const assignments = { ...(block.data?.assignments ?? {}) } as Record<string, string[]>;

  for (const key of Object.keys(assignments)) {
    assignments[key] = (assignments[key] ?? []).filter((id) => id !== charId);
  }

  const list = assignments[positionId] ?? [];
  if (!list.includes(charId)) list.push(charId);
  assignments[positionId] = list;
  updateBlockData(block, { assignments });
  hoveredPositionId.value = null;

  removeFromAllBenches(charId);
}

function removeFromPosition(block: RaidPlanBlock, positionId: string, charId: string) {
  const assignments = { ...(block.data?.assignments ?? {}) } as Record<string, string[]>;
  assignments[positionId] = (assignments[positionId] ?? []).filter((id) => id !== charId);
  updateBlockData(block, { assignments });
}

function addPosition(block: RaidPlanBlock) {
  const positions = ((block.data?.positions ?? []) as any[]).slice();
  const newId = 'pos' + (positions.length + 1);
  positions.push({ id: newId, label: `Position ${positions.length + 1}`, accepts: 'ANY' });
  updateBlockData(block, { positions });
}

function updatePositionLabel(block: RaidPlanBlock, positionId: string, label: string) {
  const positions = ((block.data?.positions ?? []) as any[]).map((p) => (p.id === positionId ? { ...p, label } : p));
  updateBlockData(block, { positions });
}

function removePosition(block: RaidPlanBlock, positionId: string) {
  const positions = ((block.data?.positions ?? []) as any[]).filter((p) => p.id !== positionId);
  const assignments = { ...(block.data?.assignments ?? {}) } as Record<string, string[]>;
  delete assignments[positionId];
  const notes = { ...(block.data?.positionNotes ?? {}) } as Record<string, string[]>;
  delete notes[positionId];
  updateBlockData(block, { positions, assignments, positionNotes: notes });
}

function addPositionNote(block: RaidPlanBlock, positionId: string) {
  const notes = { ...(block.data?.positionNotes ?? {}) } as Record<string, string[]>;
  const list = notes[positionId] ? [...notes[positionId]] : [];
  list.push('Note');
  notes[positionId] = list;
  updateBlockData(block, { positionNotes: notes });
}

function updatePositionNote(block: RaidPlanBlock, positionId: string, index: number, text: string) {
  const notes = { ...(block.data?.positionNotes ?? {}) } as Record<string, string[]>;
  const list = notes[positionId] ? [...notes[positionId]] : [];
  list[index] = text;
  notes[positionId] = list;
  updateBlockData(block, { positionNotes: notes });
}

function removePositionNote(block: RaidPlanBlock, positionId: string, index: number) {
  const notes = { ...(block.data?.positionNotes ?? {}) } as Record<string, string[]>;
  const list = (notes[positionId] ?? []).slice();
  list.splice(index, 1);
  notes[positionId] = list;
  updateBlockData(block, { positionNotes: notes });
}

function assignToSelected(characterId: string) {
  const block = innerBlocks.value.find(b => b.id === selectedBlockId.value);
  if (!block) return false;

  if (block.type === 'GROUPS_GRID') {
    const groups = (block.data?.groups ?? []) as { id: string; title: string; members: string[] }[];
    const max = (block.data?.playersPerGroup ?? 5) as number;
    let target = groups.slice().sort((a,b) => a.members.length - b.members.length).find(g => g.members.length < max);
    if (!target) return false;
    onDropToGroup(block, target.id, { dataTransfer: { getData: () => characterId } } as any as DragEvent);
    // Ensure default override set to character's spec for clarity
    const spec = getCharacterById(characterId)?.spec;
    if (spec) {
      const overrides = { ...(block.data?.specOverrides || {}) } as Record<string, string>;
      overrides[characterId] = overrides[characterId] || spec;
      updateBlockData(block, { specOverrides: overrides });
    }
    return true;
  }

  if (block.type === 'ROLE_MATRIX') {
    const r = derivedRoleFor(block, characterId);
    if (!r) return false;
    onDropToRole(block, r, { dataTransfer: { getData: () => characterId } } as any as DragEvent);
    // pick a compatible spec for that role
    const chosen = pickSpecForRole(characterId, r);
    if (chosen) applySpecOverride(block, characterId, chosen);
    return true;
  }

  if (block.type === 'COOLDOWN_ROTATION') {
    const rows = ((block.data?.rows ?? []) as any[]).map((r: any) => ({ ...r, cells: { ...(r.cells || {}) } }));
    const cols = (block.data?.columns ?? []) as any[];

    for (const r of rows) { for (const k of Object.keys(r.cells || {})) { if (r.cells[k] === characterId) r.cells[k] = null; } }
    outer: for (const r of rows) {
      for (const c of cols) {
        if (!r.cells[c.id]) { r.cells[c.id] = characterId; break outer; }
      }
    }
    updateBlockData(block, { rows });
    return true;
  }

  if (block.type === 'INTERRUPT_ROTATION') {
    const rows = ((block.data?.rows ?? []) as any[]).map((r: any) => ({ ...r, cells: { ...(r.cells || {}) } }));
    const cols = (block.data?.columns ?? []) as any[];
    for (const r of rows) { for (const k of Object.keys(r.cells || {})) { if (r.cells[k] === characterId) r.cells[k] = null; } }
    outer2: for (const r of rows) { for (const c of cols) { if (!r.cells[c.id]) { r.cells[c.id] = characterId; break outer2; } } }
    updateBlockData(block, { rows });
    return true;
  }

  if (block.type === 'CUSTOM_SECTION') {
    const rows = ((block.data?.rows ?? []) as any[]).map((r: any) => ({ ...r, data: { ...(r.data || {}) } }));
    const cols = (block.data?.columns ?? []) as any[];
    const assigneeCols = cols.filter(c => (c.type || 'text') === 'assignee');

    for (const r of rows) { for (const key of Object.keys(r.data || {})) { if (r.data[key] === characterId) r.data[key] = null; } }
    for (const r of rows) {
      for (const c of assigneeCols) {
        if (!r.data[c.id]) { r.data[c.id] = characterId; updateBlockData(block, { rows }); return true; }
      }
    }
  }

  if (block.type === 'BENCH_ROSTER') {
    const bench = new Set<string>((block.data?.bench ?? []) as string[]);
    bench.add(characterId);
    updateBlockData(block, { bench: Array.from(bench) });
    // When benching a character, remove them from any other assignments across the plan
    removeCharacterFromAllAssignments(characterId);
    return true;
  }
  return false;
}

defineExpose({ assignToSelected, selectedBlockId });

</script>

<template>
  <div class="h-full rounded-xl bg-slate-900/40 p-4 relative" @click.self="selectedBlockId = null">

    
    <div
      v-if="collisions.size > 0"
      class="absolute top-4 left-1/2 -translate-x-1/2 z-50 px-4 py-2 bg-red-500/90 backdrop-blur text-white rounded-lg shadow-xl border border-red-400 flex items-center gap-2 text-sm font-medium animate-pulse"
    >
      <span class="text-lg">⚠️</span>
      <span>{{ collisions.size }} block{{ collisions.size > 1 ? 's' : '' }} overlapping! Check manual row positions.</span>
    </div>

    
    <div
      v-if="showGridOverlay && innerBlocks.length"
      class="absolute inset-4 pointer-events-none z-0 grid grid-cols-12 gap-3 transition-opacity duration-300"
      :class="resizing || selectedBlockId ? 'opacity-100' : 'opacity-0'"
    >
      <div v-for="i in 12" :key="i" class="border-l border-r border-dashed border-emerald-500/20 relative">
        <span class="absolute top-0 left-1/2 -translate-x-1/2 text-[10px] text-emerald-400/60 font-mono bg-slate-900/80 px-1 rounded">{{ i }}</span>
      </div>
    </div>

    
    <div v-if="!innerBlocks.length" class="flex flex-col items-center justify-center h-full text-center px-8">
      <div class="max-w-md space-y-4">
        <div class="text-6xl">📋</div>
        <h2 class="text-2xl font-bold text-slate-200">Start Building Your Raid Plan</h2>
        <p class="text-slate-400 text-sm leading-relaxed">
          Add blocks from the left sidebar to create your custom raid layout.
          Each block can be resized, repositioned, and filled with character assignments.
        </p>
        <div class="space-y-2 text-left bg-slate-950/60 rounded-lg p-4 border border-slate-800">
          <div class="text-xs font-semibold text-emerald-400 uppercase tracking-wide mb-2">Quick Start</div>
          <div class="flex items-start gap-2 text-sm text-slate-300">
            <span class="text-emerald-500">1.</span>
            <span>Click <strong>"Raid Groups"</strong> to add a groups grid</span>
          </div>
          <div class="flex items-start gap-2 text-sm text-slate-300">
            <span class="text-emerald-500">2.</span>
            <span>Use <strong>Classic 40 / Mythic 20</strong> presets for instant setup</span>
          </div>
          <div class="flex items-start gap-2 text-sm text-slate-300">
            <span class="text-emerald-500">3.</span>
            <span>Drag characters from the right sidebar into groups</span>
          </div>
        </div>
      </div>
    </div>

    
    <Draggable
      v-else
      v-model="innerBlocks"
      item-key="id"
      handle=".drag-handle"
      ghost-class="opacity-60"
      @change="onDragChange"
      tag="div"
      class="planner-grid grid auto-rows-min gap-3 relative z-10"
      :style="{ gridTemplateColumns: 'repeat(12, minmax(0, 1fr))' }"
    >
        <template #item="{ element: block }">
          <article
            class="relative rounded-lg bg-slate-950/70 p-3 cursor-pointer transition-all duration-200 shadow-sm border group"
            :class="[
          collisions.has(block.id)
            ? 'ring-2 ring-red-500 shadow-red-500/20 shadow-xl border-red-500/50 animate-pulse'
            : selectedBlockId === block.id
            ? 'ring-2 ring-emerald-500 shadow-emerald-500/20 shadow-xl border-emerald-500/50 scale-[1.01]'
            : 'hover:ring-1 hover:ring-slate-600 hover:shadow-md border-slate-800'
        ]"
            :style="{
          gridColumn: block.colSpan
            ? block.colStart + ' / span ' + block.colSpan
            : '1 / span 12',
          gridRow: (block as any).row && (block as any).row > 0 ? String((block as any).row) : 'auto'
        }"
            @click="selectBlock(block.id)"
          >
            
            <div
              v-if="collisions.has(block.id)"
              class="absolute -top-2 -right-2 px-2 py-1 bg-red-500 text-white text-[10px] font-bold rounded-full shadow-lg z-20 flex items-center gap-1"
              title="Block overlaps with another block on the same row!"
            >
              ⚠️ Collision
            </div>
            
            <div
              v-if="selectedBlockId === block.id && block.colStart > 1"
              class="absolute left-0 top-0 bottom-0 w-2 cursor-ew-resize hover:bg-emerald-500/30 transition-colors flex items-center justify-center group/handle"
              @mousedown="onResizeLeft(block, $event)"
              title="Resize left edge"
            >
              <div class="w-1 h-8 bg-emerald-500/60 rounded-full opacity-0 group-hover/handle:opacity-100 transition-opacity"></div>
            </div>
            <div
              v-if="selectedBlockId === block.id && block.colStart + block.colSpan - 1 < 12"
              class="absolute right-0 top-0 bottom-0 w-2 cursor-ew-resize hover:bg-emerald-500/30 transition-colors flex items-center justify-center group/handle"
              @mousedown="onResizeRight(block, $event)"
              title="Resize right edge"
            >
              <div class="w-1 h-8 bg-emerald-500/60 rounded-full opacity-0 group-hover/handle:opacity-100 transition-opacity"></div>
            </div>
            
            <header class="flex items-center justify-between mb-2">
              <div class="flex items-center gap-2 min-w-0 flex-1">
                <span v-if="selectedBlockId !== block.id" class="text-sm font-semibold text-slate-200 truncate">{{ block.title || block.type }}</span>
                <input v-else
                       type="text"
                       class="bg-slate-900 border border-slate-700 rounded px-2 py-0.5 text-xs focus:outline-none focus:ring-1 focus:ring-emerald-500 flex-1 min-w-0"
                       :value="block.title || ''"
                       @input="emit('update-block', { ...block, title: ($event.target as HTMLInputElement).value })"
                       @click.stop
                       placeholder="Block title" />
                <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-800 border border-slate-700 text-slate-400 flex-shrink-0">{{ block.type }}</span>
              </div>
              <div class="flex items-center gap-1">
                <button
                  v-if="selectedBlockId === block.id"
                  class="rounded p-1 text-[10px] text-amber-300 hover:bg-amber-900/30 border border-amber-700/50"
                  title="Reset overrides for this block"
                  @click.stop="updateBlockData(block, { specOverrides: {} })"
                >
                  ⟲ Reset overrides
                </button>
                
                <button
                  class="drag-handle rounded p-1 text-xs text-slate-400 hover:bg-slate-800 cursor-grab"
                  title="Drag to reorder"
                  @click.stop
                >
                  ☰
                </button>
                
                <button
                  class="rounded p-1 text-xs text-red-400 hover:bg-red-900/40"
                  title="Remove block"
                  @click.stop="remove(block.id)"
                >
                  ✕
                </button>
              </div>
            </header>

            
            <div
              v-if="selectedBlockId === block.id"
              class="mb-2 flex flex-wrap items-center gap-3 text-[11px] text-slate-300"
            >
              <div class="flex items-center gap-1">
                <span class="uppercase tracking-wide text-slate-500">Start</span>
                <input
                  type="number"
                  min="1"
                  max="12"
                  :value="block.colStart"
                  @input="onStartInput(block, $event)"
                  class="w-16 bg-slate-900 border border-slate-700 rounded px-1 py-0.5 text-xs focus:outline-none focus:ring-1 focus:ring-emerald-500"
                />
                <div class="inline-flex rounded-md border border-slate-700 overflow-hidden">
                  <button
                    v-for="s in allowedStarts"
                    :key="s"
                    class="px-1.5 py-0.5 text-[10px] border-r border-slate-700 last:border-r-0 hover:bg-slate-800"
                    :class="block.colStart === s ? 'bg-emerald-600 text-slate-950' : 'text-slate-300'"
                    @click.stop="setBlockStart(block, s)"
                  >
                    {{ s }}
                  </button>
                </div>
              </div>

              <div class="flex items-center gap-1">
                <span class="uppercase tracking-wide text-slate-500">Span</span>
                <input
                  type="number"
                  min="1"
                  max="12"
                  :value="block.colSpan"
                  @input="onSpanInput(block, $event)"
                  class="w-16 bg-slate-900 border border-slate-700 rounded px-1 py-0.5 text-xs focus:outline-none focus:ring-1 focus:ring-emerald-500"
                />

                <div class="inline-flex rounded-md border border-slate-700 overflow-hidden">
                  <button
                    v-for="span in allowedSpans"
                    :key="span"
                    class="px-1.5 py-0.5 text-[10px] border-r border-slate-700 last:border-r-0 hover:bg-slate-800"
                    :class="block.colSpan === span ? 'bg-emerald-600 text-slate-950' : 'text-slate-300'"
                    @click.stop="setBlockSpan(block, span)"
                  >
                    {{ span }}
                  </button>
                </div>
              </div>

              <div class="flex items-center gap-1">
                <span class="uppercase tracking-wide text-slate-500">Row</span>
                <input
                  type="number"
                  min="0"
                  :value="(block as any).row ?? 0"
                  @input="emit('update-block', { ...block, row: Math.max(0, Number(($event.target as HTMLInputElement).value || 0)) })"
                  class="w-16 bg-slate-900 border border-slate-700 rounded px-1 py-0.5 text-xs focus:outline-none focus:ring-1 focus:ring-emerald-500"
                />
                <button class="px-1.5 py-0.5 text-[10px] rounded-md border border-slate-700 hover:bg-slate-800"
                        @click.stop="emit('update-block', { ...block, row: 0 })">
                  Auto
                </button>
              </div>
              
              <div v-if="block.type === 'GROUPS_GRID'" class="flex items-center gap-2">
                <span class="uppercase tracking-wide text-slate-500">Groups</span>
                <input type="number" min="1" max="12" :value="block.data?.groupCount ?? 8"
                       class="w-16 bg-slate-900 border border-slate-700 rounded px-1 py-0.5 text-xs focus:outline-none focus:ring-1 focus:ring-emerald-500"
                       @change="(e: Event) => updateGroupsConfig(block, Number((e.target as HTMLInputElement).value), block.data?.playersPerGroup ?? 5)"/>
                <span class="uppercase tracking-wide text-slate-500">Size</span>
                <input type="number" min="1" max="8" :value="block.data?.playersPerGroup ?? 5"
                       class="w-16 bg-slate-900 border border-slate-700 rounded px-1 py-0.5 text-xs focus:outline-none focus:ring-1 focus:ring-emerald-500"
                       @change="(e: Event) => updateGroupsConfig(block, block.data?.groupCount ?? 8, Number((e.target as HTMLInputElement).value))"/>
              </div>
            </div>

            
            <div class="mt-1 text-xs text-slate-300 space-y-2">
              
              <div v-if="block.type === 'IMAGE'" class="space-y-2">
                <div>
                  <label class="block text-[11px] font-semibold text-slate-400 mb-1">Image URL</label>
                  <input
                    type="text"
                    class="w-full bg-slate-900 border border-slate-700 rounded-md px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-emerald-500"
                    :value="block.data?.imageUrl ?? ''"
                    @input="updateBlockData(block, { imageUrl: ($event.target as HTMLInputElement).value })"
                    placeholder="https://example.com/image.png or paste image URL"
                  />
                  <p class="text-[10px] text-slate-500 mt-1">Paste an image URL or upload to imgur.com and paste the link</p>
                </div>

                <div>
                  <label class="block text-[11px] font-semibold text-slate-400 mb-1">Caption (optional)</label>
                  <input
                    type="text"
                    class="w-full bg-slate-900 border border-slate-700 rounded-md px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-emerald-500"
                    :value="block.data?.imageCaption ?? ''"
                    @input="updateBlockData(block, { imageCaption: ($event.target as HTMLInputElement).value })"
                    placeholder="e.g., Boss positioning for Phase 2"
                  />
                </div>

                <div>
                  <label class="block text-[11px] font-semibold text-slate-400 mb-1">Display Mode</label>
                  <select
                    class="w-full bg-slate-900 border border-slate-700 rounded-md px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-emerald-500"
                    :value="block.data?.imageSize ?? 'contain'"
                    @change="updateBlockData(block, { imageSize: ($event.target as HTMLSelectElement).value })"
                  >
                    <option value="contain">Fit (contain)</option>
                    <option value="cover">Fill (cover)</option>
                    <option value="fill">Stretch (fill)</option>
                  </select>
                </div>

                
                <div v-if="block.data?.imageUrl" class="border-t border-slate-800 pt-2">
                  <div class="text-[10px] text-slate-400 mb-1">Preview</div>
                  <div class="relative w-full h-48 bg-slate-950 rounded-md overflow-hidden border border-slate-700">
                    <img
                      :src="block.data.imageUrl"
                      :alt="block.data?.imageCaption || 'Raid image'"
                      class="w-full h-full"
                      :style="{ objectFit: block.data?.imageSize || 'contain' }"
                      @error="($event.target as HTMLImageElement).src = 'data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22100%22 height=%22100%22%3E%3Crect fill=%22%23334155%22 width=%22100%22 height=%22100%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 fill=%22%23cbd5e1%22 font-size=%2212%22%3EImage not found%3C/text%3E%3C/svg%3E'"
                    />
                  </div>
                  <div v-if="block.data?.imageCaption" class="text-[11px] text-slate-400 text-center mt-1 italic">
                    {{ block.data.imageCaption }}
                  </div>
                </div>
                <div v-else class="border border-dashed border-slate-600 rounded-md p-6 text-center text-slate-500 text-xs">
                  <div class="text-2xl mb-2">🖼️</div>
                  <div>Paste an image URL above to display</div>
                </div>
              </div>

              
              <div v-else-if="block.type === 'TEXT'">
                <label class="block text-[11px] font-semibold text-slate-400 mb-1">
                  Notes
                </label>
                <textarea
                  class="w-full min-h-[80px] resize-y bg-slate-900 border border-slate-700 rounded-md px-2 py-1 text-xs
             focus:outline-none focus:ring-1 focus:ring-emerald-500"
                  :value="block.data?.textContent ?? ''"
                  @input="updateBlockData(block, { textContent: ($event.target as HTMLTextAreaElement).value })"
                  placeholder="Write your strategy notes, assignments, etc..."
                />
              </div>

              
              <div v-else-if="block.type === 'ROLE_MATRIX'" class="space-y-2">
                <div v-if="selectedBlockId === block.id" class="text-[11px] text-slate-400 px-1">
                  Choisissez la spécialisation par joueur pour ce boss. Le rôle s'ajuste automatiquement.
                </div>
                <div class="flex items-center gap-2 text-[10px] text-slate-400">
                  <span class="px-1.5 py-0.5 rounded border" :style="{ borderColor: (ROLE_COLORS as any)['Tanks'] + '66', backgroundColor: (ROLE_COLORS as any)['Tanks'] + '22' }">🛡️ T</span>
                  <span class="px-1.5 py-0.5 rounded border" :style="{ borderColor: (ROLE_COLORS as any)['Healers'] + '66', backgroundColor: (ROLE_COLORS as any)['Healers'] + '22' }">✚ H</span>
                  <span class="px-1.5 py-0.5 rounded border" :style="{ borderColor: (ROLE_COLORS as any)['Melee'] + '66', backgroundColor: (ROLE_COLORS as any)['Melee'] + '22' }">⚔️ M</span>
                  <span class="px-1.5 py-0.5 rounded border" :style="{ borderColor: (ROLE_COLORS as any)['Ranged'] + '66', backgroundColor: (ROLE_COLORS as any)['Ranged'] + '22' }">🏹 R</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                  <div
                    v-for="role in ['Tanks','Healers','Melee','Ranged'] as Role[]"
                    :key="role"
                    class="rounded-md border border-slate-700 bg-slate-900/60 p-2"
                  >
                    <div class="mb-1 text-[11px] font-semibold px-2 py-1 rounded"
                         :style="{ backgroundColor: (ROLE_COLORS as any)[role] + '22', border: '1px solid ' + (ROLE_COLORS as any)[role] + '55' }">
                      {{ role }}
                    </div>
                    <div
                      class="min-h-16 rounded bg-slate-900/40 p-1 space-y-1 transition-all duration-150"
                      :class="hoveredRoleKey === (block.id + ':' + role) ? 'ring-2 ring-emerald-500/80 bg-emerald-500/10 shadow-lg' : 'hover:bg-slate-900/60'"
                      @dragover.prevent
                      @dragenter.prevent="hoveredRoleKey = block.id + ':' + role"
                      @dragleave.prevent="hoveredRoleKey = null"
                      @drop.prevent="onDropToRole(block, role, $event)"
                    >
                      <div
                        v-for="cid in (block.data?.roleAssignments?.[role] ?? [])"
                        :key="cid"
                        class="flex items-center justify-between rounded px-2 py-1"
                        :style="{
                          backgroundColor: charColorById(cid) + '22',
                          border: '1px solid ' + charColorById(cid) + '55'
                        }"
                      >
                        <div class="truncate font-medium" :style="{ color: charColorById(cid) }">
                          <span class="truncate">{{ getCharacterById(cid)?.name ?? 'Unknown' }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                          <select
                            class="text-[10px] bg-slate-900/60 border border-slate-700 rounded px-1 py-0.5 text-slate-300"
                            :value="displaySpecFor(block, cid) || ''"
                            @change="applySpecOverride(block, cid, ($event.target as HTMLSelectElement).value)"
                            title="Spécialisation"
                          >
                            <option value="">—</option>
                            <option v-for="sp in specOptionsFor(cid)" :key="sp" :value="sp">{{ sp }}</option>
                          </select>
                          <button class="text-red-400 hover:text-red-300" @click.stop="removeFromRole(block, role, cid)" title="Remove">✕</button>
                        </div>
                      </div>
                      <div v-if="!(block.data?.roleAssignments?.[role]?.length)" class="text-[11px] text-slate-500 text-center py-1">
                        Drag characters here
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              
              <div v-else-if="block.type === 'GROUPS_GRID'" class="space-y-2">
                <div v-if="selectedBlockId === block.id" class="text-[11px] text-slate-400 px-1">
                  Choisissez la spécialisation affichée pour chaque joueur. Le rôle se met à jour.
                </div>
                <div class="flex items-center gap-2 text-[10px] text-slate-400">
                  <span class="px-1.5 py-0.5 rounded border" :style="{ borderColor: (ROLE_COLORS as any)['Tanks'] + '66', backgroundColor: (ROLE_COLORS as any)['Tanks'] + '22' }">🛡️ T</span>
                  <span class="px-1.5 py-0.5 rounded border" :style="{ borderColor: (ROLE_COLORS as any)['Healers'] + '66', backgroundColor: (ROLE_COLORS as any)['Healers'] + '22' }">✚ H</span>
                  <span class="px-1.5 py-0.5 rounded border" :style="{ borderColor: (ROLE_COLORS as any)['Melee'] + '66', backgroundColor: (ROLE_COLORS as any)['Melee'] + '22' }">⚔️ M</span>
                  <span class="px-1.5 py-0.5 rounded border" :style="{ borderColor: (ROLE_COLORS as any)['Ranged'] + '66', backgroundColor: (ROLE_COLORS as any)['Ranged'] + '22' }">🏹 R</span>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-2">
                  <div
                    v-for="g in (block.data?.groups ?? [])"
                    :key="g.id"
                    class="rounded-md border border-slate-700 bg-slate-900/60 p-2"
                  >
                    <div class="mb-1 flex items-center justify-between text-[11px] font-semibold text-slate-300">
                      <span>{{ g.title }}</span>
                      <span :class="[
                        (g.members.length > (block.data?.playersPerGroup ?? 5)) ? 'text-red-400' :
                        (g.members.length === (block.data?.playersPerGroup ?? 5)) ? 'text-amber-400' : 'text-slate-500'
                      ]">{{ g.members.length }}/{{ block.data?.playersPerGroup ?? 5 }}</span>
                    </div>
                    <div v-if="props.groupTargets" class="text-[10px] text-slate-400 mb-1">
                      Target: T ≤{{ props.groupTargets.tank }} • H ≤{{ props.groupTargets.healer }}
                    </div>
                    <div class="mb-1 flex flex-wrap gap-1 text-[10px]">
                      <span v-for="role in (['Tanks','Healers','Melee','Ranged'] as Role[])" :key="role"
                            class="px-1.5 py-0.5 rounded border"
                            :style="{ borderColor: (ROLE_COLORS as any)[role] + '66', backgroundColor: (ROLE_COLORS as any)[role] + '22' }">
                        {{ roleLabel(role) }} {{ roleCountInGroup(block, g.members, role) }}
                      </span>
                    </div>
                    <div
                      class="min-h-20 rounded bg-slate-900/40 p-1 space-y-1 transition-all duration-150"
                      :class="hoveredGroupKey === (block.id + ':' + g.id) ? 'ring-2 ring-emerald-500/80 bg-emerald-500/10 shadow-lg' : 'hover:bg-slate-900/60'"
                      @dragover.prevent
                      @dragenter.prevent="hoveredGroupKey = block.id + ':' + g.id"
                      @dragleave.prevent="hoveredGroupKey = null"
                      @drop.prevent="onDropToGroup(block, g.id, $event)"
                    >
                      <div
                        v-for="cid in g.members"
                        :key="cid"
                        class="flex items-center justify-between rounded px-2 py-1"
                        :style="{
                          backgroundColor: charColorById(cid) + '22',
                          border: '1px solid ' + charColorById(cid) + '55'
                        }"
                      >
                        <div class="truncate font-medium" :style="{ color: charColorById(cid) }">
                          <span class="truncate">{{ getCharacterById(cid)?.name ?? 'Unknown' }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                          <select
                            class="text-[10px] bg-slate-900/60 border border-slate-700 rounded px-1 py-0.5 text-slate-300"
                            :value="displaySpecFor(block, cid) || ''"
                            @change="applySpecOverride(block, cid, ($event.target as HTMLSelectElement).value)"
                            title="Spécialisation"
                          >
                            <option value="">—</option>
                            <option v-for="sp in specOptionsFor(cid)" :key="sp" :value="sp">{{ sp }}</option>
                          </select>
                          <button class="text-red-400 hover:text-red-300" @click.stop="removeFromGroup(block, g.id, cid)" title="Remove">✕</button>
                        </div>
                      </div>
                      <div v-if="!g.members.length" class="text-[11px] text-slate-500 text-center py-1">Drag characters here</div>
                    </div>
                  </div>
                </div>
              </div>

              
              <div v-else-if="false && block.type === 'COOLDOWN_ROTATION'" class="space-y-2">
                <div class="flex flex-wrap items-center gap-2" v-if="selectedBlockId === block.id">
                  <button class="px-2 py-0.5 text-[11px] rounded-md border border-slate-700 hover:bg-slate-800" @click.stop="updateBlockData(block, { columns: [...(block.data?.columns ?? []), { id: 't' + (block.data?.columns?.length ?? 0) + 1, label: 'New' }] })">+ Column</button>
                  <button class="px-2 py-0.5 text-[11px] rounded-md border border-slate-700 hover:bg-slate-800" @click.stop="updateBlockData(block, { rows: [...(block.data?.rows ?? []), { id: 'cd' + (block.data?.rows?.length ?? 0) + 1, label: 'CD', cells: {} }] })">+ Row</button>
                  <div class="border-l border-slate-700 pl-2 ml-2 flex flex-wrap items-center gap-1.5">
                    <div class="text-[10px] text-slate-500">💡 Healing:</div>
                    <button
                      v-for="cd in ['Spirit Link', 'Barrier', 'AMZ', 'Rally', 'Tranq', 'Aura Mastery']"
                      :key="cd"
                      class="px-1.5 py-0.5 text-[10px] rounded border border-emerald-700/50 hover:bg-emerald-900/30 text-emerald-400"
                      @click.stop="updateBlockData(block, { rows: [...(block.data?.rows ?? []), { id: 'cd' + Date.now(), label: cd, type: 'healing', cells: {} }] })"
                    >
                      {{ cd }}
                    </button>
                  </div>
                  <div class="border-l border-slate-700 pl-2 flex flex-wrap items-center gap-1.5">
                    <div class="text-[10px] text-slate-500">⚔️ DPS:</div>
                    <button
                      v-for="cd in ['Bloodlust', 'Power Infusion', 'Innervate', 'Darkness', 'Smoke Bomb']"
                      :key="cd"
                      class="px-1.5 py-0.5 text-[10px] rounded border border-red-700/50 hover:bg-red-900/30 text-red-400"
                      @click.stop="updateBlockData(block, { rows: [...(block.data?.rows ?? []), { id: 'cd' + Date.now(), label: cd, type: 'dps', cells: {} }] })"
                    >
                      {{ cd }}
                    </button>
                  </div>
                </div>
                <div class="overflow-auto">
                  <table class="w-full text-xs border-separate border-spacing-y-1">
                    <thead>
                      <tr>
                        <th class="text-left text-slate-400 px-2 py-1 align-top">
                          <div class="font-semibold">Cooldown</div>
                        </th>
                        <th v-for="col in (block.data?.columns ?? [])" :key="col.id" class="text-center px-2 py-1 align-top">
                          <div v-if="selectedBlockId === block.id" class="space-y-1">
                            <input
                              class="w-full bg-slate-900 border border-slate-700 rounded px-1 py-0.5 text-center font-semibold text-slate-200"
                              :value="col.label"
                              @input="col.label = ($event.target as HTMLInputElement).value; updateBlockData(block, { columns: block.data?.columns })"
                              placeholder="0:30"
                            />
                            <input
                              class="w-full bg-slate-900 border border-slate-700 rounded px-1 py-0.5 text-center text-[10px] text-slate-400"
                              :value="col.sublabel || ''"
                              @input="col.sublabel = ($event.target as HTMLInputElement).value; updateBlockData(block, { columns: block.data?.columns })"
                              placeholder="Phase"
                            />
                          </div>
                          <div v-else class="space-y-0.5">
                            <div class="font-semibold text-slate-200">{{ col.label }}</div>
                            <div v-if="col.sublabel" class="text-[10px] text-slate-400">{{ col.sublabel }}</div>
                          </div>
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="row in (block.data?.rows ?? [])" :key="row.id">
                        <td class="text-slate-300 px-2 py-1">
                          <input v-if="selectedBlockId === block.id"
                                 class="bg-slate-900 border border-slate-700 rounded px-1 py-0.5"
                                 :value="row.label"
                                 @input="row.label = ($event.target as HTMLInputElement).value; updateBlockData(block, { rows: block.data?.rows })"/>
                          <span v-else>{{ row.label }}</span>
                        </td>
                        <td v-for="col in (block.data?.columns ?? [])" :key="col.id" class="px-2 py-1">
                          <div class="min-h-8 rounded bg-slate-900/40 px-2 py-1 flex items-center justify-between"
                               @dragover.prevent
                               @drop.prevent="
                                 (e: DragEvent) => {
                                   const id = e.dataTransfer?.getData('text/plain');
                                   if (!id) return;
                                   const rows = ((block.data?.rows ?? []) as any[]).map((r: any) => ({ ...r, cells: { ...(r.cells || {}) } }))
                                   for (const r of rows) {
                                     for (const key of Object.keys(r.cells || {})) {
                                       if (r.cells[key] === id) r.cells[key] = null
                                     }
                                   }
                                   const rr = rows.find((r: any) => r.id === row.id)
                                   if (rr) { rr.cells[col.id] = id }
                                   updateBlockData(block, { rows })
                                 }">
                            <div v-if="row.cells?.[col.id]" class="flex items-center gap-1">
                              <span
                                class="px-2 py-0.5 rounded text-[11px] font-medium truncate"
                                :style="{
                                  backgroundColor: charColorById(row.cells[col.id]) + '22',
                                  color: charColorById(row.cells[col.id]),
                                  border: '1px solid ' + charColorById(row.cells[col.id]) + '55'
                                }"
                              >
                                {{ getCharacterById(row.cells[col.id])?.name }}
                              </span>
                              <button class="text-[11px] text-red-400 hover:text-red-300" @click.stop="row.cells[col.id] = null; updateBlockData(block, { rows: block.data?.rows })">✕</button>
                            </div>
                            <span v-else class="text-slate-500">—</span>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              
              <div v-else-if="block.type === 'INTERRUPT_ROTATION'" class="space-y-2">
                <div class="flex items-center gap-2" v-if="selectedBlockId === block.id">
                  <button class="px-2 py-0.5 text-[11px] rounded-md border border-slate-700 hover:bg-slate-800" @click.stop="updateBlockData(block, { columns: [...(block.data?.columns ?? []), { id: 'i' + (block.data?.columns?.length ?? 0) + 1, label: String((block.data?.columns?.length ?? 0) + 1) }] })">+ Add Slot</button>
                  <button class="px-2 py-0.5 text-[11px] rounded-md border border-slate-700 hover:bg-slate-800" @click.stop="updateBlockData(block, { rows: [...(block.data?.rows ?? []), { id: 'r' + (block.data?.rows?.length ?? 0) + 1, label: 'Add', cells: {} }] })">+ Add Target</button>
                </div>
                <div class="overflow-auto">
                  <table class="w-full text-xs border-separate border-spacing-y-1">
                    <thead>
                      <tr>
                        <th class="text-left text-slate-400 px-2 py-1">{{ block.data?.rowHeaderLabel || 'Target' }}</th>
                        <th v-for="(col, ci) in (block.data?.columns ?? [])" :key="col.id" class="text-left text-slate-300 px-2 py-1">
                          <div class="flex items-center gap-1">
                            <input class="w-16 bg-slate-900 border border-slate-700 rounded px-1 py-0.5" :value="col.label" @input="updateColumn(block, col.id, { label: ($event.target as HTMLInputElement).value })" />
                            <button class="text-slate-400 hover:text-slate-200" title="←" @click.stop="(function(){ const cols=(block.data!.columns as any[]).slice(); const idx=cols.findIndex((c:any)=>c.id===col.id); if(idx>0){ const tmp=cols[idx-1]; cols[idx-1]=cols[idx]; cols[idx]=tmp; updateBlockData(block, { columns: cols }) } })()">←</button>
                            <button class="text-slate-400 hover:text-slate-200" title=">" @click.stop="(function(){ const cols=(block.data!.columns as any[]).slice(); const idx=cols.findIndex((c:any)=>c.id===col.id); if(idx<cols.length-1){ const tmp=cols[idx+1]; cols[idx+1]=cols[idx]; cols[idx]=tmp; updateBlockData(block, { columns: cols }) } })()">→</button>
                            <button class="text-emerald-400 hover:text-emerald-300" title="⧉" @click.stop="(function(){ const cols=(block.data!.columns as any[]).slice(); const idx=cols.findIndex((c:any)=>c.id===col.id); const clone={ ...cols[idx], id: cols[idx].id + '_copy' }; cols.splice(idx+1,0,clone); updateBlockData(block, { columns: cols }) })()">⧉</button>
                            <button class="text-red-400 hover:text-red-300" title="✕" @click.stop="removeRotationColumn(block, col.id)">✕</button>
                          </div>
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="row in (block.data?.rows ?? [])" :key="row.id">
                        <td class="text-slate-300 px-2 py-1">
                          <input v-if="selectedBlockId === block.id" class="bg-slate-900 border border-slate-700 rounded px-1 py-0.5" :value="row.label" @input="row.label = ($event.target as HTMLInputElement).value; updateBlockData(block, { rows: block.data?.rows })"/>
                          <span v-else>{{ row.label }}</span>
                        </td>
                        <td v-for="col in (block.data?.columns ?? [])" :key="col.id" class="px-2 py-1">
                          <div class="min-h-8 rounded bg-slate-900/40 px-2 py-1 flex items-center justify-between"
                               @dragover.prevent
                               @drop.prevent="(e: DragEvent) => { const id = e.dataTransfer?.getData('text/plain'); if (!id) return; const rows = ((block.data?.rows ?? []) as any[]).map((r: any) => ({ ...r, cells: { ...(r.cells || {}) } })); for (const r of rows) { for (const k of Object.keys(r.cells || {})) { if (r.cells[k] === id) r.cells[k] = null } } const rr = rows.find((r: any) => r.id === row.id); if (rr) { rr.cells[col.id] = id } updateBlockData(block, { rows }) }">
                            <span class="truncate" :style="{ borderLeft: row.cells?.[col.id] ? '4px solid ' + charColorById(row.cells[col.id]) : undefined }">{{ getCharacterById(row.cells?.[col.id])?.name || '—' }}</span>
                            <button v-if="row.cells?.[col.id]" class="text-[11px] text-red-400 hover:text-red-300" @click.stop="row.cells[col.id] = null; updateBlockData(block, { rows: block.data?.rows })">✕</button>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              
              <div v-else-if="block.type === 'CUSTOM_SECTION'" class="space-y-2">
                <div class="flex items-center gap-2" v-if="selectedBlockId === block.id">
                  <button class="px-2 py-0.5 text-[11px] rounded-md border border-slate-700 hover:bg-slate-800" @click.stop="updateBlockData(block, { columns: [...(block.data?.columns ?? []), { id: 'c' + (block.data?.columns?.length ?? 0) + 1, label: 'Col', type: 'text' }] })">+ Column</button>
                  <button class="px-2 py-0.5 text-[11px] rounded-md border border-slate-700 hover:bg-slate-800" @click.stop="updateBlockData(block, { rows: [...(block.data?.rows ?? []), { id: 'r' + (block.data?.rows?.length ?? 0) + 1, data: {} }] })">+ Row</button>
                </div>
                <div class="overflow-auto">
                  <table class="w-full text-xs border-separate border-spacing-y-1">
                    <thead>
                      <tr>
                        <th v-for="col in (block.data?.columns ?? [])" :key="col.id" class="text-left text-slate-300 px-2 py-1">
                          <template v-if="selectedBlockId === block.id">
                            <input class="bg-slate-900 border border-slate-700 rounded px-1 py-0.5 mr-1" :value="col.label" @input="col.label = ($event.target as HTMLInputElement).value; updateBlockData(block, { columns: block.data?.columns })"/>
                            <select class="bg-slate-900 border border-slate-700 rounded px-1 py-0.5 text-[11px]" :value="col.type || 'text'" @change="col.type = ($event.target as HTMLSelectElement).value; updateBlockData(block, { columns: block.data?.columns })">
                              <option value="text">Text</option>
                              <option value="assignee">Assignee</option>
                            </select>
                          </template>
                          <template v-else>{{ col.label }}</template>
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="row in (block.data?.rows ?? [])" :key="row.id">
                        <td v-for="col in (block.data?.columns ?? [])" :key="col.id" class="px-2 py-1">
                          <template v-if="(col.type || 'text') === 'text'">
                            <input class="w-full bg-slate-900 border border-slate-700 rounded px-2 py-1" :value="row.data?.[col.id] || ''" @input="row.data = { ...(row.data || {}), [col.id]: ($event.target as HTMLInputElement).value }; updateBlockData(block, { rows: block.data?.rows })"/>
                          </template>
                          <template v-else>
                            <div class="min-h-8 rounded bg-slate-900/40 px-2 py-1 flex items-center justify-between"
                                 @dragover.prevent
                                 @drop.prevent="(e: DragEvent) => { const id = e.dataTransfer?.getData('text/plain'); if (!id) return; const rows = ((block.data?.rows ?? []) as any[]).map((r: any) => ({ ...r, data: { ...(r.data || {}) } })); for (const r of rows) { for (const key of Object.keys(r.data || {})) { if (r.data[key] === id) r.data[key] = null } } const rr = rows.find((r: any) => r.id === row.id); if (rr) { rr.data[col.id] = id } updateBlockData(block, { rows }) }">
                              <span class="truncate" :style="{ borderLeft: row.data?.[col.id] ? '4px solid ' + charColorById(row.data[col.id]) : undefined }">{{ getCharacterById(row.data?.[col.id])?.name || '—' }}</span>
                              <button v-if="row.data?.[col.id]" class="text-[11px] text-red-400 hover:text-red-300" @click.stop="row.data[col.id] = null; updateBlockData(block, { rows: block.data?.rows })">✕</button>
                            </div>
                          </template>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              
              <div v-else-if="block.type === 'HEADING'">
                <div class="flex items-center gap-2 mb-1">
                  <label class="text-[11px] font-semibold text-slate-400">
                    Heading
                  </label>
                  <select
                    class="bg-slate-900 border border-slate-700 rounded px-1 py-0.5 text-[11px]
               focus:outline-none focus:ring-1 focus:ring-emerald-500"
                    :value="block.data?.headingLevel ?? 2"
                    @change="updateBlockData(block, { headingLevel: Number(($event.target as HTMLSelectElement).value) })"
                  >
                    <option :value="1">H1</option>
                    <option :value="2">H2</option>
                    <option :value="3">H3</option>
                  </select>
                </div>

                <input
                  type="text"
                  class="w-full bg-slate-900 border border-slate-700 rounded-md px-2 py-1 text-xs
             focus:outline-none focus:ring-1 focus:ring-emerald-500"
                  :value="block.data?.headingText ?? ''"
                  @input="updateBlockData(block, { headingText: ($event.target as HTMLInputElement).value })"
                  placeholder="Section title (e.g. Boss 1 – P1)"
                />

                
                <div class="mt-2 border-t border-slate-800 pt-2">
                  <div
                    :class="[
          block.data?.headingLevel === 1 ? 'text-lg' :
          block.data?.headingLevel === 2 ? 'text-base' : 'text-sm',
          'font-semibold text-slate-100'
        ]"
                  >
                    {{ block.data?.headingText || 'Section title preview' }}
                  </div>
                </div>
              </div>

              
              <div v-else-if="block.type === 'DIVIDER'">
                <div class="flex items-center gap-2 mb-2">
                  <label class="text-[11px] font-semibold text-slate-400">
                    Label
                  </label>
                  <input
                    type="text"
                    class="flex-1 bg-slate-900 border border-slate-700 rounded-md px-2 py-1 text-xs
               focus:outline-none focus:ring-1 focus:ring-emerald-500"
                    :value="block.data?.dividerLabel ?? ''"
                    @input="updateBlockData(block, { dividerLabel: ($event.target as HTMLInputElement).value })"
                    placeholder="Optional label (e.g. 'Phase 2')"
                  />

                  <select
                    class="bg-slate-900 border border-slate-700 rounded px-1 py-0.5 text-[11px]
               focus:outline-none focus:ring-1 focus:ring-emerald-500"
                    :value="block.data?.dividerStyle ?? 'solid'"
                    @change="updateBlockData(block, { dividerStyle: ($event.target as HTMLSelectElement).value })"
                  >
                    <option value="solid">Solid</option>
                    <option value="dashed">Dashed</option>
                  </select>
                </div>

                
                <div class="flex items-center gap-2 text-[11px] text-slate-400">
                  <div class="flex-1">
                    <div
                      :class="[
            'border-t',
            block.data?.dividerStyle === 'dashed' ? 'border-dashed' : 'border-solid',
            'border-slate-600'
          ]"
                    />
                  </div>
                  <span v-if="block.data?.dividerLabel" class="px-2 py-0.5 rounded bg-slate-800 border border-slate-700">
        {{ block.data.dividerLabel }}
      </span>
                </div>
              </div>

              
              <div v-else-if="block.type === 'CHECKLIST'">
                <div class="flex items-center justify-between mb-1">
      <span class="text-[11px] font-semibold text-slate-400">
        Checklist items
      </span>
                  <button
                    class="text-[11px] px-2 py-0.5 rounded-md border border-slate-700 hover:bg-slate-800"
                    @click.stop="addChecklistItem(block)"
                  >
                    + Add
                  </button>
                </div>

                <div class="space-y-1 max-h-48 overflow-y-auto pr-1">
                  <div
                    v-for="item in block.data?.checklistItems ?? []"
                    :key="item.id"
                    class="flex items-center gap-2 group"
                  >
                    <input
                      type="checkbox"
                      class="h-3 w-3 rounded border-slate-600 bg-slate-900"
                      :checked="item.done"
                      @change="toggleChecklistItem(block, item.id)"
                    />

                    <input
                      type="text"
                      class="flex-1 bg-slate-900 border border-slate-700 rounded px-2 py-0.5 text-[11px]
                 focus:outline-none focus:ring-1 focus:ring-emerald-500"
                      :class="item.done ? 'line-through text-slate-500' : 'text-slate-100'"
                      :value="item.label"
                      @input="updateChecklistItemLabel(block, item.id, ($event.target as HTMLInputElement).value)"
                    />

                    <button
                      class="opacity-0 group-hover:opacity-100 text-[11px] text-red-400 hover:text-red-300 px-1"
                      title="Remove item"
                      @click.stop="removeChecklistItem(block, item.id)"
                    >
                      ✕
                    </button>
                  </div>
                </div>
              </div>


              
              <div v-else-if="block.type === 'BOSS_GRID'" class="space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                  <div class="text-[11px] text-slate-400">Configure positions and drag players into them</div>
                  <button class="text-[11px] px-2 py-1 rounded-md border border-slate-700 hover:bg-slate-800 self-start" @click.stop="addPosition(block)">+ Add position</button>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                  <div
                    v-for="pos in (block.data?.positions ?? [])"
                    :key="pos.id"
                    class="rounded-lg border border-slate-700 bg-slate-900/60 p-3 transition-all hover:border-slate-600"
                  >
                    <div class="flex items-center gap-2 mb-2">
                      <input
                        type="text"
                        class="flex-1 bg-slate-900 border border-slate-700 rounded px-2 py-1 text-xs font-medium focus:outline-none focus:ring-1 focus:ring-emerald-500"
                        :value="pos.label"
                        @input="updatePositionLabel(block, pos.id, ($event.target as HTMLInputElement).value)"
                        placeholder="Position name"
                      />
                      <button class="text-red-400 hover:text-red-300 text-sm p-1" title="Remove position" @click.stop="removePosition(block, pos.id)">✕</button>
                    </div>
                    <div
                      class="min-h-14 rounded bg-slate-900/40 p-1 space-y-1 transition-all duration-150"
                      :class="hoveredPositionId === pos.id ? 'ring-2 ring-emerald-500/80 bg-emerald-500/10 shadow-lg' : 'hover:bg-slate-900/60'"
                      @dragover.prevent
                      @dragenter.prevent="hoveredPositionId = pos.id"
                      @dragleave.prevent="hoveredPositionId = null"
                      @drop.prevent="onDropToPosition(block, pos.id, $event)"
                    >
                      <div
                        v-for="cid in (block.data?.assignments?.[pos.id] ?? [])"
                        :key="cid"
                        class="flex items-center justify-between rounded bg-slate-800/70 px-2 py-1"
                        :style="{ borderLeft: '4px solid ' + charColorById(cid) }"
                      >
                        <div class="truncate">{{ getCharacterById(cid)?.name ?? 'Unknown' }}</div>
                        <button class="text-red-400 hover:text-red-300" @click.stop="removeFromPosition(block, pos.id, cid)" title="Remove">✕</button>
                      </div>
                      <div v-if="!(block.data?.assignments?.[pos.id]?.length)" class="text-[11px] text-slate-500 text-center py-1">
                        Drag characters here
                      </div>
                    </div>
                    <div class="mt-2 space-y-1">
                      <div class="flex items-center justify-between text-[11px] text-slate-400">
                        <span>Notes</span>
                        <button class="px-1.5 py-0.5 rounded border border-slate-700 hover:bg-slate-800" @click.stop="addPositionNote(block, pos.id)">+ Add</button>
                      </div>
                      <div v-for="(note, ni) in ((block.data?.positionNotes?.[pos.id] ?? []) as any[])" :key="pos.id + ':' + ni" class="flex items-center gap-1">
                        <input class="flex-1 bg-slate-900 border border-slate-700 rounded px-2 py-0.5 text-[11px] text-slate-200" :value="note" @input="updatePositionNote(block, pos.id, ni, ($event.target as HTMLInputElement).value)"/>
                        <button class="text-red-400 hover:text-red-300" title="Remove note" @click.stop="removePositionNote(block, pos.id, ni)">✕</button>
                      </div>
                      <div v-if="!((block.data?.positionNotes?.[pos.id] ?? []) as any[]).length" class="text-[10px] text-slate-500">—</div>
                    </div>
                  </div>
                </div>
              </div>

              
              <div v-else-if="block.type === 'BENCH_ROSTER'" class="space-y-2">
                <div class="text-[11px] text-slate-400">Drag players not in the main comp</div>
                <div
                  class="min-h-24 rounded border border-dashed border-slate-600 p-2 space-y-1"
                  @dragover.prevent
                  @drop.prevent="
                    (e: DragEvent) => {
                      const id = e.dataTransfer?.getData('text/plain');
                      if (!id) return;
                      const bench = new Set<string>((block.data?.bench ?? []) as string[]);
                      bench.add(id);
                      updateBlockData(block, { bench: Array.from(bench) });
                    }
                  "
                >
                  <div
                    v-for="cid in (block.data?.bench ?? [])"
                    :key="cid"
                    class="flex items-center justify-between rounded bg-slate-800/70 px-2 py-1"
                  >
                    <div class="truncate">{{ getCharacterById(cid)?.name ?? 'Unknown' }}</div>
                    <button class="text-red-400 hover:text-red-300" @click.stop="updateBlockData(block, { bench: (block.data?.bench ?? []).filter((x: string) => x !== cid) })" title="Remove">✕</button>
                  </div>
                  <div v-if="!(block.data?.bench?.length)" class="text-[11px] text-slate-500 text-center py-1">Drag characters here</div>
                </div>
              </div>

              
              <div v-else-if="block.type === 'PHASE_STRATEGY'" class="space-y-2">
                <div class="text-[11px] text-slate-400">List phases and add notes per phase</div>
                <div class="space-y-2">
                  <div v-for="ph in (block.data?.phases ?? [])" :key="ph.id" class="rounded-md border border-slate-700 bg-slate-900/60 p-2">
                    <input
                      type="text"
                      class="w-full bg-slate-900 border border-slate-700 rounded-md px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-emerald-500 mb-1"
                      :value="ph.title"
                      @input="
                        (e: Event) => {
                          const phases = (block.data?.phases ?? []).map((p: any) => p.id === ph.id ? { ...p, title: (e.target as HTMLInputElement).value } : p)
                          updateBlockData(block, { phases })
                        }
                      "
                      placeholder="Phase title"
                    />
                    <textarea
                      class="w-full min-h-[70px] resize-y bg-slate-900 border border-slate-700 rounded-md px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-emerald-500"
                      :value="ph.notes"
                      @input="
                        (e: Event) => {
                          const phases = (block.data?.phases ?? []).map((p: any) => p.id === ph.id ? { ...p, notes: (e.target as HTMLTextAreaElement).value } : p)
                          updateBlockData(block, { phases })
                        }
                      "
                      placeholder="Notes for this phase"
                    />
                    <div class="mt-1 text-right">
                      <button class="text-[11px] px-2 py-0.5 rounded-md border border-slate-700 hover:bg-slate-800" @click.stop="updateBlockData(block, { phases: (block.data?.phases ?? []).filter((p: any) => p.id !== ph.id) })">Remove</button>
                    </div>
                  </div>
                </div>
                <div class="text-right">
                  <button class="text-[11px] px-2 py-0.5 rounded-md border border-slate-700 hover:bg-slate-800" @click.stop="updateBlockData(block, { phases: [...(block.data?.phases ?? []), { id: 'p' + (block.data?.phases?.length ?? 0) + 1, title: 'New phase', notes: '' }] })">+ Add phase</button>
                </div>
              </div>

              
              <div v-else-if="block.type === 'COOLDOWN_ROTATION'" class="space-y-2">
                <div class="flex items-center justify-between gap-2">
                  <div class="text-[11px] text-slate-400 flex items-center gap-2" v-if="selectedBlockId === block.id">
                    <span>Define time slots and assign cooldowns</span>
                    <label class="inline-flex items-center gap-1">
                      <span class="text-slate-500">Row header</span>
                      <input class="bg-slate-900 border border-slate-700 rounded px-1 py-0.5 w-28" :value="block.data?.rowHeaderLabel || 'Cooldown'" @input="updateBlockData(block, { rowHeaderLabel: ($event.target as HTMLInputElement).value })" />
                    </label>
                  </div>
                  <div class="text-[11px] text-slate-400" v-else>Define time slots and assign cooldowns</div>
                  <div class="flex items-center gap-1">
                    <button class="text-[11px] px-2 py-0.5 rounded-md border border-slate-700 hover:bg-slate-800" @click.stop="addRotationColumn(block)">+ Add time</button>
                    <span class="text-[10px] text-slate-500 ml-1">Presets:</span>
                    <button class="text-[10px] px-2 py-0.5 rounded-md border border-slate-700 hover:bg-slate-800" @click.stop="addColumnPreset(block, '30s')">0:30</button>
                    <button class="text-[10px] px-2 py-0.5 rounded-md border border-slate-700 hover:bg-slate-800" @click.stop="addColumnPreset(block, '1m')">1:00</button>
                    <button class="text-[10px] px-2 py-0.5 rounded-md border border-slate-700 hover:bg-slate-800" @click.stop="addColumnPreset(block, '90s')">1:30</button>
                    <button class="text-[11px] px-2 py-0.5 rounded-md border border-slate-700 hover:bg-slate-800" @click.stop="addRotationRow(block, 'Power Infusion')">+ PI</button>
                    <button class="text-[11px] px-2 py-0.5 rounded-md border border-slate-700 hover:bg-slate-800" @click.stop="addRotationRow(block, 'Barrier')">+ Barrier</button>
                    <button class="text-[11px] px-2 py-0.5 rounded-md border border-slate-700 hover:bg-slate-800" @click.stop="addRotationRow(block, 'Rally')">+ Rally</button>
                  </div>
                </div>
                <div class="overflow-auto">
                  <table class="min-w-full text-[11px] border border-slate-700">
                    <thead>
                      <tr class="bg-slate-900/70">
                        <th class="border-b border-slate-700 p-1 text-left w-40">{{ block.data?.rowHeaderLabel || 'Cooldown' }}</th>
                        <th v-for="col in (block.data?.columns ?? [])" :key="col.id" class="border-b border-l border-slate-700 p-1">
                          <div class="flex items-center gap-1">
                            <input class="w-20 bg-slate-900 border border-slate-700 rounded px-1 py-0.5" :value="col.label" @input="updateColumn(block, col.id, { label: ($event.target as HTMLInputElement).value })" />
                            <input class="w-24 bg-slate-900 border border-slate-700 rounded px-1 py-0.5" :value="col.sublabel || ''" placeholder="phase" @input="updateColumn(block, col.id, { sublabel: ($event.target as HTMLInputElement).value })" />
                            <button class="text-slate-400 hover:text-slate-200" title="←" @click.stop="(function(){ const cols=(block.data!.columns as any[]).slice(); const idx=cols.findIndex((c:any)=>c.id===col.id); if(idx>0){ const tmp=cols[idx-1]; cols[idx-1]=cols[idx]; cols[idx]=tmp; updateBlockData(block, { columns: cols }) } })()">←</button>
                            <button class="text-slate-400 hover:text-slate-200" title=">" @click.stop="(function(){ const cols=(block.data!.columns as any[]).slice(); const idx=cols.findIndex((c:any)=>c.id===col.id); if(idx<cols.length-1){ const tmp=cols[idx+1]; cols[idx+1]=cols[idx]; cols[idx]=tmp; updateBlockData(block, { columns: cols }) } })()">→</button>
                            <button class="text-emerald-400 hover:text-emerald-300" title="⧉" @click.stop="(function(){ const cols=(block.data!.columns as any[]).slice(); const idx=cols.findIndex((c:any)=>c.id===col.id); const clone={ ...cols[idx], id: cols[idx].id + '_copy' }; cols.splice(idx+1,0,clone); updateBlockData(block, { columns: cols }) })()">⧉</button>
                            <button class="text-red-400 hover:text-red-300" title="✕" @click.stop="removeRotationColumn(block, col.id)">✕</button>
                          </div>
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="row in (block.data?.rows ?? [])" :key="row.id" class="odd:bg-slate-900/40">
                        <td class="border-t border-slate-700 p-1 align-top">
                          <div class="flex items-center gap-1 flex-wrap">
                            <span v-if="rowChip(row)" class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-semibold ring-1 ring-inset" :class="rowChip(row)!.cls">{{ rowChip(row)!.text }}</span>
                            <input class="bg-slate-900 border border-slate-700 rounded px-1 py-0.5 w-44" :value="row.label" @input="updateRow(block, row.id, { label: ($event.target as HTMLInputElement).value })" />
                            <div class="flex items-center gap-1 text-[10px] text-slate-400">
                              <label class="inline-flex items-center gap-1">
                                <input type="checkbox" :checked="isPairRow(row)" @change="updateRow(block, row.id, { mode: ($event.target as HTMLInputElement).checked ? 'pair' : 'single' })" /> Pair
                              </label>
                              <template v-if="isPairRow(row)">
                                <input class="bg-slate-900 border border-slate-700 rounded px-1 py-0.5 w-24" :value="row.fromLabel || 'Priest'" @input="updateRow(block, row.id, { fromLabel: ($event.target as HTMLInputElement).value })" placeholder="Caster label" />
                                <input class="bg-slate-900 border border-slate-700 rounded px-1 py-0.5 w-24" :value="row.toLabel || 'Target'" @input="updateRow(block, row.id, { toLabel: ($event.target as HTMLInputElement).value })" placeholder="Target label" />
                                <label class="inline-flex items-center gap-1">
                                  <input type="checkbox" :checked="(row.requirePriest ?? true)" @change="updateRow(block, row.id, { requirePriest: ($event.target as HTMLInputElement).checked })" /> Priest only
                                </label>
                              </template>
                            </div>
                            <button class="text-slate-400 hover:text-slate-200" title="↑" @click.stop="(function(){ const rows=(block.data!.rows as any[]).slice(); const idx=rows.findIndex((r:any)=>r.id===row.id); if(idx>0){ const tmp=rows[idx-1]; rows[idx-1]=rows[idx]; rows[idx]=tmp; updateBlockData(block, { rows }) } })()">↑</button>
                            <button class="text-slate-400 hover:text-slate-200" title="↓" @click.stop="(function(){ const rows=(block.data!.rows as any[]).slice(); const idx=rows.findIndex((r:any)=>r.id===row.id); if(idx<rows.length-1){ const tmp=rows[idx+1]; rows[idx+1]=rows[idx]; rows[idx]=tmp; updateBlockData(block, { rows }) } })()">↓</button>
                            <button class="text-emerald-400 hover:text-emerald-300" title="⧉" @click.stop="(function(){ const rows=(block.data!.rows as any[]).slice(); const idx=rows.findIndex((r:any)=>r.id===row.id); const clone={ ...rows[idx], id: rows[idx].id + '_copy' }; rows.splice(idx+1,0,clone); updateBlockData(block, { rows }) })()">⧉</button>
                            <button class="text-red-400 hover:text-red-300" title="✕" @click.stop="removeRotationRow(block, row.id)">✕</button>
                          </div>
                        </td>
                        <td v-for="col in (block.data?.columns ?? [])" :key="col.id" class="border-t border-l border-slate-700 p-1">
                          <template v-if="isPairRow(row)">
                            <div class="grid grid-cols-2 gap-1">
                              <div class="rounded bg-slate-900/40 p-1 min-h-8" @dragover.prevent @drop.prevent="(e: DragEvent) => dropToPairCell(block, row.id, col.id, 'from', e)">
                                <div class="text-[10px] text-slate-500 mb-0.5">Priest</div>
                                <div v-if="getPairCell(block, row.id, col.id).from" class="flex items-center justify-between rounded bg-slate-800/70 px-2 py-1" :style="{ borderLeft: '3px solid ' + charColorById(getPairCell(block, row.id, col.id).from!) }">
                                  <div class="truncate">{{ getCharacterById(getPairCell(block, row.id, col.id).from!)?.name ?? 'Unknown' }}</div>
                                  <button class="text-red-400 hover:text-red-300" @click.stop="clearPair(block, row.id, col.id, 'from')">✕</button>
                                </div>
                                <div v-else class="text-center text-slate-500 text-[11px]">Drop priest</div>
                              </div>
                              <div class="rounded bg-slate-900/40 p-1 min-h-8" @dragover.prevent @drop.prevent="(e: DragEvent) => dropToPairCell(block, row.id, col.id, 'to', e)">
                                <div class="text-[10px] text-slate-500 mb-0.5">Target</div>
                                <div v-if="getPairCell(block, row.id, col.id).to" class="flex items-center justify-between rounded bg-slate-800/70 px-2 py-1" :style="{ borderLeft: '3px solid ' + charColorById(getPairCell(block, row.id, col.id).to!) }">
                                  <div class="truncate">{{ getCharacterById(getPairCell(block, row.id, col.id).to!)?.name ?? 'Unknown' }}</div>
                                  <button class="text-red-400 hover:text-red-300" @click.stop="clearPair(block, row.id, col.id, 'to')">✕</button>
                                </div>
                                <div v-else class="text-center text-slate-500 text-[11px]">Drop target</div>
                              </div>
                            </div>
                          </template>
                          <template v-else>
                            <div class="min-h-9 rounded bg-slate-900/40 p-1 transition-all" @dragover.prevent @drop.prevent="(e: DragEvent) => { const id = e.dataTransfer?.getData('text/plain'); if (!id) return; const cells = ensureCells(block); const rowCells = cells[row.id] ?? (cells[row.id] = {}); rowCells[col.id] = [id]; updateBlockData(block, { cells }) }">
                              <div v-for="cid in getCell(block, row.id, col.id)" :key="cid" class="flex items-center justify-between rounded bg-slate-800/70 px-2 py-1" :style="{ borderLeft: '3px solid ' + charColorById(cid) }">
                                <div class="truncate">{{ getCharacterById(cid)?.name ?? 'Unknown' }}</div>
                                <button class="text-red-400 hover:text-red-300" @click.stop="removeFromCell(block, row.id, col.id, cid)" title="Remove">✕</button>
                              </div>
                              <div v-if="!getCell(block, row.id, col.id).length" class="text-center text-slate-500">Drop here</div>
                            </div>
                          </template>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div class="text-right">
                  <button class="text-[11px] px-2 py-0.5 rounded-md border border-slate-700 hover:bg-slate-800" @click.stop="addRotationRow(block, 'Power Infusion')">+ Add PI</button>
                  <button class="text-[11px] px-2 py-0.5 rounded-md border border-slate-700 hover:bg-slate-800 ml-2" @click.stop="addRotationRow(block, 'Barrier')">+ Add Barrier</button>
                  <button class="text-[11px] px-2 py-0.5 rounded-md border border-slate-700 hover:bg-slate-800 ml-2" @click.stop="addRotationRow(block, 'Rally')">+ Add Rally</button>
                </div>
              </div>

              
              <div v-else-if="block.type === 'INTERRUPT_ROTATION'" class="space-y-2">
                <div class="flex items-center justify-between">
                  <div class="text-[11px] text-slate-400">Set interrupt order across waves or time slots</div>
                  <button class="text-[11px] px-2 py-0.5 rounded-md border border-slate-700 hover:bg-slate-800" @click.stop="addRotationColumn(block)">+ Add slot</button>
                </div>
                <div class="overflow-auto">
                  <table class="min-w-full text-[11px] border border-slate-700">
                    <thead>
                      <tr class="bg-slate-900/70">
                        <th class="border-b border-slate-700 p-1 text-left w-40">Target</th>
                        <th v-for="col in (block.data?.columns ?? [])" :key="col.id" class="border-b border-l border-slate-700 p-1">
                          <div class="flex items-center gap-1">
                            <input class="w-20 bg-slate-900 border border-slate-700 rounded px-1 py-0.5" :value="col.label" @input="updateColumn(block, col.id, { label: ($event.target as HTMLInputElement).value })" />
                            <button class="text-red-400 hover:text-red-300" title="Remove" @click.stop="removeRotationColumn(block, col.id)">✕</button>
                          </div>
                        </th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="row in (block.data?.rows ?? [])" :key="row.id" class="odd:bg-slate-900/40">
                        <td class="border-t border-slate-700 p-1 align-top">
                          <div class="flex items-center gap-1">
                            <input class="w-full bg-slate-900 border border-slate-700 rounded px-1 py-0.5" :value="row.label" @input="updateRow(block, row.id, { label: ($event.target as HTMLInputElement).value })" />
                            <button class="text-red-400 hover:text-red-300" title="Remove" @click.stop="removeRotationRow(block, row.id)">✕</button>
                          </div>
                        </td>
                        <td v-for="col in (block.data?.columns ?? [])" :key="col.id" class="border-t border-l border-slate-700 p-1">
                          <div
                            class="min-h-9 rounded bg-slate-900/40 p-1 space-y-1 transition-all"
                            @dragover.prevent
                            @drop.prevent="(e: DragEvent) => dropToCell(block, row.id, col.id, e)"
                          >
                            <div v-for="cid in getCell(block, row.id, col.id)" :key="cid" class="flex items-center justify-between rounded bg-slate-800/70 px-2 py-1" :style="{ borderLeft: '3px solid ' + charColorById(cid) }">
                              <div class="truncate">{{ getCharacterById(cid)?.name ?? 'Unknown' }}</div>
                              <button class="text-red-400 hover:text-red-300" @click.stop="removeFromCell(block, row.id, col.id, cid)" title="Remove">✕</button>
                            </div>
                            <div v-if="!getCell(block, row.id, col.id).length" class="text-center text-slate-500">Drop here</div>
                          </div>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <div class="text-right">
                  <button class="text-[11px] px-2 py-0.5 rounded-md border border-slate-700 hover:bg-slate-800" @click.stop="addRotationRow(block, 'Boss')">+ Add row</button>
                </div>
              </div>

              
              <div v-else>
                <span class="text-slate-500 italic">{{ block.type }} content coming soon…</span>
                <div class="mt-1 text-[11px] text-slate-500">start {{ block.colStart }}/12 • span {{ block.colSpan }}/12</div>
              </div>
            </div>
          </article>
        </template>
    </Draggable>
  </div>
</template>

<style scoped>
.drag-handle { cursor: grab; }
.drag-handle:active { cursor: grabbing; }
</style>
