<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch, computed } from 'vue';
import { useRoute } from 'vue-router';
import BlockSidebar from '@/components/BlockSidebar.vue';
import RaidPlanCanvas from '@/components/RaidPlanCanvas.vue';
import CharacterSidebar from '@/components/CharacterSidebar.vue';
import BaseModal from '@/components/ui/BaseModal.vue';
import TemplatesPanel from '@/components/TemplatesPanel.vue';
import BossLibraryModal from '@/components/BossLibraryModal.vue';
import PlanManagerModal from '@/components/PlanManagerModal.vue';
import type { RaidPlanBlock, BlockType } from '@/interfaces/raidPlan.interface.ts';
import type { Character } from '@/interfaces/game.interface';
import { Role } from '@/interfaces/game.interface';
import { getCharactersByGuildId } from '@/services/character.service';
import { createRaidPlan, updateRaidPlan, generateShareLink, revokeShareLink, getRaidPlansByGuild, type RaidPlanDTO } from '@/services/raidPlan.service'
import type { RaidBoss } from '@/data/raidData';

const planName = ref('My Raid Plan');
const route = useRoute();
const guildId = ref<string | null>(null);
const characters = ref<Character[]>([]);
const loadingChars = ref(false);
const charsError = ref<string | null>(null);

let nextId = 1;
const blocks = ref<RaidPlanBlock[]>([]);

const draftKey = ref<string>('');
const versionsKey = ref<string>('');
const history: RaidPlanBlock[][] = [];
let historyIndex = -1;
let autosaveTimer: number | null = null;
const showHistory = ref(false);
const showTemplates = ref(false);
const saving = ref(false);
const lastSavedAt = ref<number | null>(null);
const canvasRef = ref<any>(null);
const exportMenuOpen = ref(false);
const showHelp = ref(false);
const showSaveTemplate = ref(false);
const templateName = ref('');
const showBossLibrary = ref(false);
const shareOpen = ref(false)
const shareUrl = ref('')
const planId = ref<number | null>(null)

function planIdKey(gid: string) {
  return `raidPlan:planId:${gid}`
}

const showPlanManager = ref(false)

function openPlanManager() {
  if (!guildId.value) return
  showPlanManager.value = true
}

function resetHistory() {
  history.length = 0
  historyIndex = -1
  pushHistorySnapshot()
}

function handleLoadPlan(p: RaidPlanDTO) {
  planId.value = p.id
  planName.value = p.name
  blocks.value = JSON.parse(JSON.stringify(p.blocks))
  try { if (guildId.value) localStorage.setItem(planIdKey(guildId.value), String(p.id)) } catch {}
  resetHistory()
  window.dispatchEvent(new CustomEvent('app:toast', { detail: { type: 'success', message: `Loaded "${p.name}"` } }))
}

function handleCreateNew() {
  const confirmed = confirm('Create a new plan? Current unsaved changes will be lost.');
  if (!confirmed) return;

  planId.value = null
  planName.value = 'New Raid Plan'
  blocks.value = []
  try { if (guildId.value) localStorage.removeItem(planIdKey(guildId.value)) } catch {}
  resetHistory()
  window.dispatchEvent(new CustomEvent('app:toast', { detail: { type: 'success', message: 'New plan created' } }))
}

const preset = ref<'classic40' | 'mythic20' | 'custom'>('custom');
const expected = ref<{ tanks: number; healers: number; dps: number }>({ tanks: 0, healers: 0, dps: 0 });
const summary = ref<{ tanks: number; healers: number; melee: number; ranged: number; missingText: string | null }>({ tanks: 0, healers: 0, melee: 0, ranged: 0, missingText: null });

function makeKeys(id: string) {
  draftKey.value = `raidPlan:draft:${id}`;
  versionsKey.value = `raidPlan:versions:${id}`;
}

function cloneBlocks(): RaidPlanBlock[] {
  return JSON.parse(JSON.stringify(blocks.value));
}

function pushHistorySnapshot() {
  const snapshot = cloneBlocks();

  if (historyIndex < history.length - 1) history.splice(historyIndex + 1);
  history.push(snapshot);
  historyIndex = history.length - 1;
}

function undo() {
  if (historyIndex <= 0) return;
  historyIndex--;
  blocks.value = JSON.parse(JSON.stringify(history[historyIndex]));
}

function redo() {
  if (historyIndex >= history.length - 1) return;
  historyIndex++;
  blocks.value = JSON.parse(JSON.stringify(history[historyIndex]));
}

function saveDraftNow() {
  if (!draftKey.value) return;
  saving.value = true;
  const payload = { planName: planName.value, blocks: cloneBlocks(), ts: Date.now() };
  localStorage.setItem(draftKey.value, JSON.stringify(payload));
  saving.value = false;
  lastSavedAt.value = Date.now();
}

async function saveToDatabase() {
  if (!guildId.value) return
  try {
    if (planId.value == null) {
      const created = await createRaidPlan({
        guildId: guildId.value,
        name: planName.value || 'Untitled Raid Plan',
        blocks: cloneBlocks(),
      })
      planId.value = created.id
      localStorage.setItem(planIdKey(guildId.value), String(created.id))
      window.dispatchEvent(new CustomEvent('app:toast', { detail: { type: 'success', message: 'Raid plan created' } }))
    } else {
      const updated: RaidPlanDTO = await updateRaidPlan(planId.value, {
        name: planName.value || 'Untitled Raid Plan',
        blocks: cloneBlocks(),
      })
      window.dispatchEvent(new CustomEvent('app:toast', { detail: { type: 'success', message: 'Raid plan saved' } }))
    }
  } catch (e: any) {
    window.dispatchEvent(new CustomEvent('app:toast', { detail: { type: 'error', message: e?.message || 'Failed to save plan' } }))
  }
}

async function openShare() {
  if (!guildId.value) return
  try {
    if (planId.value == null) {
      await saveToDatabase()
    }
    if (planId.value == null) return
    const { shareUrl: url } = await generateShareLink(planId.value)
    shareUrl.value = url
    shareOpen.value = true
  } catch (e: any) {
    window.dispatchEvent(new CustomEvent('app:toast', { detail: { type: 'error', message: e?.message || 'Failed to generate share link' } }))
  }
}

function scheduleAutosave() {
  if (autosaveTimer) window.clearTimeout(autosaveTimer);
  autosaveTimer = window.setTimeout(saveDraftNow, 1200);
}

function loadDraft() {
  if (!draftKey.value) return;
  try {
    const txt = localStorage.getItem(draftKey.value);
    if (!txt) return;
    const json = JSON.parse(txt);
    if (json?.planName) planName.value = json.planName;
    if (Array.isArray(json?.blocks)) {
      blocks.value = json.blocks;
      recalcNextId();
    }
  } catch {}
}

type VersionEntry = { id: string; ts: number; name: string; count: number; blocks: RaidPlanBlock[] }

function getVersions(): VersionEntry[] {
  try {
    const txt = localStorage.getItem(versionsKey.value);
    if (!txt) return [];
    const arr = JSON.parse(txt) as any[];
    return Array.isArray(arr) ? arr : [];
  } catch { return []; }
}

function setVersions(v: VersionEntry[]) {
  localStorage.setItem(versionsKey.value, JSON.stringify(v));
}

function saveVersion() {
  if (!versionsKey.value) {
    window.dispatchEvent(new CustomEvent('app:toast', {
      detail: { type: 'error', message: 'Cannot save snapshot: No guild selected' }
    }));
    return;
  }

  const versions = getVersions();
  const v: VersionEntry = { id: String(Date.now()), ts: Date.now(), name: planName.value, count: blocks.value.length, blocks: cloneBlocks() };
  versions.unshift(v);

  setVersions(versions.slice(0, 20));

  window.dispatchEvent(new CustomEvent('app:toast', {
    detail: { type: 'success', message: `Snapshot saved: "${planName.value}"` }
  }));
}

function restoreVersion(id: string) {
  const versions = getVersions();
  const v = versions.find((x) => x.id === id);
  if (!v) {
    window.dispatchEvent(new CustomEvent('app:toast', {
      detail: { type: 'error', message: 'Snapshot not found' }
    }));
    return;
  }

  blocks.value = JSON.parse(JSON.stringify(v.blocks ?? []));
  recalcNextId();
  resetHistory();
  showHistory.value = false;

  window.dispatchEvent(new CustomEvent('app:toast', {
    detail: { type: 'success', message: `Restored snapshot: "${v.name}"` }
  }));
}

function deleteVersion(id: string) {
  const versions = getVersions();
  const filtered = versions.filter(v => v.id !== id);
  setVersions(filtered);

  window.dispatchEvent(new CustomEvent('app:toast', {
    detail: { type: 'success', message: 'Snapshot deleted' }
  }));
}

function formatRelativeTime(timestamp: number): string {
  const now = Date.now();
  const diff = now - timestamp;
  const minutes = Math.floor(diff / 60000);
  const hours = Math.floor(diff / 3600000);
  const days = Math.floor(diff / 86400000);

  if (minutes < 1) return 'Just now';
  if (minutes < 60) return `${minutes}m ago`;
  if (hours < 24) return `${hours}h ago`;
  if (days < 7) return `${days}d ago`;

  return new Date(timestamp).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function onKey(e: KeyboardEvent) {
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'z') { e.preventDefault(); undo(); }
  if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'y') { e.preventDefault(); redo(); }
}

function getDefaultColSpan(type: BlockType): number {
  switch (type) {

    case 'GROUPS_GRID':
    case 'ROLE_MATRIX':
    case 'HEADING':
    case 'DIVIDER':
      return 12;

    case 'BOSS_GRID':
    case 'COOLDOWN_ROTATION':
    case 'INTERRUPT_ROTATION':
    case 'CUSTOM_SECTION':
    case 'PHASE_STRATEGY':
    case 'IMAGE':
      return 6;

    case 'TEXT':
    case 'CHECKLIST':
    case 'BENCH_ROSTER':
      return 4;

    default:
      return 6;
  }
}

function addBlock(type: BlockType) {
  const defaultColSpan = getDefaultColSpan(type);

  const base: RaidPlanBlock = {
    id: String(nextId++),
    type,
    colStart: 1,
    colSpan: defaultColSpan,
    row: 0,
    title: type,
    data: {},
  };

  switch (type) {
    case 'TEXT':
      base.title = 'Notes';
      base.data = {
        textContent: '',
      };
      break;

    case 'HEADING':
      base.title = 'Section';
      base.data = {
        headingText: 'New section',
        headingLevel: 2,
      };
      break;

    case 'DIVIDER':
      base.title = 'Divider';
      base.data = {
        dividerLabel: '',
        dividerStyle: 'solid',
      };
      break;

    case 'CHECKLIST':
      base.title = 'Checklist';
      base.data = {
        checklistItems: [
          { id: '1', label: 'Flasks / Phials', done: false },
          { id: '2', label: 'Food buff', done: false },
          { id: '3', label: 'Runes / Vantus', done: false },
        ],
      };
      break;

    case 'IMAGE':
      base.title = 'Image';
      base.colSpan = 6; // Half width by default
      base.data = {
        imageUrl: '',
        imageCaption: '',
        imageSize: 'contain',
      };
      break;

    case 'BOSS_GRID':
      base.title = 'Boss Grid';
      base.data = {
        positions: [
          { id: 'pos1', label: 'Tank 1', accepts: ['Tanks'] },
          { id: 'pos2', label: 'Tank 2', accepts: ['Tanks'] },
          { id: 'pos3', label: 'Healer 1', accepts: ['Healers'] },
          { id: 'pos4', label: 'Healer 2', accepts: ['Healers'] },
          { id: 'pos5', label: 'Melee 1', accepts: ['Melee'] },
          { id: 'pos6', label: 'Ranged 1', accepts: ['Ranged'] },
        ],
        assignments: {},
      };
      break;

    case 'PHASE_STRATEGY':
      base.title = 'Phase Strategy';
      base.data = {
        phases: [
          { id: 'p1', title: 'Phase 1', notes: '' },
          { id: 'p2', title: 'Phase 2', notes: '' },
        ],
      };
      break;

    case 'BENCH_ROSTER':
      base.title = 'Bench';
      base.data = {
        bench: [] as string[],
      };
      break;

    case 'COOLDOWN_ROTATION':
      base.title = 'Cooldown Rotation';
      base.data = {
        columns: [
          { id: 't1', label: '0:30', sublabel: 'Phase 1' },
          { id: 't2', label: '1:00', sublabel: 'Phase 1' },
          { id: 't3', label: '1:30', sublabel: 'Phase 1' },
          { id: 't4', label: '2:00', sublabel: 'Phase 2' },
        ],
        rows: [
          { id: 'cd1', label: 'Spirit Link', type: 'healing', cells: {} },
          { id: 'cd2', label: 'Barrier', type: 'healing', cells: {} },
          { id: 'cd3', label: 'AMZ', type: 'healing', cells: {} },
          { id: 'cd4', label: 'Rally', type: 'healing', cells: {} },
        ],
      };
      break;

    case 'INTERRUPT_ROTATION':
      base.title = 'Interrupt Rotation';
      base.data = {
        columns: [
          { id: 'i1', label: '1' },
          { id: 'i2', label: '2' },
          { id: 'i3', label: '3' },
        ],
        rows: [
          { id: 'r1', label: 'Boss', cells: {} },
        ],
      };
      break;

    case 'CUSTOM_SECTION':
      base.title = 'Custom Section';
      base.data = {
        columns: [
          { id: 'c1', label: 'Task', type: 'text' },
          { id: 'c2', label: 'Assignee', type: 'assignee' },
          { id: 'c3', label: 'Notes', type: 'text' },
        ],
        rows: [
          { id: 'r1', data: { c1: 'Mark Left', c2: null, c3: '' } },
          { id: 'r2', data: { c1: 'Mark Right', c2: null, c3: '' } },
        ],
      };
      break;

    case 'ROLE_MATRIX':
      base.title = 'Role Matrix';
      base.colSpan = 12;
      base.data = {
        roleAssignments: {
          [Role.TANKS]: [] as string[],
          [Role.HEALERS]: [] as string[],
          [Role.MELEE]: [] as string[],
          [Role.RANGED]: [] as string[],
        },
      };
      break;

    case 'GROUPS_GRID':
      base.title = 'Groups';
      base.colSpan = 12;
      base.data = {
        groupCount: 8,
        playersPerGroup: 5,
        groups: Array.from({ length: 8 }).map((_, i) => ({
          id: String(i + 1),
          title: `Group ${i + 1}`,
          members: [] as string[],
        })),
      };
      break;

    default:
      break;
  }

  blocks.value.push(base);
}


function updateBlock(updated: RaidPlanBlock) {
  blocks.value = blocks.value.map((b) => (b.id === updated.id ? updated : b));
}

function removeBlock(id: string) {
  blocks.value = blocks.value.filter((b) => b.id !== id);
}


function reorderBlocks(newOrder: RaidPlanBlock[]) {
  blocks.value = newOrder;
}

onMounted(async () => {
  const idParam = route.params.id;
  guildId.value = typeof idParam === 'string' ? idParam : Array.isArray(idParam) ? idParam[0] : null;
  if (!guildId.value) return;
  makeKeys(guildId.value);
  try {
    const stored = localStorage.getItem(planIdKey(guildId.value))
    if (stored) {
      const n = parseInt(stored, 10)
      if (Number.isFinite(n)) planId.value = n
    }
  } catch {}
  loadDraft();

  pushHistorySnapshot();
  window.addEventListener('keydown', onKey);
  loadingChars.value = true;
  const res = await getCharactersByGuildId(guildId.value).catch((e) => ({ ok: false, error: e?.message || 'Network error' } as const));
  loadingChars.value = false;
  if (!res.ok) {

    characters.value = generateMockRoster();
    charsError.value = res.error || 'Using local mock roster';
  } else {
    characters.value = res.data;
  }
});

onUnmounted(() => {
  window.removeEventListener('keydown', onKey);
});

watch(blocks, () => { pushHistorySnapshot(); scheduleAutosave(); }, { deep: true });
watch(planName, () => scheduleAutosave());
watch([blocks, characters, expected], () => computeSummary(), { deep: true });

function recalcNextId() {
  const maxId = blocks.value.reduce((acc, b) => {
    const n = parseInt(b.id, 10);
    return Number.isFinite(n) ? Math.max(acc, n) : acc;
  }, 0);
  nextId = maxId + 1;
}

function loadTemplate(b: RaidPlanBlock[]) {
  blocks.value = JSON.parse(JSON.stringify(b));
  showTemplates.value = false;
  pushHistorySnapshot();
  recalcNextId();
}

function generateMockRoster() {
  const now = Date.now();
  const mk = (id: number, name: string, cls: string, spec: string | undefined, role: any) => ({
    id: `mock-${now}-${id}`,
    name,
    class: cls,
    spec,
    role,
    status: 'active',
    createdAt: new Date(now).toISOString(),
  });
  return [
    mk(1, 'Thorin', 'Warrior', 'Protection', 'Tanks'),
    mk(2, 'Aegwyn', 'Paladin', 'Holy', 'Healers'),
    mk(3, 'Lirien', 'Priest', 'Discipline', 'Healers'),
    mk(4, 'Gromm', 'Warrior', 'Arms', 'Melee'),
    mk(5, 'Fenris', 'Rogue', 'Assassination', 'Melee'),
    mk(6, 'Sylva', 'Hunter', 'Marksmanship', 'Ranged'),
    mk(7, 'Dalar', 'Mage', 'Frost', 'Ranged'),
    mk(8, 'Elwyn', 'Druid', 'Restoration', 'Healers'),
    mk(9, 'Voljin', 'Shaman', 'Enhancement', 'Melee'),
    mk(10, 'Tyrande', 'Priest', 'Holy', 'Healers'),
    mk(11, 'Rexx', 'Hunter', 'Beast Mastery', 'Ranged'),
    mk(12, 'Vale', 'Warlock', 'Destruction', 'Ranged'),
    mk(13, 'Jaina', 'Mage', 'Arcane', 'Ranged'),
    mk(14, 'Uther', 'Paladin', 'Protection', 'Tanks'),
    mk(15, 'Brox', 'Warrior', 'Fury', 'Melee'),
    mk(16, 'Kael', 'Mage', 'Fire', 'Ranged'),
    mk(17, 'Velen', 'Priest', 'Shadow', 'Ranged'),
    mk(18, 'Malf', 'Druid', 'Feral', 'Melee'),
    mk(19, 'Andu', 'Paladin', 'Retribution', 'Melee'),
    mk(20, 'Medivh', 'Mage', 'Frost', 'Ranged'),
  ] as unknown as Character[];
}

function getAllAssignedIds(): Set<string> {
  const ids = new Set<string>();
  for (const b of blocks.value) {
    if (b.type === 'GROUPS_GRID') {
      for (const g of (b.data?.groups ?? []) as any[]) {
        (g.members ?? []).forEach((id: string) => ids.add(id));
      }
    }
    if (b.type === 'ROLE_MATRIX') {
      const ra = b.data?.roleAssignments as any;
      if (ra) Object.values(ra).flat().forEach((id: any) => ids.add(String(id)));
    }
  }
  return ids;
}

function computeSummary() {
  let t = 0, h = 0, m = 0, r = 0;
  const assigned = getAllAssignedIds();
  for (const c of characters.value) {
    if (!assigned.has(c.id)) continue;
    if (c.role === 'Tanks') t++;
    else if (c.role === 'Healers') h++;
    else if (c.role === 'Melee') m++;
    else if (c.role === 'Ranged') r++;
  }
  summary.value = { tanks: t, healers: h, melee: m, ranged: r, missingText: buildMissingText(t, h, m + r) };
}

function buildMissingText(t: number, h: number, dps: number): string | null {
  if (!expected.value.tanks && !expected.value.healers && !expected.value.dps) return null;
  const missT = Math.max(0, expected.value.tanks - t);
  const missH = Math.max(0, expected.value.healers - h);
  const missD = Math.max(0, expected.value.dps - dps);
  const parts: string[] = [];
  if (missT) parts.push(`${missT} Tanks`);
  if (missH) parts.push(`${missH} Healers`);
  if (missD) parts.push(`${missD} DPS`);
  return parts.length ? `Missing: ${parts.join(', ')}` : '✅ Balanced';
}

function ensureGroupsBlock(groupCount: number, size: number) {
  const existing = blocks.value.find(b => b.type === 'GROUPS_GRID');
  if (existing) {
    (existing.data as any).groupCount = groupCount;
    (existing.data as any).playersPerGroup = size;
    (existing.data as any).groups = Array.from({ length: groupCount }).map((_, i) => ({ id: String(i + 1), title: `Group ${i + 1}`, members: (existing.data as any).groups?.[i]?.members || [] }));
    updateBlock(existing);
    return;
  }
  const base: RaidPlanBlock = { id: String(nextId++), type: 'GROUPS_GRID', colStart: 1, colSpan: 12, row: 0, title: 'Groups', data: { groupCount, playersPerGroup: size, groups: Array.from({ length: groupCount }).map((_, i) => ({ id: String(i + 1), title: `Group ${i + 1}`, members: [] })) } };
  blocks.value.push(base);
}

function applyPreset(p: 'classic40' | 'mythic20') {
  preset.value = p;
  if (p === 'classic40') {
    expected.value = { tanks: 5, healers: 10, dps: 25 };
    ensureGroupsBlock(8, 5);
  } else if (p === 'mythic20') {
    expected.value = { tanks: 2, healers: 4, dps: 14 };
    ensureGroupsBlock(4, 5);
  }
  computeSummary();
}

function copyMarkdown() {
  const lines: string[] = [];
  lines.push(`# ${planName.value}`);
  lines.push(`Tanks: ${summary.value.tanks} • Healers: ${summary.value.healers} • DPS: ${summary.value.melee + summary.value.ranged}`);
  for (const b of blocks.value) {
    if (b.type === 'GROUPS_GRID') {
      lines.push(`\n## Groups`);
      for (const g of (b.data?.groups ?? []) as any[]) {
        const names = (g.members ?? []).map((id: string) => characters.value.find(c => c.id === id)?.name || 'Unknown');
        lines.push(`- ${g.title}: ${names.join(', ')}`);
      }
    }
    if (b.type === 'COOLDOWN_ROTATION') {
      lines.push(`\n## Cooldown Rotation`);
      const cols = (b.data?.columns ?? []) as any[];
      const rows = (b.data?.rows ?? []) as any[];
      lines.push(`| CD | ${cols.map(c => c.label).join(' | ')} |`);
      lines.push(`|${Array(cols.length + 1).fill('---').join('|')}|`);
      for (const r of rows) {
        const cells = cols.map(c => {
          const id = r.cells?.[c.id]; const name = characters.value.find(cc => cc.id === id)?.name || '';
          return name || '—';
        });
        lines.push(`| ${r.label} | ${cells.join(' | ')} |`);
      }
    }
    if (b.type === 'INTERRUPT_ROTATION') {
      lines.push(`\n## Interrupt Rotation`);
      const cols = (b.data?.columns ?? []) as any[];
      const rows = (b.data?.rows ?? []) as any[];
      lines.push(`| Target | ${cols.map(c => c.label).join(' | ')} |`);
      lines.push(`|${Array(cols.length + 1).fill('---').join('|')}|`);
      for (const r of rows) {
        const cells = cols.map(c => {
          const id = r.cells?.[c.id]; const name = characters.value.find(cc => cc.id === id)?.name || '';
          return name || '—';
        });
        lines.push(`| ${r.label} | ${cells.join(' | ')} |`);
      }
    }
  }
  navigator.clipboard?.writeText(lines.join('\n'));
}

function downloadJson() {
  const payload = { name: planName.value, blocks: blocks.value };
  const blob = new Blob([JSON.stringify(payload, null, 2)], { type: 'application/json' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url; a.download = `${planName.value.replace(/\s+/g,'_')}.json`; a.click();
  URL.revokeObjectURL(url);
}

function renderExportCanvas(): HTMLCanvasElement {

  const groupsBlocks = blocks.value.filter(b => b.type === 'GROUPS_GRID');
  const width = 1000;
  const rowHeight = 28;
  const padding = 16;
  let rows = 2; // header lines
  for (const b of groupsBlocks) rows += ((b.data as any)?.groups?.length || 0) + 1; // title + groups
  const height = Math.max(200, rows * rowHeight + padding * 2);
  const canvas = document.createElement('canvas');
  canvas.width = width; canvas.height = height;
  const ctx = canvas.getContext('2d')!;

  ctx.fillStyle = '#0f172a'; ctx.fillRect(0,0,width,height);
  ctx.fillStyle = '#e5e7eb'; ctx.font = 'bold 20px system-ui, sans-serif';
  ctx.fillText(planName.value, padding, padding + 20);
  ctx.font = '12px system-ui, sans-serif';
  const sumText = `T ${summary.value.tanks} | H ${summary.value.healers} | DPS ${summary.value.melee + summary.value.ranged}`;
  ctx.fillText(sumText, padding, padding + 40);
  let y = padding + 70;
  for (const b of groupsBlocks) {
    ctx.fillStyle = '#94a3b8'; ctx.font = 'bold 14px system-ui, sans-serif'; ctx.fillText('Groups', padding, y); y += rowHeight;
    ctx.font = '12px system-ui, sans-serif'; ctx.fillStyle = '#e5e7eb';
    for (const g of (b.data as any).groups as any[]) {
      const names = (g.members ?? []).map((id: string) => characters.value.find(c => c.id === id)?.name || 'Unknown').join(', ');
      ctx.fillText(`${g.title}: ${names}`, padding, y);
      y += rowHeight;
    }
  }
  return canvas;
}

async function copyImage() {
  const canvas = renderExportCanvas();
  if ((navigator as any).clipboard && (window as any).ClipboardItem) {
    const blob: Blob = await new Promise(res => canvas.toBlob(b => res(b!), 'image/png'));
    const item = new (window as any).ClipboardItem({ 'image/png': blob });
    await (navigator as any).clipboard.write([item]);
  }
}

function downloadPng() {
  const canvas = renderExportCanvas();
  const a = document.createElement('a');
  a.href = canvas.toDataURL('image/png');
  a.download = `${planName.value.replace(/\s+/g,'_')}.png`;
  a.click();
}

const groupTargets = computed(() => {
  if (preset.value === 'classic40') return { tank: 1, healer: 2 };
  if (preset.value === 'mythic20') return { tank: 0, healer: 1 };
  return null;
});

const sidebarRef = ref<any>(null);
function setRosterFilter(r: any) {
  sidebarRef.value?.setRoleFilter?.(r);
}

type BlockTemplate = {
  id: string;
  name: string;
  description?: string;
  block: RaidPlanBlock;
  createdAt: number;
};

const TEMPLATES_KEY = 'raidPlan:blockTemplates';

function getBlockTemplates(): BlockTemplate[] {
  try {
    const txt = localStorage.getItem(TEMPLATES_KEY);
    if (!txt) return [];
    const arr = JSON.parse(txt);
    return Array.isArray(arr) ? arr : [];
  } catch {
    return [];
  }
}

function saveBlockAsTemplate() {
  if (!canvasRef.value?.selectedBlockId) {
    alert('Please select a block first');
    return;
  }

  const blockId = canvasRef.value.selectedBlockId;
  const block = blocks.value.find(b => b.id === blockId);
  if (!block) return;

  showSaveTemplate.value = true;
}

function confirmSaveTemplate() {
  if (!templateName.value.trim()) {
    alert('Please enter a template name');
    return;
  }

  const blockId = canvasRef.value?.selectedBlockId;
  const block = blocks.value.find(b => b.id === blockId);
  if (!block) return;

  const templates = getBlockTemplates();
  const newTemplate: BlockTemplate = {
    id: String(Date.now()),
    name: templateName.value.trim(),
    block: JSON.parse(JSON.stringify(block)),
    createdAt: Date.now(),
  };

  templates.unshift(newTemplate);

  localStorage.setItem(TEMPLATES_KEY, JSON.stringify(templates.slice(0, 50)));

  showSaveTemplate.value = false;
  templateName.value = '';
  alert(`Template "${newTemplate.name}" saved!`);
}

function loadBlockTemplate(template: BlockTemplate) {
  const newBlock = JSON.parse(JSON.stringify(template.block));
  newBlock.id = String(nextId++);
  newBlock.colStart = 1;
  newBlock.row = 0;
  blocks.value.push(newBlock);
}

function deleteBlockTemplate(templateId: string) {
  const templates = getBlockTemplates().filter(t => t.id !== templateId);
  localStorage.setItem(TEMPLATES_KEY, JSON.stringify(templates));
}

const blockTemplates = ref<BlockTemplate[]>(getBlockTemplates());

function applyBossData(boss: RaidBoss) {
  planName.value = `${boss.name} - ${boss.raid}`;

  const headingBlock: RaidPlanBlock = {
    id: String(nextId++),
    type: 'HEADING',
    title: 'Boss Header',
    colStart: 1,
    colSpan: 12,
    data: {
      headingLevel: 1,
      headingText: `${boss.name} - ${boss.raid}`,
    },
  };
  blocks.value.push(headingBlock);

  const linkBlock: RaidPlanBlock = {
    id: String(nextId++),
    type: 'TEXT',
    title: 'Resources',
    colStart: 1,
    colSpan: 12,
    data: {
      textContent: `📖 Guide: ${boss.wowheadUrl}\n\nExpansion: ${boss.expansion}\nNPC ID: ${boss.npcId}`,
    },
  };
  blocks.value.push(linkBlock);

  const bossGridBlock: RaidPlanBlock = {
    id: String(nextId++),
    type: 'BOSS_GRID',
    title: `${boss.name} - Positions`,
    colStart: 1,
    colSpan: 6,
    data: {
      positions: [
        { id: 'pos1', label: 'Tank 1', accepts: ['Tanks'] },
        { id: 'pos2', label: 'Tank 2', accepts: ['Tanks'] },
        { id: 'pos3', label: 'Healer 1', accepts: ['Healers'] },
        { id: 'pos4', label: 'Healer 2', accepts: ['Healers'] },
        { id: 'pos5', label: 'Melee 1', accepts: ['Melee'] },
        { id: 'pos6', label: 'Ranged 1', accepts: ['Ranged'] },
      ],
      assignments: {},
    },
  };
  blocks.value.push(bossGridBlock);

  const cdBlock: RaidPlanBlock = {
    id: String(nextId++),
    type: 'COOLDOWN_ROTATION',
    title: `${boss.name} - Cooldowns`,
    colStart: 7,
    colSpan: 6,
    data: {
      columns: [
        { id: 't1', label: '0:30', sublabel: 'Phase 1' },
        { id: 't2', label: '1:00', sublabel: '' },
        { id: 't3', label: '2:00', sublabel: 'Phase 2' },
      ],
      rows: [
        { id: 'cd1', label: 'Spirit Link', type: 'healing', cells: {} },
        { id: 'cd2', label: 'Barrier', type: 'healing', cells: {} },
      ],
    },
  };
  blocks.value.push(cdBlock);

  window.dispatchEvent(new CustomEvent('app:toast', {
    detail: { type: 'success', message: `Loaded boss: ${boss.name}` }
  }));
}
</script>

<template>
  <div class="flex h-screen flex-col bg-slate-900 text-slate-100">
    
    <!-- Header redesigné - Organisation claire et ergonomique -->
    <header class="border-b border-slate-800 bg-slate-950/90 backdrop-blur-sm shadow-lg">
      <!-- Ligne 1: Titre et actions principales -->
      <div class="flex items-center justify-between px-4 py-3 border-b border-slate-800/50">
        <!-- Gauche: Titre du plan -->
        <div class="flex items-center gap-3">
          <div class="flex items-center gap-2">
            <span class="text-2xl">🎯</span>
            <input
              v-model="planName"
              type="text"
              class="bg-slate-900/60 border border-slate-700 rounded-lg px-3 py-2 text-base font-semibold focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent min-w-[300px]"
              placeholder="My Raid Plan"
            />
          </div>
          <!-- Status autosave -->
          <div class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-slate-800/60 border border-slate-700">
            <div class="w-2 h-2 rounded-full" :class="saving ? 'bg-amber-400 animate-pulse' : 'bg-emerald-400'"></div>
            <span class="text-xs text-slate-400">
              <template v-if="saving">Saving...</template>
              <template v-else-if="lastSavedAt">
                Saved {{ Math.max(1, Math.round((Date.now() - lastSavedAt)/1000)) }}s ago
              </template>
              <template v-else>Autosave</template>
            </span>
          </div>
        </div>

        <!-- Droite: Actions primaires -->
        <div class="flex items-center gap-2">
          <!-- Groupe: Édition -->
          <div class="flex items-center gap-1 px-1 py-1 rounded-lg bg-slate-900/60 border border-slate-700/50">
            <button
              class="p-2 rounded hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
              @click="undo"
              :disabled="historyIndex <= 0"
              title="Undo (Ctrl/Cmd+Z)"
            >
              <span class="text-lg">↶</span>
            </button>
            <button
              class="p-2 rounded hover:bg-slate-800 disabled:opacity-40 disabled:cursor-not-allowed transition-colors"
              @click="redo"
              :disabled="historyIndex >= history.length - 1"
              title="Redo (Ctrl/Cmd+Y)"
            >
              <span class="text-lg">↷</span>
            </button>
            <div class="w-px h-6 bg-slate-700"></div>
            <button
              class="px-3 py-2 rounded hover:bg-slate-800 text-sm font-medium transition-colors"
              @click="showHistory = true"
              title="View version history"
            >
              📜 History
            </button>
          </div>

          <!-- Groupe: Sauvegarde -->
          <div class="flex items-center gap-1">
            <button
              class="px-4 py-2 rounded-lg border border-blue-600/50 bg-blue-900/30 text-blue-300 hover:bg-blue-900/50 text-sm font-medium transition-colors"
              @click="saveToDatabase"
              title="Save to database"
            >
              💾 Save
            </button>
            <button
              class="px-4 py-2 rounded-lg border border-emerald-600/50 bg-emerald-900/30 text-emerald-300 hover:bg-emerald-900/50 text-sm font-medium transition-colors"
              @click="openShare"
              title="Share this plan"
            >
              🔗 Share
            </button>
          </div>

          <!-- Groupe: Export -->
          <div class="relative">
            <button
              class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-medium transition-colors flex items-center gap-2"
              @click="exportMenuOpen = !exportMenuOpen"
            >
              📤 Export
              <span class="text-xs">▼</span>
            </button>
            <div v-if="exportMenuOpen" class="absolute right-0 mt-2 w-52 rounded-lg border border-slate-700 bg-slate-900/98 backdrop-blur shadow-2xl z-50 overflow-hidden">
              <button class="w-full text-left px-4 py-2.5 text-sm hover:bg-slate-800 transition-colors flex items-center gap-2" @click="copyMarkdown(); exportMenuOpen = false">
                📝 <span>Copy Markdown</span>
              </button>
              <button class="w-full text-left px-4 py-2.5 text-sm hover:bg-slate-800 transition-colors flex items-center gap-2" @click="downloadJson(); exportMenuOpen = false">
                { } <span>Download JSON</span>
              </button>
              <div class="border-t border-slate-800"></div>
              <button class="w-full text-left px-4 py-2.5 text-sm hover:bg-slate-800 transition-colors flex items-center gap-2" @click="copyImage(); exportMenuOpen = false">
                🖼️ <span>Copy as Image</span>
              </button>
              <button class="w-full text-left px-4 py-2.5 text-sm hover:bg-slate-800 transition-colors flex items-center gap-2" @click="downloadPng(); exportMenuOpen = false">
                💾 <span>Download PNG</span>
              </button>
            </div>
          </div>

          <!-- Help -->
          <button
            class="p-2 rounded-lg border border-slate-700 hover:bg-slate-800 transition-colors"
            @click="showHelp = true"
            title="Keyboard shortcuts & help"
          >
            <span class="text-lg">❓</span>
          </button>
        </div>
      </div>

      <!-- Ligne 2: Outils et bibliothèques -->
      <div class="flex items-center justify-between px-4 py-2">
        <!-- Gauche: Bibliothèques et templates -->
        <div class="flex items-center gap-2">
          <span class="text-xs uppercase tracking-wide text-slate-500 font-semibold">Tools</span>
          <button
            class="px-3 py-1.5 rounded-md border border-emerald-600/40 bg-emerald-900/20 text-emerald-300 hover:bg-emerald-900/40 text-xs font-medium transition-colors"
            @click="showBossLibrary = true"
            title="Load boss templates"
          >
            📚 Boss Library
          </button>
          <button
            class="px-3 py-1.5 rounded-md border border-slate-700 hover:bg-slate-800 text-xs font-medium transition-colors"
            @click="showTemplates = true"
            title="Browse plan templates"
          >
            📋 Templates
          </button>
          <button
            class="px-3 py-1.5 rounded-md border border-slate-700 hover:bg-slate-800 text-xs font-medium transition-colors"
            @click="saveBlockAsTemplate"
            title="Save selected block as template"
          >
            💾 Save Block
          </button>
          <div class="w-px h-4 bg-slate-700"></div>
          <button
            class="px-3 py-1.5 rounded-md border border-blue-600/40 bg-blue-900/20 text-blue-300 hover:bg-blue-900/40 text-xs font-medium transition-colors"
            @click="openPlanManager"
            title="Manage your saved plans"
          >
            📂 My Plans
          </button>
          <button
            class="px-3 py-1.5 rounded-md border border-slate-700 hover:bg-slate-800 text-xs font-medium transition-colors"
            @click="saveVersion"
            title="Save version snapshot"
          >
            📸 Snapshot
          </button>
        </div>

        <!-- Droite: Presets rapides -->
        <div class="flex items-center gap-2">
          <span class="text-xs uppercase tracking-wide text-slate-500 font-semibold">Quick Setup</span>
          <button
            class="px-3 py-1.5 rounded-md border border-slate-700 hover:bg-slate-800 text-xs font-medium transition-colors"
            @click="applyPreset('classic40')"
            title="40-player Classic raid (5T, 10H, 25DPS)"
          >
            🏛️ Classic 40
          </button>
          <button
            class="px-3 py-1.5 rounded-md border border-slate-700 hover:bg-slate-800 text-xs font-medium transition-colors"
            @click="applyPreset('mythic20')"
            title="20-player Mythic raid (2T, 4H, 14DPS)"
          >
            ⚔️ Mythic 20
          </button>
        </div>
      </div>
    </header>

    <!-- Composition Summary Bar -->
    <div class="flex items-center justify-between px-4 py-2 border-b border-slate-800 bg-slate-950/60">
      <div class="flex items-center gap-3">
        <span class="text-xs uppercase tracking-wider text-slate-500 font-semibold">Composition</span>
        <div class="flex items-center gap-1.5">
          <button
            class="px-2.5 py-1 rounded-md border border-blue-500/40 bg-blue-500/10 hover:bg-blue-500/20 text-xs font-medium transition-colors flex items-center gap-1.5"
            @click="setRosterFilter('Tanks')"
            title="Click to filter roster by Tanks"
          >
            <span class="text-blue-400">🛡️</span>
            <span class="text-slate-300">{{ summary.tanks }}</span>
          </button>
          <button
            class="px-2.5 py-1 rounded-md border border-green-500/40 bg-green-500/10 hover:bg-green-500/20 text-xs font-medium transition-colors flex items-center gap-1.5"
            @click="setRosterFilter('Healers')"
            title="Click to filter roster by Healers"
          >
            <span class="text-green-400">✚</span>
            <span class="text-slate-300">{{ summary.healers }}</span>
          </button>
          <button
            class="px-2.5 py-1 rounded-md border border-red-500/40 bg-red-500/10 hover:bg-red-500/20 text-xs font-medium transition-colors flex items-center gap-1.5"
            @click="setRosterFilter('Melee')"
            title="Click to filter roster by Melee DPS"
          >
            <span class="text-red-400">⚔️</span>
            <span class="text-slate-300">{{ summary.melee }}</span>
          </button>
          <button
            class="px-2.5 py-1 rounded-md border border-amber-500/40 bg-amber-500/10 hover:bg-amber-500/20 text-xs font-medium transition-colors flex items-center gap-1.5"
            @click="setRosterFilter('Ranged')"
            title="Click to filter roster by Ranged DPS"
          >
            <span class="text-amber-400">🏹</span>
            <span class="text-slate-300">{{ summary.ranged }}</span>
          </button>
        </div>
        <div class="w-px h-4 bg-slate-700"></div>
        <span class="text-xs font-medium" :class="summary.missingText?.includes('✅') ? 'text-emerald-400' : 'text-amber-400'">
          {{ summary.missingText || 'No expected composition set' }}
        </span>
      </div>

      <!-- Total assignments -->
      <div class="flex items-center gap-2">
        <span class="text-xs text-slate-400">
          Total: <span class="font-semibold text-slate-300">{{ summary.tanks + summary.healers + summary.melee + summary.ranged }}</span>
        </span>
      </div>
    </div>

    
    <div class="flex flex-1 overflow-hidden">
      
      <aside class="w-72 border-r border-slate-800 bg-slate-950/60 p-3 overflow-y-auto space-y-4">
        <BlockSidebar @add-block="addBlock" />

        
        <section v-if="blockTemplates.length">
          <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">
            💾 Saved Templates
          </h3>
          <div class="space-y-1">
            <div
              v-for="tpl in blockTemplates"
              :key="tpl.id"
              class="group flex items-center justify-between px-2 py-1.5 rounded-md hover:bg-slate-800/80 text-sm"
            >
              <button class="flex-1 text-left truncate" @click="loadBlockTemplate(tpl); blockTemplates = getBlockTemplates()">
                {{ tpl.name }}
              </button>
              <button
                class="opacity-0 group-hover:opacity-100 text-red-400 hover:text-red-300 text-xs px-1"
                @click="deleteBlockTemplate(tpl.id); blockTemplates = getBlockTemplates()"
                title="Delete template"
              >
                ✕
              </button>
            </div>
          </div>
        </section>
      </aside>

      
      <main class="flex-1 p-4 overflow-auto">
        <RaidPlanCanvas
          ref="canvasRef"
          :blocks="blocks"
          :characters="characters"
          :group-targets="groupTargets"
          @update-block="updateBlock"
          @remove-block="removeBlock"
          @reorder-blocks="reorderBlocks"
        />
      </main>

      
      <aside class="w-80 border-l border-slate-800 bg-slate-950/60 p-3 overflow-y-auto">
        <CharacterSidebar
          ref="sidebarRef"
          :characters="characters"
          :loading="loadingChars"
          :error="charsError"
          @quick-assign="(c) => canvasRef?.assignToSelected?.(c.id)"
        />
      </aside>
    </div>


    <BaseModal v-model="showHistory" title="📜 Version History" size="lg">
      <div class="space-y-3">
        <p class="text-sm text-slate-400 mb-4">
          Restore previous snapshots of your raid plan. Snapshots are saved locally (max 20).
        </p>
        <div class="space-y-2 max-h-[400px] overflow-y-auto pr-2">
          <div
            v-for="v in getVersions()"
            :key="v.id"
            class="group flex items-start justify-between rounded-lg border border-slate-700 bg-slate-900/60 hover:bg-slate-900/80 px-4 py-3 transition-all"
          >
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-1">
                <h3 class="font-semibold text-slate-100 truncate">{{ v.name }}</h3>
                <span class="px-2 py-0.5 rounded-md bg-slate-800/60 text-slate-400 text-xs font-medium">
                  {{ v.count }} blocks
                </span>
              </div>
              <div class="flex items-center gap-3 text-xs text-slate-400">
                <span>🕒 {{ formatRelativeTime(v.ts) }}</span>
                <span>•</span>
                <span>{{ new Date(v.ts).toLocaleString() }}</span>
              </div>
            </div>
            <div class="flex items-center gap-2">
              <button
                class="px-3 py-1.5 rounded-md border border-emerald-600/50 bg-emerald-900/30 text-emerald-300 hover:bg-emerald-900/50 text-sm font-medium transition-colors"
                @click="restoreVersion(v.id)"
                title="Restore this snapshot"
              >
                📂 Restore
              </button>
              <button
                class="px-3 py-1.5 rounded-md border border-red-600/50 bg-red-900/30 text-red-300 hover:bg-red-900/50 text-sm font-medium transition-colors"
                @click="deleteVersion(v.id)"
                title="Delete this snapshot"
              >
                🗑️
              </button>
            </div>
          </div>
          <div v-if="!getVersions().length" class="text-center py-12">
            <div class="text-6xl mb-3">📸</div>
            <div class="text-lg font-medium text-slate-300 mb-1">No snapshots yet</div>
            <div class="text-sm text-slate-400">Click the "📸 Snapshot" button to save a version of your plan</div>
          </div>
        </div>
      </div>
      <template #footer>
        <div class="flex items-center justify-between">
          <span class="text-xs text-slate-400">
            {{ getVersions().length }} of 20 snapshots saved
          </span>
          <button class="px-3 py-1.5 text-sm rounded-md border border-slate-700 hover:bg-slate-800" @click="showHistory = false">Close</button>
        </div>
      </template>
    </BaseModal>

    
    <BaseModal v-model="showTemplates" title="Templates" size="lg">
      <TemplatesPanel :current-name="planName" :blocks="blocks" @close="showTemplates = false" @load="loadTemplate" />
    </BaseModal>

    
    <BaseModal v-model="showSaveTemplate" title="Save Block as Template" size="md">
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-300 mb-2">Template Name</label>
          <input
            v-model="templateName"
            type="text"
            class="w-full bg-slate-900 border border-slate-700 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
            placeholder="e.g., My Cooldown Rotation Setup"
            @keyup.enter="confirmSaveTemplate"
          />
        </div>
        <p class="text-xs text-slate-400">
          This will save the current block configuration (layout, data, settings) as a reusable template. You can load it later from the sidebar.
        </p>
      </div>
      <template #footer>
        <div class="flex justify-end gap-2">
          <button class="px-3 py-1.5 text-sm rounded-md border border-slate-700 hover:bg-slate-800" @click="showSaveTemplate = false">Cancel</button>
          <button class="px-3 py-1.5 text-sm rounded-md bg-emerald-600 hover:bg-emerald-500 font-medium" @click="confirmSaveTemplate">Save Template</button>
        </div>
      </template>
    </BaseModal>

    
    <BaseModal v-model="showHelp" title="Keyboard Shortcuts & Tips" size="lg">
      <div class="space-y-4 text-sm">
        <section>
          <h3 class="text-base font-semibold text-emerald-400 mb-2">⌨️ Keyboard Shortcuts</h3>
          <div class="space-y-2">
            <div class="flex items-center justify-between py-1 px-2 rounded bg-slate-900/60">
              <span class="text-slate-300">Undo</span>
              <kbd class="px-2 py-1 text-xs rounded bg-slate-800 border border-slate-700 font-mono">Ctrl/Cmd + Z</kbd>
            </div>
            <div class="flex items-center justify-between py-1 px-2 rounded bg-slate-900/60">
              <span class="text-slate-300">Redo</span>
              <kbd class="px-2 py-1 text-xs rounded bg-slate-800 border border-slate-700 font-mono">Ctrl/Cmd + Y</kbd>
            </div>
            <div class="flex items-center justify-between py-1 px-2 rounded bg-slate-900/60">
              <span class="text-slate-300">Move selected block left/right</span>
              <kbd class="px-2 py-1 text-xs rounded bg-slate-800 border border-slate-700 font-mono">← →</kbd>
            </div>
            <div class="flex items-center justify-between py-1 px-2 rounded bg-slate-900/60">
              <span class="text-slate-300">Move selected block up/down (row)</span>
              <kbd class="px-2 py-1 text-xs rounded bg-slate-800 border border-slate-700 font-mono">↑ ↓</kbd>
            </div>
            <div class="flex items-center justify-between py-1 px-2 rounded bg-slate-900/60">
              <span class="text-slate-300">Move with larger steps</span>
              <kbd class="px-2 py-1 text-xs rounded bg-slate-800 border border-slate-700 font-mono">Shift + Arrows</kbd>
            </div>
          </div>
        </section>

        <section>
          <h3 class="text-base font-semibold text-emerald-400 mb-2">🎯 Quick Tips</h3>
          <ul class="space-y-2 text-slate-300">
            <li class="flex items-start gap-2">
              <span class="text-emerald-500">•</span>
              <span><strong>Click a block</strong> to select it and see layout controls</span>
            </li>
            <li class="flex items-start gap-2">
              <span class="text-emerald-500">•</span>
              <span><strong>Drag the ☰ handle</strong> to reorder blocks vertically</span>
            </li>
            <li class="flex items-start gap-2">
              <span class="text-emerald-500">•</span>
              <span><strong>Drag characters</strong> from the right sidebar into assignment blocks</span>
            </li>
            <li class="flex items-start gap-2">
              <span class="text-emerald-500">•</span>
              <span><strong>Double-click a character</strong> to quick-assign to selected block</span>
            </li>
            <li class="flex items-start gap-2">
              <span class="text-emerald-500">•</span>
              <span><strong>Click summary chips</strong> (T, H, M, R) to filter roster by role</span>
            </li>
            <li class="flex items-start gap-2">
              <span class="text-emerald-500">•</span>
              <span><strong>Use presets</strong> (Classic 40 / Mythic 20) for instant raid setup</span>
            </li>
            <li class="flex items-start gap-2">
              <span class="text-emerald-500">•</span>
              <span><strong>Autosave</strong> runs every 1.2 seconds — your work is safe!</span>
            </li>
          </ul>
        </section>

        <section>
          <h3 class="text-base font-semibold text-emerald-400 mb-2">📐 Block Layout</h3>
          <ul class="space-y-2 text-slate-300">
            <li class="flex items-start gap-2">
              <span class="text-blue-400">•</span>
              <span><strong>Start</strong>: Column position (1-12) where block begins</span>
            </li>
            <li class="flex items-start gap-2">
              <span class="text-blue-400">•</span>
              <span><strong>Span</strong>: Number of columns (2-12) block occupies</span>
            </li>
            <li class="flex items-start gap-2">
              <span class="text-blue-400">•</span>
              <span><strong>Row</strong>: Manual row number (0 = auto-flow)</span>
            </li>
            <li class="flex items-start gap-2">
              <span class="text-blue-400">•</span>
              <span><strong>Tip</strong>: Use Span=6 for side-by-side, Span=4 for three columns</span>
            </li>
          </ul>
        </section>

        <section>
          <h3 class="text-base font-semibold text-emerald-400 mb-2">🗂️ Block Types</h3>
          <div class="grid grid-cols-2 gap-2 text-xs">
            <div class="p-2 rounded bg-slate-900/60 border border-slate-700">
              <div class="font-semibold text-slate-200 mb-1">Full Width (12)</div>
              <ul class="text-slate-400 space-y-0.5">
                <li>• Groups Grid</li>
                <li>• Role Matrix</li>
                <li>• Heading / Divider</li>
              </ul>
            </div>
            <div class="p-2 rounded bg-slate-900/60 border border-slate-700">
              <div class="font-semibold text-slate-200 mb-1">Half Width (6)</div>
              <ul class="text-slate-400 space-y-0.5">
                <li>• Boss Grid</li>
                <li>• Cooldown Rotation</li>
                <li>• Phase Strategy</li>
              </ul>
            </div>
            <div class="p-2 rounded bg-slate-900/60 border border-slate-700">
              <div class="font-semibold text-slate-200 mb-1">Third Width (4)</div>
              <ul class="text-slate-400 space-y-0.5">
                <li>• Text / Notes</li>
                <li>• Checklist</li>
                <li>• Bench Roster</li>
              </ul>
            </div>
            <div class="p-2 rounded bg-slate-900/60 border border-slate-700">
              <div class="font-semibold text-slate-200 mb-1">Custom</div>
              <ul class="text-slate-400 space-y-0.5">
                <li>• Interrupt Rotation</li>
                <li>• Custom Section</li>
              </ul>
            </div>
          </div>
        </section>
      </div>
      <template #footer>
        <div class="flex justify-end">
          <button class="px-3 py-1.5 text-sm rounded-md border border-slate-700 hover:bg-slate-800" @click="showHelp = false">Close</button>
        </div>
      </template>
    </BaseModal>

    
    <BossLibraryModal
      v-model="showBossLibrary"
      @select-boss="applyBossData"
    />

    
    <BaseModal v-model="shareOpen" title="Share raid plan (read-only)" size="md">
      <div class="space-y-3">
        <p class="text-sm text-slate-300">Anyone with this link can view the plan. They cannot edit it.</p>
        <div class="flex items-center gap-2">
          <input :value="shareUrl" readonly class="flex-1 bg-slate-900/80 border border-slate-700 rounded-md px-2 py-1 text-sm" />
          <button class="px-2 py-1 text-sm rounded bg-white/10 hover:bg-white/15" @click="navigator.clipboard.writeText(shareUrl)">Copy</button>
          <a :href="shareUrl" target="_blank" class="px-2 py-1 text-sm rounded bg-emerald-600 hover:bg-emerald-500">Open</a>
        </div>
        <div class="flex items-center justify-between text-xs text-slate-400">
          <span>Revoke access to disable the public link.</span>
          <button
            class="px-2 py-1 rounded bg-red-600/20 text-red-300 ring-1 ring-inset ring-red-600/40 hover:bg-red-600/30 disabled:opacity-50"
            :disabled="!planId"
            @click="async () => { if (!planId) return; try { await revokeShareLink(planId); shareUrl = ''; window.dispatchEvent(new CustomEvent('app:toast', { detail: { type: 'success', message: 'Public link revoked' } })) } catch (e) { window.dispatchEvent(new CustomEvent('app:toast', { detail: { type: 'error', message: 'Failed to revoke link' } })) } }"
          >Unshare</button>
        </div>
        <p class="text-xs text-slate-500">Tip: You can revoke access anytime by updating the plan to private (feature coming soon).</p>
      </div>
      <template #footer>
        <div class="flex justify-end">
          <button class="px-3 py-1.5 text-sm rounded-md border border-slate-700 hover:bg-slate-800" @click="shareOpen = false">Close</button>
        </div>
      </template>
    </BaseModal>


    <PlanManagerModal
      v-if="showPlanManager && guildId"
      :guild-id="guildId"
      :current-plan-id="planId"
      @close="showPlanManager = false"
      @load="handleLoadPlan"
      @create-new="handleCreateNew"
    />
  </div>
</template>
