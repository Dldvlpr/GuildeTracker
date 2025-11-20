<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted, computed, type ComponentPublicInstance } from 'vue';
import Draggable from 'vuedraggable';
import type { RaidPlanBlock } from '@/interfaces/raidPlan.interface.ts';
import type { Character } from '@/interfaces/game.interface';
import { Role, ROLE_COLORS } from '@/interfaces/game.interface';
import { getSpecOptions, getRoleByClassAndSpec } from '@/data/gameData';
import { getClassColor } from '@/utils/classColors';
import { CANVAS_MAPS } from '@/data/canvasMaps';
import { RAID_MARKER_ICONS } from '@/data/raidMarkers';

const props = defineProps<{
  blocks: RaidPlanBlock[];
  characters?: Character[];
  groupTargets?: { tank: number; healer: number } | null;
  readonly?: boolean;
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
const canvasMaps = CANVAS_MAPS;
const raidMarkerIcons = RAID_MARKER_ICONS;
const genericAssignmentTokens = {
  boss: [
    { id: 'boss-tank', label: 'Boss Tank', role: 'Tanks', tint: '#0ea5e9' },
    { id: 'boss-healer', label: 'Boss Healer', role: 'Healers', tint: '#22c55e' },
    { id: 'boss-melee', label: 'Boss Melee', role: 'Melee', tint: '#f97316' },
    { id: 'boss-ranged', label: 'Boss Ranged', role: 'Ranged', tint: '#a855f7' },
  ],
  adds: [
    { id: 'adds-tank', label: 'Adds Tank', role: 'Tanks', tint: '#06b6d4' },
    { id: 'adds-healer', label: 'Adds Healer', role: 'Healers', tint: '#34d399' },
    { id: 'adds-melee', label: 'Adds Melee', role: 'Melee', tint: '#fb923c' },
    { id: 'adds-ranged', label: 'Adds Ranged', role: 'Ranged', tint: '#c084fc' },
  ],
} as const;
const activeMarkerPaletteId = ref<string | null>(null);
const activePlayerPaletteId = ref<string | null>(null);
const activeMapPickerId = ref<string | null>(null);
const playerSearch = ref('');
const mapSearch = ref('');
const filteredCharacters = computed<Character[]>(() => {
  if (!props.characters) return [];
  const q = playerSearch.value.trim().toLowerCase();
  if (!q) return props.characters;
  return props.characters.filter((c) => c.name.toLowerCase().includes(q) || (c.class?.toLowerCase().includes(q)));
});

const filteredMaps = computed(() => {
  const q = mapSearch.value.trim().toLowerCase();
  if (!q) return canvasMaps;
  return canvasMaps.filter((m) =>
    m.name.toLowerCase().includes(q) ||
    (m.raid?.toLowerCase().includes(q)) ||
    (m.expansion?.toLowerCase().includes(q)) ||
    (m.tags?.some((tag) => tag.toLowerCase().includes(q)))
  );
});

// FREE_CANVAS drag/resize state
type DragMode = 'move' | 'resize-nw' | 'resize-ne' | 'resize-sw' | 'resize-se' | 'rotate'
const activeShape = ref<{
  blockId: string; shapeId: string; mode: DragMode;
  startMouseX: number; startMouseY: number;
  startX: number; startY: number; startW: number; startH: number;
  centerX?: number; centerY?: number;
} | null>(null)
const canvasRefs = new Map<string, HTMLElement>()
const mapById = new Map(canvasMaps.map((m) => [m.id, m]))
const raidMarkerMap = new Map(raidMarkerIcons.map((i) => [i.id, i]))

type CanvasBackgroundState = {
  color: string;
  grid: boolean;
  gridSize: number;
  imageUrl: string;
  imageOpacity: number;
  mapId: string | null;
  showCenter: boolean;
}

function ensureCanvasBackground(block: RaidPlanBlock): CanvasBackgroundState {
  const legacyColor = (block.data?.canvasBackground as any)?.color || '#0f172a'
  const legacyGrid = (block.data?.canvasBackground as any)?.grid ?? (block.data?.grid ?? false)
  const legacyGridSize = (block.data?.canvasBackground as any)?.gridSize ?? 8
  const legacyImage = (block.data?.canvasBackground as any)?.imageUrl || ''
  const legacyImageOpacity = (block.data?.canvasBackground as any)?.imageOpacity ?? 0.5
  const legacyMap = (block.data?.canvasBackground as any)?.mapId ?? null
  const legacyCenter = (block.data?.canvasBackground as any)?.showCenter ?? false
  return {
    color: legacyColor,
    grid: Boolean(legacyGrid),
    gridSize: Math.max(4, Math.min(64, Number(legacyGridSize) || 8)),
    imageUrl: legacyImage,
    imageOpacity: Math.min(1, Math.max(0, Number(legacyImageOpacity) ?? 0.5)),
    mapId: legacyMap,
    showCenter: legacyCenter,
  }
}

function patchCanvasBackground(block: RaidPlanBlock, patch: Partial<CanvasBackgroundState>) {
  const current = ensureCanvasBackground(block)
  const next = { ...current, ...patch }
  updateBlockData(block, { canvasBackground: next })
}

function canvasSnap(block: RaidPlanBlock): number {
  const snap = Number((block.data as any)?.canvasSnap ?? 8)
  return Math.max(1, Math.min(64, Number.isFinite(snap) ? snap : 8))
}

function snapTo(block: RaidPlanBlock, value: number): number {
  const size = canvasSnap(block)
  return Math.round(value / size) * size
}

function setCanvasRef(blockId: string, el: Element | ComponentPublicInstance | null) {
  const dom = (el as any)?.$el ? (el as any).$el as HTMLElement : (el as HTMLElement | null)
  if (dom) canvasRefs.set(blockId, dom)
}

function canvasBackgroundStyle(block: RaidPlanBlock): Record<string, string> {
  const bg = ensureCanvasBackground(block)
  const style: Record<string, string> = {
    height: `${block.data?.canvasHeight || 280}px`,
    backgroundColor: bg.color || '#0f172a',
  }
  if (bg.grid) {
    const size = Math.max(4, Math.min(64, bg.gridSize || 8))
    style.backgroundImage = `linear-gradient(0deg, rgba(148,163,184,0.12) 1px, transparent 1px), linear-gradient(90deg, rgba(148,163,184,0.12) 1px, transparent 1px)`
    style.backgroundSize = `${size}px ${size}px`
  } else {
    style.backgroundImage = 'none'
  }
  return style
}

function canvasMapSvg(block: RaidPlanBlock): string | null {
  const bg = ensureCanvasBackground(block)
  if (!bg.mapId) return null
  const map = mapById.get(bg.mapId)
  if (!map) return null
  return map.svg
}

function canvasBackgroundImage(block: RaidPlanBlock): { url: string; opacity: number } | null {
  const bg = ensureCanvasBackground(block)
  if (!bg.imageUrl) return null
  return { url: bg.imageUrl, opacity: bg.imageOpacity }
}

function clearCanvasMap(block: RaidPlanBlock) {
  patchCanvasBackground(block, { mapId: null })
}

function clearCanvasImage(block: RaidPlanBlock) {
  patchCanvasBackground(block, { imageUrl: '' })
}

function clamp(n: number, min: number, max: number): number { return Math.max(min, Math.min(max, n)) }

function getCanvasSize(blockId: string): { w: number; h: number } {
  const el = canvasRefs.get(blockId)
  if (!el) return { w: 0, h: 0 }
  return { w: el.clientWidth || 0, h: el.clientHeight || 0 }
}

function getShape(block: RaidPlanBlock, id: string): any | null {
  const arr = (block.data?.shapes as any[] | undefined) || []
  return arr.find((s: any) => s.id === id) || null
}

function updateShape(block: RaidPlanBlock, id: string, mutate: (s: any) => any) {
  const shapes = ([...(block.data?.shapes as any[] || [])]).map((s: any) => s.id === id ? mutate({ ...s }) : s)
  updateBlockData(block, { shapes })
}

function onShapeMouseDown(block: RaidPlanBlock, shapeId: string, e: MouseEvent) {
  // Select shape on drag start
  selectShape(block, shapeId)
  const s = getShape(block, shapeId)
  if (!s) return
  const startMouseX = e.clientX
  const startMouseY = e.clientY
  activeShape.value = {
    blockId: block.id,
    shapeId,
    mode: 'move',
    startMouseX,
    startMouseY,
    startX: Number(s.x || 0),
    startY: Number(s.y || 0),
    startW: Number(s.w || 60),
    startH: Number(s.h || 40),
  }

  const onMove = (ev: MouseEvent) => {
    const act = activeShape.value
    if (!act) return
    const dx = ev.clientX - act.startMouseX
    const dy = ev.clientY - act.startMouseY
    const size = getCanvasSize(act.blockId)
    const minW = 16, minH = 16
    let nx = act.startX + dx
    let ny = act.startY + dy
    // Snap to 8px grid
    nx = snapTo(block, nx)
    ny = snapTo(block, ny)
    // Clamp into canvas
    nx = clamp(nx, 0, Math.max(0, size.w - minW))
    ny = clamp(ny, 0, Math.max(0, size.h - minH))
    updateShape(block, shapeId, (sh: any) => ({ ...sh, x: nx, y: ny }))
  }

  const onUp = () => {
    document.removeEventListener('mousemove', onMove)
    document.removeEventListener('mouseup', onUp)
    activeShape.value = null
  }

  document.addEventListener('mousemove', onMove)
  document.addEventListener('mouseup', onUp)
}

function onShapeResizeMouseDown(block: RaidPlanBlock, shapeId: string, mode: DragMode, e: MouseEvent) {
  // Select shape on resize start
  selectShape(block, shapeId)
  const s = getShape(block, shapeId)
  if (!s) return
  const startMouseX = e.clientX
  const startMouseY = e.clientY
  activeShape.value = {
    blockId: block.id,
    shapeId,
    mode,
    startMouseX,
    startMouseY,
    startX: Number(s.x || 0),
    startY: Number(s.y || 0),
    startW: Number(s.w || 60),
    startH: Number(s.h || 40),
  }

  const onMove = (ev: MouseEvent) => {
    const act = activeShape.value
    if (!act) return
    const dx = ev.clientX - act.startMouseX
    const dy = ev.clientY - act.startMouseY
    const size = getCanvasSize(act.blockId)
    const minW = 16, minH = 16

    // Base new values before modifiers
    let nx = act.startX
    let ny = act.startY
    let nw = act.startW
    let nh = act.startH

    if (act.mode === 'resize-se') {
      nw = act.startW + dx
      nh = act.startH + dy
    } else if (act.mode === 'resize-ne') {
      nw = act.startW + dx
      nh = act.startH - dy
      ny = act.startY + dy
    } else if (act.mode === 'resize-sw') {
      nw = act.startW - dx
      nh = act.startH + dy
      nx = act.startX + dx
    } else if (act.mode === 'resize-nw') {
      nw = act.startW - dx
      nh = act.startH - dy
      nx = act.startX + dx
      ny = act.startY + dy
    }

    // Keep aspect ratio with Shift
    const keepRatio = ev.shiftKey
    const ratio = act.startW > 0 && act.startH > 0 ? (act.startW / act.startH) : 1
    if (keepRatio) {
      // Decide dominant axis
      const aw = Math.abs(nw - act.startW)
      const ah = Math.abs(nh - act.startH)
      if (aw >= ah) {
        nh = nw / ratio
      } else {
        nw = nh * ratio
      }
      // Recompute position for handles that move origin
      if (act.mode === 'resize-ne') {
        ny = act.startY + (act.startH - nh)
      } else if (act.mode === 'resize-sw') {
        nx = act.startX + (act.startW - nw)
      } else if (act.mode === 'resize-nw') {
        nx = act.startX + (act.startW - nw)
        ny = act.startY + (act.startH - nh)
      }
    }

    // Resize from center with Alt/Option
    if (ev.altKey) {
      const dw = nw - act.startW
      const dh = nh - act.startH
      nx = act.startX - dw / 2
      ny = act.startY - dh / 2
    }

    // Snap values to 8px
    nx = snapTo(block, nx)
    ny = snapTo(block, ny)
    nw = Math.max(minW, snapTo(block, nw))
    nh = Math.max(minH, snapTo(block, nh))

    // Clamp within canvas bounds
    nx = clamp(nx, 0, Math.max(0, size.w - minW))
    ny = clamp(ny, 0, Math.max(0, size.h - minH))
    nw = clamp(nw, minW, Math.max(minW, size.w - nx))
    nh = clamp(nh, minH, Math.max(minH, size.h - ny))

    updateShape(block, shapeId, (sh: any) => ({ ...sh, x: nx, y: ny, w: nw, h: nh }))
  }

  const onUp = () => {
    document.removeEventListener('mousemove', onMove)
    document.removeEventListener('mouseup', onUp)
    activeShape.value = null
  }

  document.addEventListener('mousemove', onMove)
  document.addEventListener('mouseup', onUp)
}

function onShapeRotateMouseDown(block: RaidPlanBlock, shapeId: string, e: MouseEvent) {
  const s = getShape(block, shapeId)
  if (!s) return
  const rect = (e.currentTarget as HTMLElement).parentElement?.getBoundingClientRect()
  if (!rect) return
  const centerX = rect.left + rect.width / 2
  const centerY = rect.top + rect.height / 2
  activeShape.value = {
    blockId: block.id,
    shapeId,
    mode: 'rotate',
    startMouseX: e.clientX,
    startMouseY: e.clientY,
    startX: s.x || 0,
    startY: s.y || 0,
    startW: s.w || 60,
    startH: s.h || 40,
    centerX, centerY
  }
  const onMove = (ev: MouseEvent) => {
    const act = activeShape.value
    if (!act || act.mode !== 'rotate') return
    const ang = Math.atan2(ev.clientY - (act.centerY || 0), ev.clientX - (act.centerX || 0)) * 180 / Math.PI
    const snapped = ev.shiftKey ? Math.round(ang / 5) * 5 : ang
    updateShape(block, shapeId, (sh: any) => ({ ...sh, rotation: snapped }))
  }
  const onUp = () => {
    document.removeEventListener('mousemove', onMove)
    document.removeEventListener('mouseup', onUp)
    activeShape.value = null
  }
  document.addEventListener('mousemove', onMove)
  document.addEventListener('mouseup', onUp)
}

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
    // enforce widths for fixed-width blocks
    normalizeBlockLayout(innerBlocks.value)
  },
  { deep: true }
);

watch(selectedBlockId, () => {
  activeMarkerPaletteId.value = null
  activePlayerPaletteId.value = null
  activeMapPickerId.value = null
  playerSearch.value = ''
  mapSearch.value = ''
});

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

function isFixedWidth(block: RaidPlanBlock): boolean {
  return [
    'GROUPS_GRID',
    'ROLE_MATRIX',
    'COOLDOWN_ROTATION',
    'INTERRUPT_ROTATION',
    'HEADING',
    'DIVIDER',
    'BOSS_GRID',
  ].includes(block.type as any)
}

function enforcedColSpan(block: RaidPlanBlock): number | null {
  if (isFixedWidth(block)) return 12
  return null
}

function minColSpanFor(block: RaidPlanBlock): number {
  switch (block.type) {
    case 'BOSS_GRID':
    case 'CUSTOM_SECTION':
    case 'PHASE_STRATEGY':
    case 'IMAGE':
      return 6
    case 'TEXT':
    case 'CHECKLIST':
    case 'BENCH_ROSTER':
      return 4
    case 'FREE_CANVAS':
      return 6
    default:
      return 6
  }
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
  const enforced = enforcedColSpan(block)
  if (enforced) {
    emit('update-block', { ...block, colSpan: enforced, colStart: 1 })
    return
  }
  const min = minColSpanFor(block)
  const snapped = Math.max(min, snapSpan(rawSpan));
  const maxStart = 12 - snapped + 1;
  const safeStart = Math.min(block.colStart, maxStart);

  emit('update-block', { ...block, colSpan: snapped, colStart: safeStart });
}

function setBlockStart(block: RaidPlanBlock, rawStart: number) {
  if (enforcedColSpan(block)) {
    emit('update-block', { ...block, colStart: 1 });
    return;
  }
  const start = Math.max(1, Math.min(rawStart, 12));
  const maxStart = 12 - block.colSpan + 1;
  const safeStart = Math.min(start, maxStart);

  emit('update-block', {
    ...block,
    colStart: safeStart,
  });
}

// Normalize block widths on load/updates so fixed-width blocks become full-width automatically
function normalizeBlockLayout(blocks: RaidPlanBlock[]) {
  for (const b of blocks) {
    const w = enforcedColSpan(b)
    if (w && (b.colSpan !== w || b.colStart !== 1)) {
      emit('update-block', { ...b, colSpan: w, colStart: 1 })
    }
  }
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

  // Arrow keys for Free Canvas shapes when selected
  if (block.type === 'FREE_CANVAS') {
    const sel = (block.data as any)?.selectedShapeId as string | undefined
    if (sel) {
      const s = getShape(block, sel)
      if (s) {
        const delta = e.shiftKey ? 8 : 1
        let handled = false
        let nx = Number(s.x || 0)
        let ny = Number(s.y || 0)
        if (key === 'arrowleft') { nx -= delta; handled = true }
        if (key === 'arrowright') { nx += delta; handled = true }
        if (key === 'arrowup') { ny -= delta; handled = true }
        if (key === 'arrowdown') { ny += delta; handled = true }
        if (handled) {
          const size = getCanvasSize(block.id)
          const minW = 16, minH = 16
          nx = clamp(nx, 0, Math.max(0, size.w - minW))
          ny = clamp(ny, 0, Math.max(0, size.h - minH))
          updateShape(block, sel, (sh: any) => ({ ...sh, x: nx, y: ny }))
          e.preventDefault()
          return
        }
      }
    }
  }

  // Default block-level arrow controls
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

// Free canvas helpers
type CanvasShapeType = 'rect'|'text'|'timer'|'image'|'marker'|'arrow'|'circle'|'icon'|'player'

function addShape(block: RaidPlanBlock, type: CanvasShapeType, overrides: Record<string, any> = {}) {
  const shapes = ([...(block.data?.shapes as any[] || [])])
  const id = 'sh' + String(Date.now()) + '-' + String(Math.random()).slice(2,6)
  const base: any = { id, type, x: 10, y: 10, w: 80, h: 40, rotation: 0 }
  if (type === 'rect') { Object.assign(base, { color: '#64748b' }) }
  if (type === 'circle') { Object.assign(base, { color: 'rgba(59,130,246,0.15)', border: '#3b82f6', borderWidth: 3, w: 120, h: 120 }) }
  if (type === 'text') { Object.assign(base, { text: 'Text', color: '#e5e7eb' }) }
  if (type === 'timer') { Object.assign(base, { seconds: 30 }) }
  if (type === 'image') { Object.assign(base, { w: 240, h: 160, url: '', fit: 'contain', opacity: 1 }) }
  if (type === 'marker') { Object.assign(base, { w: 40, h: 40, marker: 'star', color: undefined }) }
  if (type === 'arrow') { Object.assign(base, { w: 160, h: 40, color: '#e5e7eb', thickness: 4, head: 12, twoHeads: false }) }
  if (type === 'icon') { Object.assign(base, { w: 52, h: 52, iconId: raidMarkerIcons[0]?.id || 'star', tint: undefined }) }
  if (type === 'player') { Object.assign(base, { w: 52, h: 52, showName: false, showRole: false }) }
  Object.assign(base, overrides)
  shapes.push(base)
  updateBlockData(block, { shapes, selectedShapeId: id })
}
function removeShape(block: RaidPlanBlock, id: string) {
  const shapes = ([...(block.data?.shapes as any[] || [])]).filter((s: any) => s.id !== id)
  const sel = (block.data as any)?.selectedShapeId
  updateBlockData(block, { shapes, selectedShapeId: sel === id ? undefined : sel })
}

function addRaidMarkerShape(block: RaidPlanBlock, markerId: string) {
  const icon = raidMarkerIcons.find((m) => m.id === markerId) || raidMarkerIcons[0]
  addShape(block, 'icon', { iconId: icon?.id, tint: icon?.color })
}

function addPlayerToken(block: RaidPlanBlock, charId: string) {
  const character = getCharacterById(charId)
  if (!character) return
  addShape(block, 'player', {
    w: 52,
    h: 52,
    showName: false,
    showRole: false,
    characterId: charId,
    playerName: character.name,
    label: character.name,
    role: character.role,
    className: character.class,
  })
}

function addGenericAssignment(block: RaidPlanBlock, token: { label: string; role: string; tint: string }) {
  addShape(block, 'player', {
    w: 52,
    h: 52,
    showName: true,
    showRole: true,
    playerName: token.label,
    label: token.label,
    role: token.role,
    tint: token.tint,
  })
}

function toggleMarkerPalette(blockId: string) {
  activeMarkerPaletteId.value = activeMarkerPaletteId.value === blockId ? null : blockId
}

function togglePlayerPalette(blockId: string) {
  const next = activePlayerPaletteId.value === blockId ? null : blockId
  activePlayerPaletteId.value = next
  if (!next) playerSearch.value = ''
}

function toggleMapPicker(blockId: string) {
  const next = activeMapPickerId.value === blockId ? null : blockId
  activeMapPickerId.value = next
  if (!next) mapSearch.value = ''
}
function selectShape(block: RaidPlanBlock, id: string) {
  updateBlockData(block, { selectedShapeId: id })
}
function currentShape(block: RaidPlanBlock): any | null {
  const id = (block.data as any)?.selectedShapeId
  const arr = (block.data?.shapes as any[] || [])
  return arr.find((s: any) => s.id === id) || null
}
function patchShape(block: RaidPlanBlock, patch: Record<string, any>) {
  const id = (block.data as any)?.selectedShapeId
  if (!id) return
  const shapes = ([...(block.data?.shapes as any[] || [])]).map((s: any) => s.id === id ? { ...s, ...patch } : s)
  updateBlockData(block, { shapes })
}
function shapeStyle(s: any): Record<string, string> {
  const style: Record<string, string> = {
    left: (s.x || 0) + 'px',
    top: (s.y || 0) + 'px',
    width: (s.w || 60) + 'px',
    height: (s.h || 40) + 'px',
    transform: `rotate(${Number(s.rotation||0)}deg)`,
    transformOrigin: 'center',
    backgroundColor: 'transparent',
    border: 'none',
  }
  if (s.type === 'rect') {
    style.backgroundColor = s.color || '#64748b'
  }
  if (s.type === 'circle') {
    style.backgroundColor = s.color || 'rgba(59,130,246,0.15)'
    style.border = `${s.borderWidth || 3}px solid ${s.border || '#3b82f6'}`
    style.borderRadius = '9999px'
  }
  if (s.type === 'text' || s.type === 'timer') {
    style.border = '1px dashed ' + (s.color || '#64748b')
  }
  if (s.type === 'player' || s.type === 'icon') {
    style.borderRadius = '9999px'
  }
  return style
}

function arrowRotateHandleStyle(s: any): Record<string, string> {
  const w = Number(s.w || 60)
  const angle = Number(s.rotation || 0)
  const offset = (w / 2) + 10
  return {
    left: '50%',
    top: '50%',
    transform: `translate(-50%, -50%) rotate(${angle}deg) translate(${offset}px, -20px)`,
  }
}

function arrowRotateLineStyle(s: any): Record<string, string> {
  const w = Number(s.w || 60)
  const angle = Number(s.rotation || 0)
  const offset = w / 2
  return {
    left: '50%',
    top: '50%',
    width: '2px',
    height: '20px',
    transform: `translate(-50%, -50%) rotate(${angle}deg) translate(${offset}px, -10px)`
  }
}
const MARKER_COLORS: Record<string, string> = { star: '#fbbf24', circle: '#fb923c', diamond: '#a855f7', triangle: '#22c55e', moon: '#93c5fd', square: '#3b82f6', cross: '#ef4444', skull: '#e5e7eb' }
const MARKER_SYMBOLS: Record<string, string> = { star: '✦', circle: '●', diamond: '◆', triangle: '▲', moon: '☾', square: '■', cross: '✖', skull: '☠' }
function markerColor(kind: string): string { return MARKER_COLORS[kind] || '#94a3b8' }
function markerSymbol(kind: string): string { return MARKER_SYMBOLS[kind] || '●' }
function raidMarkerSvg(iconId: string | undefined): string | null {
  if (!iconId) return null
  const icon = raidMarkerMap.get(iconId)
  return icon?.svg ?? null
}

function roleEmoji(role?: string | null): string {
  if (!role) return ''
  if (role === 'Tanks') return '🛡️'
  if (role === 'Healers') return '✚'
  if (role === 'Melee') return '⚔️'
  if (role === 'Ranged') return '🏹'
  return ''
}

function playerTokenColor(shape: any): string {
  if (shape.characterId) {
    const char = getCharacterById(shape.characterId)
    const color = getClassColor(char?.class)
    if (color) return color
  }
  if (shape.tint) return shape.tint
  if (shape.role && ROLE_COLORS[shape.role as Role]) {
    return ROLE_COLORS[shape.role as Role]
  }
  return '#475569'
}

function playerDisplayName(shape: any): string {
  if (shape.playerName) return shape.playerName
  if (shape.characterId) {
    const c = getCharacterById(shape.characterId)
    if (c?.name) return c.name
  }
  return shape.label || 'Player'
}
function formatTimer(sec: number): string {
  const m = Math.floor(sec / 60)
  const s = sec % 60
  return String(m).padStart(1,'0') + ':' + String(s).padStart(2,'0')
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
  const prev = rows.find((r: any) => r.id === rowId)
  const nextRows = rows.map((r: any) => r.id === rowId ? { ...r, ...patch } : r)

  // If switching mode, convert existing cell shapes accordingly
  if (patch.hasOwnProperty('mode') && prev && prev.mode !== patch.mode) {
    const nextMode = patch.mode as string
    const cells = ensureCells(block)
    const rowCells = cells[rowId] ?? (cells[rowId] = {})
    for (const colId of Object.keys(rowCells)) {
      const cur = rowCells[colId]
      if (nextMode === 'pair') {
        if (Array.isArray(cur)) {
          const from = cur[0] || null
          const to = cur[1] || null
          rowCells[colId] = { from, to } as any
        } else if (typeof cur === 'string') {
          rowCells[colId] = { from: cur, to: null } as any
        }
      } else {
        // list/single mode
        if (cur && typeof cur === 'object' && !Array.isArray(cur)) {
          const from = (cur as any).from || null
          const to = (cur as any).to || null
          rowCells[colId] = [from, to].filter(Boolean) as any
        }
      }
    }
    updateBlockData(block, { rows: nextRows, cells })
    return
  }

  updateBlockData(block, { rows: nextRows })
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

function characterInitials(char: Character | { name?: string } | undefined): string {
  const name = char?.name
  if (!name) return '?'
  const parts = name.split(' ')
  if (parts.length >= 2) {
    return (parts[0][0] + parts[1][0]).toUpperCase()
  }
  return name.slice(0, 2).toUpperCase()
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
  if (props.readonly) return;
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
  if (props.readonly) return;
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
  if (props.readonly) return;
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
  if (props.readonly) return;
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
  if (props.readonly) return;
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
  if (props.readonly) return;
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
    const target = groups.slice().sort((a,b) => a.members.length - b.members.length).find(g => g.members.length < max);
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
      <div class="max-w-2xl space-y-5">
        <div class="text-6xl">🛠️</div>
        <h2 class="text-2xl font-bold text-slate-200">Build Your Raid Plan</h2>
        <p class="text-slate-300 text-sm leading-relaxed">
          This canvas lets you compose your raid strategy using blocks: Groups, Role Matrix, Boss Assignments,
          Rotations, and more. Add blocks from the left, then drag players from the roster on the right.
        </p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-left">
          <div class="bg-slate-950/60 rounded-lg p-4 border border-slate-800">
            <div class="text-xs font-semibold text-emerald-400 uppercase tracking-wide mb-2">Step-by-step</div>
            <ol class="list-decimal list-inside space-y-1 text-sm text-slate-300">
              <li>Add <strong>Raid Groups</strong> to structure your roster (auto full width).</li>
              <li>Choose a <strong>Preset</strong> in Tools → Presets (Raid 40/25/20/10).</li>
              <li>Drag & drop players into groups; adjust specs per block if needed.</li>
              <li>Add <strong>Boss Assignments</strong> and <strong>Rotations</strong> for specific encounters.</li>
              <li>Use <strong>Smart Assign</strong> (Tools) to auto-fill Groups/Matrix intelligently.</li>
              <li>Click <strong>Save</strong> and <strong>Share</strong> when ready.</li>
            </ol>
          </div>
          <div class="bg-slate-950/60 rounded-lg p-4 border border-slate-800">
            <div class="text-xs font-semibold text-blue-400 uppercase tracking-wide mb-2">Tips</div>
            <ul class="space-y-1 text-sm text-slate-300">
              <li>Fixed-width blocks (Groups/Matrix/Rotations/Boss) always span 12 for clarity.</li>
              <li>Use the <strong>Tools</strong> menu for Templates, Presets, Export, and History.</li>
              <li><strong>Bench</strong> a player to hide them from the roster and prevent assignments.</li>
              <li>Preview the <strong>public view</strong> from Tools to share a read-only link.</li>
              <li>Autosave keeps changes locally; use <strong>Save</strong> to persist on the server.</li>
            </ul>
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
      :disabled="props.readonly === true"
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
              v-if="!props.readonly && selectedBlockId === block.id && block.colStart > 1 && !isFixedWidth(block)"
              class="absolute left-0 top-0 bottom-0 w-2 cursor-ew-resize hover:bg-emerald-500/30 transition-colors flex items-center justify-center group/handle"
              @mousedown="onResizeLeft(block, $event)"
              title="Resize left edge"
            >
              <div class="w-1 h-8 bg-emerald-500/60 rounded-full opacity-0 group-hover/handle:opacity-100 transition-opacity"></div>
            </div>
            <div
              v-if="!props.readonly && selectedBlockId === block.id && block.colStart + block.colSpan - 1 < 12 && !isFixedWidth(block)"
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
              v-if="!props.readonly && selectedBlockId === block.id"
              class="mb-2 flex flex-wrap items-center gap-3 text-[11px] text-slate-300"
            >
              <div v-if="!isFixedWidth(block)" class="flex items-center gap-1">
                <span class="uppercase tracking-wide text-slate-500">Start</span>
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

              <div v-if="!isFixedWidth(block)" class="flex items-center gap-1">
                <span class="uppercase tracking-wide text-slate-500">Span</span>

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
                <div class="h-[260px] overflow-auto pr-1">
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
                <div class="h-[260px] overflow-auto pr-1">
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
                      <div v-if="!g.members.length" class="text-[11px] text-slate-500 text-center py-1">Drag players here</div>
                    </div>
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
                <div class="overflow-auto h-[300px]">
                  <table class="w-full text-xs border-separate border-spacing-y-1">
                    <thead class="sticky top-0 z-10">
                      <tr class="bg-slate-900/80 backdrop-blur">
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
                <div class="overflow-auto h-[300px]">
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
                <div class="overflow-auto h-[260px]">
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


              
              <div v-else-if="block.type === 'BOSS_GRID'" class="space-y-2">
                <div class="flex items-center justify-between gap-2">
                  <div class="text-[11px] text-slate-400">Define boss positions and assign players</div>
                  <div class="flex items-center gap-1">
                    <button class="text-[11px] px-2 py-1 rounded-md border border-slate-700 hover:bg-slate-800" @click.stop="addPosition(block)">+ Add position</button>
                  </div>
                </div>
                <div class="overflow-auto h-[260px]">
                  <table class="w-full text-xs border border-slate-700">
                    <thead class="sticky top-0 z-10 bg-slate-900/80 backdrop-blur">
                      <tr>
                        <th class="p-2 text-left w-6"></th>
                        <th class="p-2 text-left w-48 border-l border-slate-700">Position</th>
                        <th class="p-2 text-left w-32 border-l border-slate-700">Role</th>
                        <th class="p-2 text-left border-l border-slate-700">Assignees</th>
                        <th class="p-2 text-left w-48 border-l border-slate-700">Notes</th>
                        <th class="p-2 text-left w-10 border-l border-slate-700">✕</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="(pos, idx) in (block.data?.positions ?? [])" :key="pos.id" class="odd:bg-slate-900/40">
                        <td class="p-2 align-top text-slate-500">
                          <div class="flex flex-col gap-1">
                            <button class="text-[10px]" title="Up" @click.stop="(function(){ const arr=[...(block.data?.positions||[])]; if(idx>0){ const tmp=arr[idx-1]; arr[idx-1]=arr[idx]; arr[idx]=tmp; updateBlockData(block,{ positions: arr }) } })()">↑</button>
                            <button class="text-[10px]" title="Down" @click.stop="(function(){ const arr=[...(block.data?.positions||[])]; if(idx<arr.length-1){ const tmp=arr[idx+1]; arr[idx+1]=arr[idx]; arr[idx]=tmp; updateBlockData(block,{ positions: arr }) } })()">↓</button>
                          </div>
                        </td>
                        <td class="p-2 align-top border-l border-slate-700">
                          <input class="w-full bg-slate-900 border border-slate-700 rounded px-2 py-1 text-[11px]" :value="pos.label" @input="updatePositionLabel(block, pos.id, ($event.target as HTMLInputElement).value)" placeholder="e.g. Soak 1"/>
                        </td>
                        <td class="p-2 align-top border-l border-slate-700">
                          <select class="w-full bg-slate-900 border border-slate-700 rounded px-2 py-1 text-[11px]" :value="(pos as any).role || 'Any'" @change="(function(v){ const positions=[...(block.data?.positions||[])].map(p=> p.id===pos.id? { ...p, role: (v.target as HTMLSelectElement).value }: p); updateBlockData(block,{ positions }) })($event)">
                            <option value="Any">Any</option>
                            <option value="Tanks">Tanks</option>
                            <option value="Healers">Healers</option>
                            <option value="Melee">Melee</option>
                            <option value="Ranged">Ranged</option>
                          </select>
                        </td>
                        <td class="p-2 align-top border-l border-slate-700">
                          <div class="min-h-10 rounded bg-slate-900/40 p-1 space-y-1"
                               @dragover.prevent
                               @drop.prevent="onDropToPosition(block, pos.id, $event)">
                            <div v-for="cid in (block.data?.assignments?.[pos.id] ?? [])" :key="cid" class="flex items-center justify-between rounded px-2 py-1" :style="{ backgroundColor: charColorById(cid) + '22', border: '1px solid ' + charColorById(cid) + '55' }">
                              <div class="truncate font-medium" :style="{ color: charColorById(cid) }">{{ getCharacterById(cid)?.name ?? 'Unknown' }}</div>
                              <button class="text-red-400 hover:text-red-300" @click.stop="removeFromPosition(block, pos.id, cid)" title="Remove">✕</button>
                            </div>
                            <div v-if="!(block.data?.assignments?.[pos.id]?.length)" class="text-[11px] text-slate-500 text-center py-1">Drag players here</div>
                          </div>
                        </td>
                        <td class="p-2 align-top border-l border-slate-700">
                          <input class="w-full bg-slate-900 border border-slate-700 rounded px-2 py-1 text-[11px]" :value="(((block.data?.positionNotes||{}) as any)[pos.id]?.[0] || '')" @input="(function(ev){ const txt=(ev.target as HTMLInputElement).value; const notes=Object.assign({}, (block.data?.positionNotes||{})); notes[pos.id]= txt? [txt]: []; updateBlockData(block,{ positionNotes: notes }) })($event)" placeholder="Notes (optional)"/>
                        </td>
                        <td class="p-2 align-top border-l border-slate-700">
                          <button class="text-red-400 hover:text-red-300" title="Remove position" @click.stop="removePosition(block, pos.id)">✕</button>
                        </td>
                      </tr>
                      <tr v-if="!(block.data?.positions?.length)" class="text-slate-500">
                        <td colspan="6" class="p-4 text-center">No positions yet — click “+ Add position”</td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              
              <div v-else-if="block.type === 'FREE_CANVAS'" class="space-y-3">
                <div v-if="selectedBlockId === block.id" class="flex flex-wrap items-center gap-2 text-[11px] text-slate-400">
                  <div class="flex flex-wrap gap-1">
                    <button class="px-2 py-0.5 rounded-md border border-slate-700 hover:bg-slate-800" @click.stop="addShape(block, 'rect')">▭ Rectangle</button>
                    <button class="px-2 py-0.5 rounded-md border border-slate-700 hover:bg-slate-800" @click.stop="addShape(block, 'circle')">◯ Circle</button>
                    <button class="px-2 py-0.5 rounded-md border border-slate-700 hover:bg-slate-800" @click.stop="addShape(block, 'text')">✎ Texte</button>
                    <button class="px-2 py-0.5 rounded-md border border-slate-700 hover:bg-slate-800" @click.stop="addShape(block, 'timer')">⏱ Timer</button>
                    <button class="px-2 py-0.5 rounded-md border border-slate-700 hover:bg-slate-800" @click.stop="addShape(block, 'image')">🖼️ Image</button>
                    <button class="px-2 py-0.5 rounded-md border border-slate-700 hover:bg-slate-800" @click.stop="addShape(block, 'marker')">⚑ Texte marker</button>
                    <button class="px-2 py-0.5 rounded-md border border-slate-700 hover:bg-slate-800" @click.stop="addShape(block, 'arrow')">➤ Flèche</button>
                  </div>
                  <div class="relative">
                    <button class="px-2 py-0.5 rounded-md border border-blue-600/40 bg-blue-900/20 hover:bg-blue-900/40" @click.stop="toggleMarkerPalette(block.id)">🎯 Raid markers ▾</button>
                    <div v-if="activeMarkerPaletteId === block.id" class="absolute left-0 mt-2 w-64 rounded-lg border border-slate-700 bg-slate-900/95 shadow-xl z-40 p-2">
                      <div class="grid grid-cols-4 gap-2">
                        <button
                          v-for="icon in raidMarkerIcons"
                          :key="icon.id"
                          class="h-14 w-14 rounded-md border border-slate-700/60 hover:border-emerald-500/60 bg-slate-800/60"
                          v-html="icon.svg"
                          @click.stop="addRaidMarkerShape(block, icon.id)"
                          title="Add {{ icon.label }}"
                        ></button>
                      </div>
                    </div>
                  </div>
                  <div class="relative">
                    <button class="px-2 py-0.5 rounded-md border border-emerald-600/40 bg-emerald-900/20 hover:bg-emerald-900/40" @click.stop="togglePlayerPalette(block.id)">🧙 Ajouter joueurs ▾</button>
                    <div v-if="activePlayerPaletteId === block.id" class="absolute left-0 mt-2 w-72 rounded-lg border border-slate-700 bg-slate-900/95 shadow-xl z-40 p-2 space-y-2">
                      <template v-if="props.characters?.length">
                        <input type="text" v-model="playerSearch" placeholder="Rechercher joueur ou classe" class="w-full bg-slate-900 border border-slate-700 rounded px-2 py-1 text-[11px] focus:outline-none focus:ring-1 focus:ring-emerald-500" />
                        <div class="max-h-64 overflow-auto space-y-1 pr-1">
                          <button
                            v-for="char in filteredCharacters"
                            :key="char.id"
                            class="w-full flex items-center gap-2 rounded-md border border-slate-800 bg-slate-900/60 px-2 py-1 hover:border-emerald-500/40"
                            @click.stop="addPlayerToken(block, char.id)"
                          >
                            <span class="w-7 h-7 rounded-full flex items-center justify-center text-[10px] font-semibold"
                                  :style="{ backgroundColor: getClassColor(char.class) || '#475569', color: '#0f172a', border: '2px solid #0f172a' }">
                              {{ characterInitials(char) }}
                            </span>
                            <div class="text-left">
                              <div class="text-slate-200">{{ char.name }}</div>
                              <div class="text-[10px] text-slate-500">{{ char.class }} • {{ char.role }}</div>
                            </div>
                          </button>
                        </div>
                      </template>
                      <template v-else>
                        <div class="text-[11px] text-slate-400">Aucun roster importé — utilisez les tokens génériques ci-dessous.</div>
                      </template>
                    </div>
                  </div>
                  <div class="relative">
                    <button class="px-2 py-0.5 rounded-md border border-purple-600/40 bg-purple-900/20 hover:bg-purple-900/40" @click.stop="toggleMapPicker(block.id)">🗺️ Cartes ▾</button>
                    <div v-if="activeMapPickerId === block.id" class="absolute left-0 mt-2 w-80 rounded-lg border border-slate-700 bg-slate-900/95 shadow-xl z-40 p-2 space-y-2">
                      <input type="text" v-model="mapSearch" placeholder="Rechercher raid ou forme" class="w-full bg-slate-900 border border-slate-700 rounded px-2 py-1 text-[11px] focus:outline-none focus:ring-1 focus:ring-purple-500" />
                      <div class="max-h-64 overflow-auto grid grid-cols-2 gap-2 pr-1">
                        <button
                          v-for="map in filteredMaps"
                          :key="map.id"
                          class="rounded-md border border-slate-800 bg-slate-900/70 p-2 text-left hover:border-purple-500/50"
                          @click.stop="patchCanvasBackground(block, { mapId: map.id })"
                        >
                          <div class="text-xs font-semibold text-slate-100">{{ map.name }}</div>
                          <div class="text-[10px] text-slate-500">{{ map.raid }}</div>
                        </button>
                      </div>
                      <div class="flex justify-end text-[10px]">
                        <button class="px-2 py-0.5 rounded border border-slate-700 hover:bg-slate-800" @click.stop="clearCanvasMap(block)">Clear map</button>
                      </div>
                    </div>
                  </div>
                  <div class="flex flex-wrap gap-2 text-[10px] text-slate-400 items-center">
                    <span class="uppercase tracking-wide text-slate-500">Boss</span>
                    <button
                      v-for="token in genericAssignmentTokens.boss"
                      :key="token.id"
                      class="px-2 py-0.5 rounded-md border border-slate-700 bg-slate-900/60 hover:border-emerald-500/60"
                      @click.stop="addGenericAssignment(block, token)"
                    >{{ token.label }}</button>
                  </div>
                  <div class="flex flex-wrap gap-2 text-[10px] text-slate-400 items-center">
                    <span class="uppercase tracking-wide text-slate-500">Adds</span>
                    <button
                      v-for="token in genericAssignmentTokens.adds"
                      :key="token.id"
                      class="px-2 py-0.5 rounded-md border border-slate-700 bg-slate-900/60 hover:border-emerald-500/60"
                      @click.stop="addGenericAssignment(block, token)"
                    >{{ token.label }}</button>
                  </div>
                  <label class="inline-flex items-center gap-1 text-slate-400">
                    Snap
                    <input type="number" min="1" max="64" class="w-16 bg-slate-900 border border-slate-700 rounded px-1 py-0.5 text-[11px]" :value="(block.data as any)?.canvasSnap ?? 8" @input="updateBlockData(block, { canvasSnap: Math.max(1, Number(($event.target as HTMLInputElement).value || 1)) })" />
                  </label>
                  <label class="inline-flex items-center gap-1 text-slate-400">
                    Height
                    <input class="w-20 bg-slate-900 border border-slate-700 rounded px-1 py-0.5" :value="block.data?.canvasHeight || 280" @input="updateBlockData(block, { canvasHeight: Number(($event.target as HTMLInputElement).value || 0) })" />
                  </label>
                  <label class="inline-flex items-center gap-1 text-slate-400">
                    <input type="checkbox" :checked="ensureCanvasBackground(block).showCenter" @change="patchCanvasBackground(block, { showCenter: ($event.target as HTMLInputElement).checked })" /> Guides centre
                  </label>
                </div>
                <div class="relative border border-dashed border-slate-700 rounded-md overflow-hidden"
                     :style="canvasBackgroundStyle(block)"
                     :ref="(el) => setCanvasRef(block.id, el)">
                  <div v-if="canvasBackgroundImage(block)" class="absolute inset-0 pointer-events-none">
                    <img :src="canvasBackgroundImage(block)!.url" alt="canvas background" class="w-full h-full object-cover" :style="{ opacity: canvasBackgroundImage(block)!.opacity }" />
                  </div>
                  <div v-if="canvasMapSvg(block)" class="absolute inset-0 pointer-events-none opacity-80" v-html="canvasMapSvg(block)"></div>
                  <div v-if="ensureCanvasBackground(block).showCenter" class="absolute inset-0 pointer-events-none">
                    <div class="absolute inset-x-10 top-1/2 border-t border-dashed border-slate-500/40"></div>
                    <div class="absolute inset-y-10 left-1/2 border-l border-dashed border-slate-500/40"></div>
                  </div>
                  <div v-for="s in (block.data?.shapes || [])" :key="s.id"
                       class="absolute rounded cursor-move group"
                       :style="shapeStyle(s)"
                       @mousedown.stop="!props.readonly && !(s as any).locked && onShapeMouseDown(block, s.id, $event)"
                       @click.stop="selectShape(block, s.id)">
                    <template v-if="s.type === 'rect'">
                      <div class="w-full h-full"></div>
                    </template>
                    <template v-else-if="s.type === 'circle'">
                      <div class="w-full h-full rounded-full"></div>
                    </template>
                    <template v-else-if="s.type === 'text'">
                      <div class="w-full h-full grid place-items-center text-xs" :style="{ color: s.color || '#e5e7eb' }">{{ s.text || 'Text' }}</div>
                    </template>
                    <template v-else-if="s.type === 'timer'">
                      <div class="w-full h-full grid place-items-center text-[11px] text-slate-200">⏱ {{ formatTimer(s.seconds || 0) }}</div>
                    </template>
                    <template v-else-if="s.type === 'image'">
                      <div class="w-full h-full overflow-hidden rounded">
                        <img :src="s.url || ''" alt="" class="w-full h-full" :style="{ objectFit: s.fit || 'contain', opacity: (typeof s.opacity==='number'? s.opacity : 1) }"/>
                      </div>
                    </template>
                    <template v-else-if="s.type === 'marker'">
                      <div class="w-full h-full grid place-items-center font-semibold"
                           :style="{ color: (s.color || markerColor(s.marker || 'star')), fontSize: (Math.min(Number(s.w||60), Number(s.h||40)) * 0.6) + 'px', lineHeight: 1 }">{{ markerSymbol(s.marker || 'star') }}</div>
                    </template>
                    <template v-else-if="s.type === 'icon'">
                      <div class="w-full h-full grid place-items-center" v-html="raidMarkerSvg(s.iconId) || ''"></div>
                    </template>
                    <template v-else-if="s.type === 'player'">
                      <div class="w-full h-full flex flex-col items-center justify-center gap-1 text-[10px]">
                        <div class="w-10 h-10 rounded-full border-2 flex items-center justify-center text-[11px] font-semibold"
                             :style="{ backgroundColor: playerTokenColor(s), borderColor: '#0f172a', color: '#0f172a' }"
                             :title="playerDisplayName(s)">
                          {{ characterInitials(getCharacterById(s.characterId) || { name: playerDisplayName(s) }) }}
                        </div>
                        <div v-if="s.showName" class="text-slate-200">{{ playerDisplayName(s) }}</div>
                        <div v-if="s.showRole" class="text-slate-400">{{ roleEmoji(s.role || getCharacterById(s.characterId)?.role) }}</div>
                      </div>
                    </template>
                    <template v-else-if="s.type === 'arrow'">
                      <svg :width="s.w || 60" :height="s.h || 40" class="block">
                        <defs>
                          <marker :id="'arrowhead-end-' + s.id" markerWidth="10" markerHeight="10" refX="9" refY="3" orient="auto" markerUnits="strokeWidth">
                            <path d="M0,0 L0,6 L9,3 z" :fill="s.color || '#e5e7eb'"></path>
                          </marker>
                          <marker :id="'arrowhead-start-' + s.id" markerWidth="10" markerHeight="10" refX="0" refY="3" orient="auto" markerUnits="strokeWidth">
                            <path d="M9,0 L9,6 L0,3 z" :fill="s.color || '#e5e7eb'"></path>
                          </marker>
                        </defs>
                        <line :x1="(s.twoHeads? 10: 0) + 5" :y1="(s.h||40)/2" :x2="(s.w||60) - 5 - (s.twoHeads? 10: 0)" :y2="(s.h||40)/2"
                              :stroke="s.color || '#e5e7eb'" :stroke-width="s.thickness || 4"
                              :marker-end="'url(#arrowhead-end-' + s.id + ')'"
                              :marker-start="s.twoHeads ? ('url(#arrowhead-start-' + s.id + ')') : undefined" />
                      </svg>
                    </template>
                    <!-- Resize handles -->
                    <template v-if="!props.readonly && (block.data?.selectedShapeId === s.id)">
                      <div class="absolute -top-1 -left-1 h-2.5 w-2.5 rounded bg-emerald-500 cursor-nwse-resize" @mousedown.stop="onShapeResizeMouseDown(block, s.id, 'resize-nw', $event)"></div>
                      <div class="absolute -top-1 -right-1 h-2.5 w-2.5 rounded bg-emerald-500 cursor-nesw-resize" @mousedown.stop="onShapeResizeMouseDown(block, s.id, 'resize-ne', $event)"></div>
                      <div class="absolute -bottom-1 -left-1 h-2.5 w-2.5 rounded bg-emerald-500 cursor-nesw-resize" @mousedown.stop="onShapeResizeMouseDown(block, s.id, 'resize-sw', $event)"></div>
                      <div class="absolute -bottom-1 -right-1 h-2.5 w-2.5 rounded bg-emerald-500 cursor-nwse-resize" @mousedown.stop="onShapeResizeMouseDown(block, s.id, 'resize-se', $event)"></div>
                      <template v-if="s.type === 'arrow'">
                        <div class="absolute bg-emerald-500/70" style="width:2px" :style="arrowRotateLineStyle(s)"></div>
                        <div class="absolute w-3 h-3 rounded-full bg-emerald-500 cursor-grab" :style="arrowRotateHandleStyle(s)" @mousedown.stop="onShapeRotateMouseDown(block, s.id, $event)"></div>
                      </template>
                      <template v-else>
                        <div class="absolute -top-5 left-1/2 -translate-x-1/2 w-3 h-3 rounded-full bg-emerald-500 cursor-grab" @mousedown.stop="onShapeRotateMouseDown(block, s.id, $event)"></div>
                        <div class="absolute -top-2 left-1/2 -translate-x-1/2 w-0.5 h-2 bg-emerald-500"></div>
                      </template>
                    </template>
                  </div>
                </div>
                <div v-if="selectedBlockId === block.id" class="grid grid-cols-1 md:grid-cols-2 gap-2 text-[11px] text-slate-300">
                  <div>
                    <div class="text-slate-400 mb-1">Shapes</div>
                    <div class="space-y-1 max-h-40 overflow-auto pr-1">
                      <div v-for="(s, si) in (block.data?.shapes || [])" :key="s.id"
                           class="flex items-center justify-between rounded border border-slate-700 bg-slate-900/60 px-2 py-1"
                           :class="(block.data?.selectedShapeId === s.id) ? 'ring-1 ring-emerald-500/50' : ''"
                           @click.stop="selectShape(block, s.id)">
                        <span class="truncate">{{ s.type.toUpperCase() }} • {{ s.id.slice(-4) }}</span>
                        <div class="flex items-center gap-1">
                          <button class="text-slate-400 hover:text-slate-200" title="Up" @click.stop="(function(){ const arr=[...(block.data?.shapes||[])]; if(si>0){ const tmp=arr[si-1]; arr[si-1]=arr[si]; arr[si]=tmp; updateBlockData(block,{ shapes: arr }) } })()">↑</button>
                          <button class="text-slate-400 hover:text-slate-200" title="Down" @click.stop="(function(){ const arr=[...(block.data?.shapes||[])]; if(si<arr.length-1){ const tmp=arr[si+1]; arr[si+1]=arr[si]; arr[si]=tmp; updateBlockData(block,{ shapes: arr }) } })()">↓</button>
                          <button class="text-slate-400 hover:text-slate-200" title="Duplicate" @click.stop="(function(){ const arr=[...(block.data?.shapes||[])]; const idx=arr.findIndex(x=>x.id===s.id); const cp={...arr[idx], id: ('sh' + Date.now() + '-' + String(Math.random()).slice(2,6))}; arr.splice(idx+1,0,cp); updateBlockData(block,{ shapes: arr, selectedShapeId: cp.id }) })()">⧉</button>
                          <button class="text-slate-400 hover:text-slate-200" :title="(s as any).locked ? 'Unlock' : 'Lock'" @click.stop="patchShape(block, { locked: !(s as any).locked })">{{ (s as any).locked ? '🔒' : '🔓' }}</button>
                          <button class="text-red-400 hover:text-red-300" title="Delete" @click.stop="removeShape(block, s.id)">✕</button>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div>
                    <div class="text-slate-400 mb-1">Properties</div>
                    <template v-if="currentShape(block)">
                      <div class="grid grid-cols-2 gap-2">
                        <label class="text-slate-400">X <input class="w-full bg-slate-900 border border-slate-700 rounded px-1 py-0.5" :value="currentShape(block)!.x || 0" @input="patchShape(block, { x: Number(($event.target as HTMLInputElement).value || 0) })"/></label>
                        <label class="text-slate-400">Y <input class="w-full bg-slate-900 border border-slate-700 rounded px-1 py-0.5" :value="currentShape(block)!.y || 0" @input="patchShape(block, { y: Number(($event.target as HTMLInputElement).value || 0) })"/></label>
                        <label class="text-slate-400">W <input class="w-full bg-slate-900 border border-slate-700 rounded px-1 py-0.5" :value="currentShape(block)!.w || 60" @input="patchShape(block, { w: Number(($event.target as HTMLInputElement).value || 0) })"/></label>
                        <label class="text-slate-400">H <input class="w-full bg-slate-900 border border-slate-700 rounded px-1 py-0.5" :value="currentShape(block)!.h || 40" @input="patchShape(block, { h: Number(($event.target as HTMLInputElement).value || 0) })"/></label>
                        <label class="text-slate-400">Rotation° <input class="w-full bg-slate-900 border border-slate-700 rounded px-1 py-0.5" :value="currentShape(block)!.rotation || 0" @input="patchShape(block, { rotation: Number(($event.target as HTMLInputElement).value || 0) })"/></label>
                        <label v-if="currentShape(block)!.type !== 'timer' && currentShape(block)!.type !== 'player' && currentShape(block)!.type !== 'icon'" class="text-slate-400 col-span-2">Color <input type="color" class="ml-2" :value="currentShape(block)!.color || '#64748b'" @input="patchShape(block, { color: ($event.target as HTMLInputElement).value })"/></label>
                        <label v-if="currentShape(block)!.type === 'text'" class="text-slate-400 col-span-2">Text <input class="w-full bg-slate-900 border border-slate-700 rounded px-1 py-0.5" :value="currentShape(block)!.text || ''" @input="patchShape(block, { text: ($event.target as HTMLInputElement).value })"/></label>
                        <label v-if="currentShape(block)!.type === 'timer'" class="text-slate-400 col-span-2">Seconds <input class="w-28 bg-slate-900 border border-slate-700 rounded px-1 py-0.5" :value="currentShape(block)!.seconds || 30" @input="patchShape(block, { seconds: Number(($event.target as HTMLInputElement).value || 0) })"/></label>
                        <template v-if="currentShape(block)!.type === 'image'">
                          <label class="text-slate-400 col-span-2">Image URL <input class="w-full bg-slate-900 border border-slate-700 rounded px-1 py-0.5" :value="currentShape(block)!.url || ''" @input="patchShape(block, { url: ($event.target as HTMLInputElement).value })" placeholder="https://..."/></label>
                          <label class="text-slate-400">Fit
                            <select class="ml-2 bg-slate-900 border border-slate-700 rounded px-1 py-0.5" :value="currentShape(block)!.fit || 'contain'" @change="patchShape(block, { fit: ($event.target as HTMLSelectElement).value })">
                              <option value="contain">Contain</option>
                              <option value="cover">Cover</option>
                            </select>
                          </label>
                          <label class="text-slate-400">Opacity <input type="range" min="0" max="1" step="0.05" :value="currentShape(block)!.opacity ?? 1" @input="patchShape(block, { opacity: Number(($event.target as HTMLInputElement).value) })"/></label>
                        </template>
                        <template v-if="currentShape(block)!.type === 'marker'">
                          <label class="text-slate-400 col-span-2">Marker
                            <select class="ml-2 bg-slate-900 border border-slate-700 rounded px-1 py-0.5" :value="currentShape(block)!.marker || 'star'" @change="patchShape(block, { marker: ($event.target as HTMLSelectElement).value })">
                              <option value="star">⭐ Star</option>
                              <option value="circle">⭕ Circle</option>
                              <option value="diamond">◇ Diamond</option>
                              <option value="triangle">▲ Triangle</option>
                              <option value="moon">☽ Moon</option>
                              <option value="square">■ Square</option>
                              <option value="cross">✖ Cross</option>
                              <option value="skull">☠ Skull</option>
                            </select>
                          </label>
                        </template>
                        <template v-if="currentShape(block)!.type === 'icon'">
                          <label class="text-slate-400 col-span-2">Icone
                            <select class="ml-2 bg-slate-900 border border-slate-700 rounded px-1 py-0.5" :value="currentShape(block)!.iconId" @change="patchShape(block, { iconId: ($event.target as HTMLSelectElement).value })">
                              <option v-for="icon in raidMarkerIcons" :key="icon.id" :value="icon.id">{{ icon.label }}</option>
                            </select>
                          </label>
                        </template>
                        <template v-if="currentShape(block)!.type === 'player'">
                          <label class="inline-flex items-center gap-1 col-span-1"><input type="checkbox" :checked="currentShape(block)!.showName" @change="patchShape(block, { showName: ($event.target as HTMLInputElement).checked })"/> Nom</label>
                          <label class="inline-flex items-center gap-1 col-span-1"><input type="checkbox" :checked="currentShape(block)!.showRole" @change="patchShape(block, { showRole: ($event.target as HTMLInputElement).checked })"/> Rôle</label>
                          <label class="text-slate-400 col-span-2">Couleur <input type="color" class="ml-2" :value="currentShape(block)!.tint || '#475569'" @input="patchShape(block, { tint: ($event.target as HTMLInputElement).value })"/></label>
                        </template>
                        <template v-if="currentShape(block)!.type === 'arrow'">
                          <label class="text-slate-400">Épaisseur <input type="range" min="1" max="12" :value="currentShape(block)!.thickness || 4" @input="patchShape(block, { thickness: Number(($event.target as HTMLInputElement).value) })"/></label>
                          <label class="text-slate-400">Tête <input type="range" min="6" max="24" :value="currentShape(block)!.head || 12" @input="patchShape(block, { head: Number(($event.target as HTMLInputElement).value) })"/></label>
                          <label class="text-slate-400 inline-flex items-center gap-2">Deux pointes <input type="checkbox" :checked="currentShape(block)!.twoHeads || false" @change="patchShape(block, { twoHeads: ($event.target as HTMLInputElement).checked })"/></label>
                        </template>
                      </div>
                    </template>
                    <div v-else class="text-slate-500">Select a shape…</div>
                  </div>
                  <div class="md:col-span-2 border-t border-slate-800 pt-2">
                    <div class="text-slate-400 mb-1">Fond</div>
                    <div class="grid grid-cols-2 gap-2">
                      <label class="text-slate-400">Couleur
                        <input type="color" class="ml-2" :value="ensureCanvasBackground(block).color" @input="patchCanvasBackground(block, { color: ($event.target as HTMLInputElement).value })" />
                      </label>
                      <label class="text-slate-400">Grille
                        <input type="checkbox" class="ml-2" :checked="ensureCanvasBackground(block).grid" @change="patchCanvasBackground(block, { grid: ($event.target as HTMLInputElement).checked })" />
                      </label>
                      <label class="text-slate-400">Taille grille
                        <input type="number" min="4" max="64" class="ml-2 bg-slate-900 border border-slate-700 rounded px-1 py-0.5" :value="ensureCanvasBackground(block).gridSize" @input="patchCanvasBackground(block, { gridSize: Number(($event.target as HTMLInputElement).value || 8) })" />
                      </label>
                      <label class="text-slate-400 col-span-2">Image (URL)
                        <input type="text" class="w-full bg-slate-900 border border-slate-700 rounded px-2 py-1" :value="ensureCanvasBackground(block).imageUrl" @input="patchCanvasBackground(block, { imageUrl: ($event.target as HTMLInputElement).value })" placeholder="https://" />
                      </label>
                      <label class="text-slate-400">Opacité image
                        <input type="range" min="0" max="1" step="0.05" :value="ensureCanvasBackground(block).imageOpacity" @input="patchCanvasBackground(block, { imageOpacity: Number(($event.target as HTMLInputElement).value) })" />
                      </label>
                      <div class="text-right">
                        <button class="px-2 py-0.5 rounded border border-slate-700 hover:bg-slate-800" @click="clearCanvasImage(block)">Clear image</button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              
              <div v-else-if="block.type === 'BENCH_ROSTER'" class="space-y-2">
                <div class="text-[11px] text-slate-400">Drag players not in the main comp</div>
                <div
                  class="min-h-24 rounded border border-dashed border-slate-600 p-2 space-y-1"
                  @dragover.prevent
                  @drop.prevent="(e: DragEvent) => { if (props.readonly) return; const id = e.dataTransfer?.getData('text/plain'); if (!id) return; const bench = new Set<string>((block.data?.bench ?? []) as string[]); bench.add(id); updateBlockData(block, { bench: Array.from(bench) }); removeCharacterFromAllAssignments(id); }"
                >
                  <div
                    v-for="cid in (block.data?.bench ?? [])"
                    :key="cid"
                    class="flex items-center justify-between rounded bg-slate-800/70 px-2 py-1"
                  >
                    <div class="truncate">{{ getCharacterById(cid)?.name ?? 'Unknown' }}</div>
                    <button class="text-red-400 hover:text-red-300" @click.stop="updateBlockData(block, { bench: (block.data?.bench ?? []).filter((x: string) => x !== cid) })" title="Remove">✕</button>
                  </div>
                  <div v-if="!(block.data?.bench?.length)" class="text-[11px] text-slate-500 text-center py-1">Drag players here</div>
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
                <div class="overflow-auto h-[300px]">
                  <table class="min-w-full text-[11px] border border-slate-700">
                    <thead class="sticky top-0 z-10">
                      <tr class="bg-slate-900/80 backdrop-blur">
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
                              <div v-if="!getCell(block, row.id, col.id).length" class="text-center text-slate-500">Drag players here</div>
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
                <div class="overflow-auto h-[300px]">
                  <table class="min-w-full text-[11px] border border-slate-700">
                    <thead class="sticky top-0 z-10">
                      <tr class="bg-slate-900/80 backdrop-blur">
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
                            <div v-if="!getCell(block, row.id, col.id).length" class="text-center text-slate-500">Drag players here</div>
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
