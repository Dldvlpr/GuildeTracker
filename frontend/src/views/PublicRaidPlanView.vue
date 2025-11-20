<script setup lang="ts">
import { ref, onMounted, computed, type CSSProperties } from 'vue';
import { Role, ROLE_COLORS } from '@/interfaces/game.interface';
import { useRoute } from 'vue-router';
import type { RaidPlanBlock } from '@/interfaces/raidPlan.interface';
import { CANVAS_MAPS } from '@/data/canvasMaps';
import { RAID_MARKER_ICONS } from '@/data/raidMarkers';

type PublicRosterEntry = {
  name?: string;
  class?: string;
  spec?: string;
  role?: Role | string;
  color?: string;
};

type RosterValue = PublicRosterEntry | PublicRosterEntry[] | null | undefined;

type CanvasShape = {
  id?: string;
  type?: string;
  x?: number;
  y?: number;
  w?: number;
  h?: number;
  rotation?: number;
  color?: string;
  border?: string;
  borderWidth?: number;
  marker?: string;
  iconId?: string;
  text?: string;
  seconds?: number;
  url?: string;
  fit?: string;
  opacity?: number;
  playerName?: string;
  label?: string;
  tint?: string;
  role?: Role | string;
  showName?: boolean;
  showRole?: boolean;
  twoHeads?: boolean;
  thickness?: number;
};

type PairCellValue = { from?: string | null; to?: string | null };
type CellValue = string | string[] | PairCellValue | undefined;
type AssignmentCells = Record<string, Record<string, CellValue>>;

type PlanRow = {
  id: string;
  label: string;
  fromLabel?: string;
  toLabel?: string;
  mode?: string;
  type?: string;
  cells?: Record<string, CellValue>;
};

type BossPosition = { id: string; label: string; role?: string };

type PublicRaidPlanResponse = {
  name: string;
  guild: string;
  blocks: RaidPlanBlock[];
  createdAt: string;
  updatedAt: string;
  rosterByName?: Record<string, RosterValue>;
  roster?: Record<string, RosterValue>;
};

const route = useRoute();
const shareToken = route.params.shareToken as string;

const planName = ref('Loading...');
const guildName = ref('');
const blocks = ref<RaidPlanBlock[]>([]);
const loading = ref(true);
const error = ref<string | null>(null);
const createdAt = ref('');
const updatedAt = ref('');
const highlight = ref('');
const roster = ref<Record<string, RosterValue>>({});
const rosterByName = computed<Record<string, PublicRosterEntry>>(() => {
  const out: Record<string, PublicRosterEntry> = {};
  Object.entries(roster.value || {}).forEach(([rawKey, value]) => {
    if (!value) return;
    if (Array.isArray(value)) {
      const first = value.find((entry) => Boolean(entry));
      if (first) {
        const n = first.name ?? rawKey;
        out[n] = first;
      }
      return;
    }
    const n = value.name ?? rawKey;
    out[n] = value;
  });
  return out;
});
const canvasMaps = CANVAS_MAPS;
const raidMarkerIcons = RAID_MARKER_ICONS;
const mapById = new Map(canvasMaps.map((m) => [m.id, m]));
const raidMarkerMap = new Map(raidMarkerIcons.map((i) => [i.id, i]));
const MARKER_COLORS: Record<string, string> = { star: '#fbbf24', circle: '#fb923c', diamond: '#a855f7', triangle: '#22c55e', moon: '#93c5fd', square: '#3b82f6', cross: '#ef4444', skull: '#e5e7eb' };
const MARKER_SYMBOLS: Record<string, string> = { star: '✦', circle: '●', diamond: '◆', triangle: '▲', moon: '☾', square: '■', cross: '✖', skull: '☠' };
function markerColor(kind: string): string { return MARKER_COLORS[kind] || '#94a3b8' }
function markerSymbol(kind: string): string { return MARKER_SYMBOLS[kind] || '●' }
function roleColor(roleName?: string | null): string {
  if (roleName === Role.TANKS || roleName === Role.HEALERS || roleName === Role.MELEE || roleName === Role.RANGED) {
    return ROLE_COLORS[roleName];
  }
  return '#94a3b8';
}
function raidMarkerSvg(iconId: string | undefined): string | null {
  if (!iconId) return null;
  return raidMarkerMap.get(iconId)?.svg ?? null;
}
function roleEmoji(shapeRole?: string | null): string {
  if (!shapeRole) return ''
  if (shapeRole === Role.TANKS) return '🛡️'
  if (shapeRole === Role.HEALERS) return '✚'
  if (shapeRole === Role.MELEE) return '⚔️'
  if (shapeRole === Role.RANGED) return '🏹'
  return ''
}
function playerDisplayNamePublic(shape: CanvasShape): string {
  return shape.playerName || shape.label || 'Player';
}
function playerTokenColorPublic(shape: CanvasShape): string {
  const rosterEntry = shape.playerName ? rosterByName.value[shape.playerName] : undefined;
  if (rosterEntry?.color) return rosterEntry.color;
  if (shape.tint) return shape.tint;
  if (shape.role) return roleColor(String(shape.role));
  return '#475569';
}

function canvasImageStyle(s: CanvasShape): CSSProperties {
  return {
    objectFit: (s.fit as CSSProperties['objectFit']) || 'contain',
    opacity: typeof s.opacity === 'number' ? s.opacity : 1,
  };
}

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
  const src = (block.data?.canvasBackground as Partial<CanvasBackgroundState> | undefined) || {};
  const data = block.data as { grid?: unknown; gridSize?: unknown } | undefined;
  const gridFlag = typeof src.grid === 'boolean' ? src.grid : typeof data?.grid === 'boolean' ? data.grid : false;
  const gridSize = Math.max(4, Math.min(64, Number(src.gridSize ?? data?.gridSize ?? 8)));
  return {
    color: src.color || '#0f172a',
    grid: gridFlag,
    gridSize,
    imageUrl: src.imageUrl || '',
    imageOpacity: Math.min(1, Math.max(0, Number(src.imageOpacity ?? 0.5))),
    mapId: src.mapId || null,
    showCenter: Boolean(src.showCenter),
  }
}

function canvasBackgroundStyle(block: RaidPlanBlock): Record<string, string> {
  const bg = ensureCanvasBackground(block)
  const style: Record<string, string> = {
    height: `${block.data?.canvasHeight || 280}px`,
    backgroundColor: bg.color,
  }
  if (bg.grid) {
    style.backgroundImage = `linear-gradient(0deg, rgba(148,163,184,0.12) 1px, transparent 1px), linear-gradient(90deg, rgba(148,163,184,0.12) 1px, transparent 1px)`
    style.backgroundSize = `${bg.gridSize}px ${bg.gridSize}px`
  }
  return style
}

function canvasMapSvg(block: RaidPlanBlock): string | null {
  const bg = ensureCanvasBackground(block)
  if (!bg.mapId) return null
  return mapById.get(bg.mapId)?.svg ?? null
}

function canvasBackgroundImage(block: RaidPlanBlock): { url: string; opacity: number } | null {
  const bg = ensureCanvasBackground(block)
  if (!bg.imageUrl) return null
  return { url: bg.imageUrl, opacity: bg.imageOpacity }
}

function initials(name?: string): string {
  if (!name) return '?'
  const parts = name.split(' ')
  if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase()
  return name.slice(0, 2).toUpperCase()
}

function getBlockData(block: RaidPlanBlock): { rows: PlanRow[]; cells: AssignmentCells; positions: BossPosition[] } {
  const data = block.data as { rows?: unknown; cells?: unknown; positions?: unknown } | undefined;
  const rows = Array.isArray(data?.rows) ? (data.rows as PlanRow[]) : [];
  const cells = data?.cells && typeof data.cells === 'object' && !Array.isArray(data.cells) ? (data.cells as AssignmentCells) : {};
  const positions = Array.isArray(data?.positions) ? (data.positions as BossPosition[]) : [];
  return { rows, cells, positions };
}

function blockPositions(block: RaidPlanBlock): BossPosition[] {
  return getBlockData(block).positions;
}
const BASE = (import.meta.env.VITE_API_BASE_URL ?? '').replace(/\/$/, '');

async function loadPlan() {
  try {
    const res = await fetch(`${BASE}/api/raid-plans/public/${shareToken}`, {
      method: 'GET',
      headers: { Accept: 'application/json' },
    });

    if (!res.ok) {
      throw new Error('Plan not found or no longer public');
    }

    const data = (await res.json()) as PublicRaidPlanResponse;
    planName.value = data.name;
    guildName.value = data.guild;
    blocks.value = data.blocks;
    createdAt.value = new Date(data.createdAt).toLocaleDateString();
    updatedAt.value = new Date(data.updatedAt).toLocaleDateString();
    roster.value = data.rosterByName ?? data.roster ?? {};
  } catch (e: unknown) {
    error.value = e instanceof Error ? e.message : 'Unable to load plan';
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  loadPlan();
  try { const p = new URL(window.location.href).searchParams.get('p'); if (p) highlight.value = p } catch {}
});
const toc = computed(() => blocks.value.map((b, i) => {
  const base = (b.title || b.type || 'Section') as string;
  const slug = base.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
  return { i, id: `block-${i}-${slug || 'section'}`, title: base };
}));

function blockId(index: number): string {
  const base = blocks.value[index];
  if (!base) return `block-${index}`;
  const t = (base.title || base.type || 'Section') as string;
  const slug = t.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/(^-|-$)/g, '');
  return `block-${index}-${slug || 'section'}`;
}

function copySectionLink(id: string) {
  const url = new URL(window.location.href); url.hash = `#${id}`; navigator.clipboard.writeText(url.toString());
}

function matchName(name: string): boolean {
  const q = highlight.value.trim().toLowerCase();
  return !!q && name.toLowerCase().includes(q);
}
function copyLink() {
  navigator.clipboard.writeText(window.location.href);

  const toast = document.createElement('div');
  toast.className = 'fixed bottom-4 right-4 px-4 py-2 rounded-lg bg-emerald-600 text-white shadow-lg z-50 animate-fade-in';
  toast.textContent = '✓ Link copied to clipboard!';
  document.body.appendChild(toast);
  setTimeout(() => {
    toast.style.opacity = '0';
    toast.style.transition = 'opacity 0.3s';
    setTimeout(() => toast.remove(), 300);
  }, 2000);
}

function getRoleAssignments(block: RaidPlanBlock): Record<string, string[]> {
  const data = block.data as { assignmentsByRole?: unknown } | undefined;
  const src = data?.assignmentsByRole;
  if (!src || typeof src !== 'object' || Array.isArray(src)) return {};
  const out: Record<string, string[]> = {};
  for (const [role, value] of Object.entries(src as Record<string, unknown>)) {
    if (Array.isArray(value)) {
      out[role] = value.filter((v): v is string => typeof v === 'string');
    }
  }
  return out;
}

function getGroupsData(block: RaidPlanBlock): { groups: { id: string; title: string; members: string[] }[]; playersPerGroup?: number } {
  const data = block.data as { groups?: unknown; playersPerGroup?: unknown } | undefined;
  const rawGroups = Array.isArray(data?.groups) ? data?.groups as { id?: string; title?: string; members?: unknown }[] : [];
  const groups = rawGroups.map(g => ({
    id: g.id || g.title || 'group',
    title: g.title || g.id || 'Group',
    members: Array.isArray(g.members) ? g.members.filter((m): m is string => typeof m === 'string') : [],
  }));
  const playersPerGroup = typeof data?.playersPerGroup === 'number' ? data.playersPerGroup : undefined;
  return { groups, playersPerGroup };
}

function getBossAssignmentsData(block: RaidPlanBlock): { assignments: Record<string, string[]>; positionNotes: Record<string, string[]> } {
  const data = block.data as { assignments?: unknown; positionNotes?: unknown } | undefined;
  const assignmentsSrc = data?.assignments;
  const notesSrc = data?.positionNotes;
  const assignments: Record<string, string[]> = {};
  const positionNotes: Record<string, string[]> = {};

  if (assignmentsSrc && typeof assignmentsSrc === 'object' && !Array.isArray(assignmentsSrc)) {
    for (const [key, value] of Object.entries(assignmentsSrc as Record<string, unknown>)) {
      if (Array.isArray(value)) {
        assignments[key] = value.filter((v): v is string => typeof v === 'string');
      }
    }
  }

  if (notesSrc && typeof notesSrc === 'object' && !Array.isArray(notesSrc)) {
    for (const [key, value] of Object.entries(notesSrc as Record<string, unknown>)) {
      if (Array.isArray(value)) {
        positionNotes[key] = value.filter((v): v is string => typeof v === 'string');
      }
    }
  }

  return { assignments, positionNotes };
}

function getBenchList(block: RaidPlanBlock): string[] {
  const data = block.data as { bench?: unknown } | undefined;
  const src = data?.bench;
  if (!Array.isArray(src)) return [];
  return src.filter((v): v is string => typeof v === 'string');
}

function getCanvasShapes(block: RaidPlanBlock): CanvasShape[] {
  const data = block.data as { shapes?: unknown } | undefined;
  const src = data?.shapes;
  if (!Array.isArray(src)) return [];
  return src as CanvasShape[];
}

function printPage() {
  if (typeof window !== 'undefined' && window.print) {
    window.print();
  }
}
</script>

<template>
  <div class="min-h-screen bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 text-slate-100">

    <header class="border-b border-slate-700/50 bg-slate-950/90 backdrop-blur-sm px-6 py-6 shadow-lg">
      <div class="max-w-6xl mx-auto">
        <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
          <div>
            <div class="flex items-center gap-3 mb-2">
              <span class="text-4xl">🎯</span>
              <h1 class="text-3xl font-bold bg-gradient-to-r from-emerald-400 to-blue-400 bg-clip-text text-transparent">
                {{ planName }}
              </h1>
            </div>
            <p class="text-sm text-slate-300 flex items-center gap-2">
              <span class="inline-flex items-center px-2 py-1 rounded-md bg-emerald-500/10 text-emerald-300 ring-1 ring-inset ring-emerald-500/20 text-xs font-medium">
                {{ guildName }}
              </span>
              <span class="text-slate-500">•</span>
              <span class="text-slate-400">Read-only raid plan</span>
            </p>
          </div>
          <div class="flex flex-col md:flex-row items-start md:items-center gap-3">
            <div class="text-xs text-slate-400 hidden lg:block">
              <div class="flex items-center gap-2">
                <span class="text-slate-500">📅 Updated:</span>
                <span class="text-slate-300">{{ updatedAt }}</span>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <input
                v-model="highlight"
                placeholder="🔍 Find player..."
                class="bg-slate-900/80 border border-slate-600 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent w-40 md:w-48"
              />
              <button
                class="px-4 py-2 text-sm font-medium rounded-lg bg-emerald-600 hover:bg-emerald-500 transition-colors flex items-center gap-2 shadow-lg"
                @click="copyLink"
              >
                <span>🔗</span>
                <span class="hidden sm:inline">Copy Link</span>
              </button>
            </div>
          </div>
        </div>
      </div>
    </header>


    <div v-if="loading" class="flex items-center justify-center h-96">
      <div class="text-center">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-emerald-500"></div>
        <div class="mt-4 text-slate-400">Loading raid plan...</div>
      </div>
    </div>


    <div v-else-if="error" class="flex items-center justify-center h-96">
      <div class="text-center max-w-md">
        <div class="text-6xl mb-4">❌</div>
        <h2 class="text-xl font-semibold text-red-400 mb-2">Plan Not Found</h2>
        <p class="text-slate-400">{{ error }}</p>
      </div>
    </div>


    <main v-else class="max-w-6xl mx-auto px-6 py-8">

      <nav class="mb-6 p-4 rounded-xl bg-slate-950/60 border border-slate-700/50 backdrop-blur-sm">
        <div class="flex items-center gap-2 mb-3">
          <span class="text-sm font-semibold text-slate-300">📑 Table of Contents</span>
        </div>
        <div class="flex flex-wrap gap-2">
          <a
            v-for="t in toc"
            :key="t.id"
            :href="`#${t.id}`"
            class="px-3 py-1.5 rounded-lg bg-slate-800/60 border border-slate-700/50 hover:bg-slate-700/60 hover:border-emerald-500/30 text-slate-300 hover:text-emerald-300 text-xs font-medium transition-all"
          >
            {{ t.title }}
          </a>
        </div>
      </nav>
      <div class="grid auto-rows-min gap-4" style="grid-template-columns: repeat(12, minmax(0, 1fr))">
        <div
          v-for="block in blocks"
          :key="block.id"
          class="rounded-xl bg-slate-950/60 p-5 border border-slate-700/50 backdrop-blur-sm hover:border-slate-600/50 transition-all shadow-lg"
          :style="{
            gridColumn: block.colSpan ? block.colStart + ' / span ' + block.colSpan : '1 / span 12',
          }"
          :id="blockId(blocks.indexOf(block))"
        >

          <div class="mb-4 pb-3 border-b border-slate-700/50 flex items-center justify-between">
            <div class="flex items-center gap-3">
              <h3 class="text-base font-semibold text-slate-100">{{ block.title || block.type }}</h3>
              <span class="text-[10px] px-2 py-1 rounded-md bg-slate-800/80 border border-slate-700/50 text-slate-400 font-mono uppercase tracking-wider">
                {{ block.type }}
              </span>
            </div>
            <button
              class="text-xs px-3 py-1.5 rounded-lg bg-slate-800/60 hover:bg-slate-700/60 text-slate-300 hover:text-emerald-300 transition-colors flex items-center gap-1.5"
              @click="copySectionLink(blockId(blocks.indexOf(block)))"
              title="Copy direct link to this section"
            >
              <span>🔗</span>
              <span class="hidden sm:inline">Copy link</span>
            </button>
          </div>


          <div class="text-xs text-slate-300">

            <div v-if="block.type === 'TEXT'" class="whitespace-pre-wrap">
              {{ block.data?.textContent || 'No content' }}
            </div>


            <div v-else-if="block.type === 'HEADING'">
              <div :class="[
                block.data?.headingLevel === 1 ? 'text-xl' :
                block.data?.headingLevel === 2 ? 'text-lg' : 'text-base',
                'font-bold text-slate-100'
              ]">
                {{ block.data?.headingText }}
              </div>
            </div>


            <div v-else-if="block.type === 'DIVIDER'" class="flex items-center gap-2">
              <div class="flex-1">
                <div :class="[
                  'border-t',
                  block.data?.dividerStyle === 'dashed' ? 'border-dashed' : 'border-solid',
                  'border-slate-600'
                ]" />
              </div>
              <span v-if="block.data?.dividerLabel" class="px-2 py-0.5 rounded bg-slate-800 text-slate-400 text-xs">
                {{ block.data.dividerLabel }}
              </span>
            </div>


            <div v-else-if="block.type === 'IMAGE' && block.data?.imageUrl" class="space-y-2">
              <img
                :src="block.data.imageUrl"
                :alt="block.data?.imageCaption || 'Raid image'"
                class="w-full rounded border border-slate-700"
                :style="{ objectFit: block.data?.imageSize || 'contain', maxHeight: '400px' }"
              />
              <div v-if="block.data?.imageCaption" class="text-center text-slate-400 italic text-xs">
                {{ block.data.imageCaption }}
              </div>
            </div>



            <div v-else-if="block.type === 'ROLE_MATRIX'" class="h-[260px] overflow-auto pr-1">
              <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
              <div v-for="role in ['Tanks','Healers','Melee','Ranged']" :key="role" class="rounded border p-2"
                   :style="{ borderColor: roleColor(role) + '55', backgroundColor: roleColor(role) + '15' }">
                <div class="text-[11px] font-semibold mb-1"
                     :style="{ color: roleColor(role) }">{{ role }}</div>
                <ul class="space-y-1">
                  <li v-for="name in (getRoleAssignments(block)[role] || [])" :key="name"
                      class="rounded px-2 py-1"
                      :style="rosterByName[name]?.color ? { backgroundColor: rosterByName[name].color + '22', border: '1px solid ' + rosterByName[name].color + '55', color: rosterByName[name].color } : {}"
                      :class="[!rosterByName[name]?.color ? 'bg-slate-800/70' : '', matchName(name) ? 'ring-2 ring-emerald-500/70' : '']">
                  {{ name }}
                  </li>
                  <li v-if="!(getRoleAssignments(block)[role]?.length)" class="text-slate-500 italic">No assignments</li>
                </ul>
              </div>
              </div>
            </div>


            <div v-else-if="block.type === 'GROUPS_GRID'" class="h-[260px] overflow-auto pr-1">
              <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
              <div v-for="g in getGroupsData(block).groups || []" :key="g.id" class="rounded border border-slate-800 bg-slate-900/60 p-2">
                <div class="text-[11px] font-semibold text-slate-300 mb-1 flex items-center justify-between">
                  <span>{{ g.title }}</span>
                  <span class="text-slate-500">{{ (g.members || []).length }}/{{ getGroupsData(block).playersPerGroup ?? 5 }}</span>
                </div>
                <ul class="space-y-1">
                  <li v-for="name in (g.members || [])" :key="name"
                      class="rounded px-2 py-1"
                      :style="rosterByName[name]?.color ? { backgroundColor: rosterByName[name].color + '22', border: '1px solid ' + rosterByName[name].color + '55', color: rosterByName[name].color } : {}"
                      :class="[!rosterByName[name]?.color ? 'bg-slate-800/70' : '', matchName(name) ? 'ring-2 ring-emerald-500/70' : '']">
                    {{ name }}
                  </li>
                  <li v-if="!(g.members?.length)" class="text-slate-500 italic">Empty</li>
                </ul>
              </div>
              </div>
            </div>


            <div v-else-if="block.type === 'BOSS_GRID'" class="overflow-auto h-[260px]">
              <table class="w-full text-xs border border-slate-800">
                <thead class="sticky top-0 z-10 bg-slate-900/80 backdrop-blur">
                  <tr>
                    <th class="p-2 text-left w-48">Position</th>
                    <th class="p-2 text-left w-32">Role</th>
                    <th class="p-2 text-left">Assignees</th>
                    <th class="p-2 text-left w-48">Notes</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="pos in blockPositions(block)" :key="pos.id" class="odd:bg-slate-900/40">
                    <td class="p-2 align-top">{{ pos.label }}</td>
                    <td class="p-2 align-top">{{ pos.role || 'Any' }}</td>
                    <td class="p-2 align-top">
                      <div class="space-y-1">
                        <div v-for="name in (getBossAssignmentsData(block).assignments?.[pos.id] || [])" :key="name"
                             class="rounded px-2 py-1"
                             :style="rosterByName[name]?.color ? { backgroundColor: rosterByName[name].color + '22', border: '1px solid ' + rosterByName[name].color + '55', color: rosterByName[name].color } : {}"
                             :class="[!rosterByName[name]?.color ? 'bg-slate-800/70' : '', matchName(name) ? 'ring-2 ring-emerald-500/70' : '']">
                          {{ name }}
                        </div>
                        <div v-if="!(getBossAssignmentsData(block).assignments?.[pos.id]?.length)" class="text-slate-500 italic">Unassigned</div>
                      </div>
                    </td>
                    <td class="p-2 align-top">
                      <div v-if="(getBossAssignmentsData(block).positionNotes?.[pos.id]?.length)" class="space-y-0.5">
                        <div v-for="(note, ni) in (getBossAssignmentsData(block).positionNotes?.[pos.id] || [])" :key="pos.id + ':' + ni" class="text-[11px] text-slate-300">- {{ note }}</div>
                      </div>
                      <div v-else class="text-slate-500 italic">—</div>
                    </td>
                  </tr>
                  <tr v-if="!(blockPositions(block).length)" class="text-slate-500">
                    <td colspan="4" class="p-4 text-center">No positions</td>
                  </tr>
                </tbody>
              </table>
            </div>


            <div v-else-if="block.type === 'BENCH_ROSTER'">
              <div class="text-[11px] text-slate-400 mb-1">Benched players</div>
              <ul class="space-y-1">
                <li v-for="name in getBenchList(block)" :key="name"
                    class="rounded px-2 py-1"
                    :style="rosterByName[name]?.color ? { backgroundColor: rosterByName[name].color + '22', border: '1px solid ' + rosterByName[name].color + '55', color: rosterByName[name].color } : {}"
                    :class="[!rosterByName[name]?.color ? 'bg-slate-800/70' : '']">
                  {{ name }}
                </li>
                <li v-if="!(getBenchList(block).length)" class="text-slate-500 italic">No bench</li>
              </ul>
            </div>


            <div v-else-if="block.type === 'FREE_CANVAS'">
              <div class="relative border border-slate-800 rounded bg-slate-900/60 overflow-hidden" :style="canvasBackgroundStyle(block)">
                <div v-if="canvasBackgroundImage(block)" class="absolute inset-0 pointer-events-none">
                  <img :src="canvasBackgroundImage(block)!.url" alt="Background" class="w-full h-full object-cover" :style="{ opacity: canvasBackgroundImage(block)!.opacity }" />
                </div>
                <div v-if="canvasMapSvg(block)" class="absolute inset-0 pointer-events-none opacity-80" v-html="canvasMapSvg(block)"></div>
                <div v-if="ensureCanvasBackground(block).showCenter" class="absolute inset-0 pointer-events-none">
                  <div class="absolute inset-x-8 top-1/2 border-t border-dashed border-slate-600/40"></div>
                  <div class="absolute inset-y-8 left-1/2 border-l border-dashed border-slate-600/40"></div>
                </div>
                <div v-for="s in getCanvasShapes(block)" :key="s.id"
                     class="absolute rounded"
                     :style="{ left: (s.x||0)+'px', top: (s.y||0)+'px', width: (s.w||60)+'px', height: (s.h||40)+'px', transform: 'rotate(' + Number(s.rotation||0) + 'deg)', transformOrigin: 'center', backgroundColor: s.type==='rect' ? (s.color || '#64748b') : s.type==='circle' ? (s.color || 'rgba(59,130,246,0.15)') : 'transparent', border: (s.type==='text'||s.type==='timer') ? ('1px dashed ' + (s.color || '#64748b')) : (s.type==='circle' ? ((s.borderWidth || 3) + 'px solid ' + (s.border || '#3b82f6')) : 'none'), borderRadius: (s.type === 'circle' || s.type === 'player' || s.type === 'icon') ? '9999px' : '0' }">
                  <template v-if="s.type === 'text'">
                    <div class="w-full h-full grid place-items-center text-xs" :style="{ color: s.color || '#e5e7eb' }">{{ s.text || 'Text' }}</div>
                  </template>
                  <template v-else-if="s.type === 'timer'">
                    <div class="w-full h-full grid place-items-center text-[11px] text-slate-200">⏱ {{ String(Math.floor((s.seconds||0)/60)).padStart(1,'0') }}:{{ String((s.seconds||0)%60).padStart(2,'0') }}</div>
                  </template>
                  <template v-else-if="s.type === 'image'">
                    <div class="w-full h-full overflow-hidden rounded">
                      <img :src="s.url || ''" alt="" class="w-full h-full" :style="canvasImageStyle(s)" />
                    </div>
                  </template>
                  <template v-else-if="s.type === 'marker'">
                    <div class="w-full h-full grid place-items-center font-semibold" :style="{ color: (s.color || markerColor(s.marker || 'star')), fontSize: (Math.min(Number(s.w||60), Number(s.h||40)) * 0.6) + 'px', lineHeight: 1 }">{{ markerSymbol(s.marker || 'star') }}</div>
                  </template>
                  <template v-else-if="s.type === 'icon'">
                    <div class="w-full h-full grid place-items-center" v-html="raidMarkerSvg(s.iconId) || ''"></div>
                  </template>
                  <template v-else-if="s.type === 'player'">
                    <div class="w-full h-full flex flex-col items-center justify-center gap-1 text-[10px]">
                      <div class="w-10 h-10 rounded-full border-2 flex items-center justify-center text-[11px] font-semibold"
                           :style="{ backgroundColor: playerTokenColorPublic(s), borderColor: '#0f172a', color: '#0f172a' }">
                        {{ initials(playerDisplayNamePublic(s)) }}
                      </div>
                      <div v-if="s.showName" class="text-slate-200">{{ playerDisplayNamePublic(s) }}</div>
                      <div v-if="s.showRole" class="text-slate-400">{{ roleEmoji(s.role) }}</div>
                    </div>
                  </template>
                  <template v-else-if="s.type === 'arrow'">
                    <svg :width="s.w || 60" :height="s.h || 40" class="block">
                      <defs>
                        <marker :id="'pub-arrowhead-end-' + s.id" markerWidth="10" markerHeight="10" refX="9" refY="3" orient="auto" markerUnits="strokeWidth">
                          <path d="M0,0 L0,6 L9,3 z" :fill="s.color || '#e5e7eb'"></path>
                        </marker>
                        <marker :id="'pub-arrowhead-start-' + s.id" markerWidth="10" markerHeight="10" refX="0" refY="3" orient="auto" markerUnits="strokeWidth">
                          <path d="M9,0 L9,6 L0,3 z" :fill="s.color || '#e5e7eb'"></path>
                        </marker>
                      </defs>
                      <line :x1="(s.twoHeads? 10: 0) + 5" :y1="(s.h||40)/2" :x2="(s.w||60) - 5 - (s.twoHeads? 10: 0)" :y2="(s.h||40)/2"
                            :stroke="s.color || '#e5e7eb'" :stroke-width="s.thickness || 4"
                            :marker-end="'url(#pub-arrowhead-end-' + s.id + ')'"
                            :marker-start="s.twoHeads ? ('url(#pub-arrowhead-start-' + s.id + ')') : undefined" />
                    </svg>
                  </template>
                </div>
              </div>
            </div>

            <div v-else class="text-slate-500 italic">
              {{ block.type }} block
            </div>
          </div>
        </div>
      </div>


      <div class="mt-16 pt-8 border-t border-slate-700/50 text-center">
        <div class="space-y-3">
          <p class="text-sm text-slate-400">
            Shared by <span class="font-semibold text-emerald-400">{{ guildName }}</span>
          </p>
          <p class="text-xs text-slate-500">
            Powered by <span class="font-medium text-slate-400">GuildTracker</span> Raid Planner ⚔️
          </p>
          <div class="flex items-center justify-center gap-2 pt-2">
            <button
              @click="copyLink"
              class="px-4 py-2 text-xs rounded-lg bg-slate-800/60 hover:bg-slate-700/60 text-slate-300 transition-colors"
            >
              📋 Copy Plan Link
            </button>
            <button
              @click="printPage"
              class="px-4 py-2 text-xs rounded-lg bg-slate-800/60 hover:bg-slate-700/60 text-slate-300 transition-colors"
            >
              🖨️ Print Plan
            </button>
          </div>
        </div>
      </div>
    </main>
  </div>
</template>

<style>
@media print {
  body { background: #fff; }
  .bg-slate-950\/80, .bg-slate-950\/70, .bg-slate-900, .bg-slate-900\/60, .bg-slate-900\/70 { background: #fff !important; }
  .border-slate-800, .border-slate-700 { border-color: #e5e7eb !important; }
  header, nav a { color: #111 !important; }
  .rota-table thead tr { position: sticky; top: 0; background: #fff !important; }
}
</style>
