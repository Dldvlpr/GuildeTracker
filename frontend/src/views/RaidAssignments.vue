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
import SmartAssignModal from '@/components/SmartAssignModal.vue';
import type { RaidPlanBlock, BlockType } from '@/interfaces/raidPlan.interface.ts';
import type { Character } from '@/interfaces/game.interface';
import { Role } from '@/interfaces/game.interface';
import { getCharactersByGuildId } from '@/services/character.service';
import { getRoleByClassAndSpec } from '@/data/gameData';
import { createRaidPlan, updateRaidPlan, generateShareLink, revokeShareLink, getRaidPlan, type RaidPlanDTO } from '@/services/raidPlan.service'
import { getGameGuild } from '@/services/gameGuild.service'
import type { GameGuild } from '@/interfaces/GameGuild.interface'
import type { RaidBoss } from '@/data/raidData';

const planName = ref('My Raid Plan');
const planRaidName = ref<string>('');
const planFolder = ref<string>('');
const route = useRoute();
const guildId = ref<string | null>(null);
const characters = ref<Character[]>([]);
const loadingChars = ref(false);
const charsError = ref<string | null>(null);

let nextId = 1;
let bootstrapToken = 0;
const blocks = ref<RaidPlanBlock[]>([]);

const draftKey = ref<string>('');
const versionsKey = ref<string>('');
const history: RaidPlanBlock[][] = [];
const historySignatures: string[] = [];
const HISTORY_LIMIT = 50;
let historyIndex = -1;
let historyMuteDepth = 0;
let autosaveTimer: number | null = null;
const showHistory = ref(false);
const showTemplates = ref(false);
const saving = ref(false);
const lastSavedAt = ref<number | null>(null);
const canvasRef = ref<any>(null);
  const toolsMenuOpen = ref(false);
  const toolsMenuRef = ref<HTMLElement | null>(null);
const showHelp = ref(false);
const showLeftPanel = ref(true);
const showRightPanel = ref(true);
const showSaveTemplate = ref(false);
const templateName = ref('');
const showBossLibrary = ref(false);
const shareOpen = ref(false)
const shareUrl = ref('')
const planId = ref<number | null>(null)
const planIsPublic = ref(false)

function planIdKey(gid: string) {
  return `raidPlan:planId:${gid}`
}

function resolveGuildId(param: unknown): string | null {
  if (typeof param === 'string') return param
  if (Array.isArray(param) && param.length) return param[0]
  return null
}

function currentRouteGuildId(): string | null {
  return resolveGuildId(route.params.id)
}

const showPlanManager = ref(false)
const showSmartAssign = ref(false)

// Permissions
const guild = ref<GameGuild | null>(null)
const myRole = computed(() => guild.value?.myRole || 'GM')
const canEdit = computed(() => myRole.value === 'GM' || myRole.value === 'Officer')

function openPlanManager() {
  if (!guildId.value) return
  showPlanManager.value = true
}

function openSmartAssign() {
  showSmartAssign.value = true
}

function resetHistory() {
  history.length = 0
  historySignatures.length = 0
  historyIndex = -1
  pushHistorySnapshot(true)
}

function handleLoadPlan(p: RaidPlanDTO, opts?: { silent?: boolean }) {
  planId.value = p.id
  planName.value = p.name
  planRaidName.value = p.raidName || ''
  planIsPublic.value = Boolean(p.isPublic)
  if (!planIsPublic.value) {
    shareUrl.value = ''
  }
  try { planFolder.value = (p.metadata as any)?.folder || '' } catch { planFolder.value = '' }
  runWithoutHistory(() => {
    blocks.value = JSON.parse(JSON.stringify(p.blocks))
  })
  try { if (guildId.value) localStorage.setItem(planIdKey(guildId.value), String(p.id)) } catch {}
  resetHistory()
  if (!opts?.silent) {
    window.dispatchEvent(new CustomEvent('app:toast', { detail: { type: 'success', message: `Loaded "${p.name}"` } }))
  }
}

async function loadPlanFromServer(id: number, token?: number) {
  try {
    const plan = await getRaidPlan(id)
    if (token && token !== bootstrapToken) return
    handleLoadPlan(plan, { silent: true })
  } catch (e: any) {
    if (token && token !== bootstrapToken) return
    window.dispatchEvent(new CustomEvent('app:toast', { detail: { type: 'error', message: e?.message || 'Failed to load plan from server' } }))
    if (guildId.value) {
      localStorage.removeItem(planIdKey(guildId.value))
    }
    planId.value = null
    planIsPublic.value = false
    shareUrl.value = ''
  }
}

async function bootstrapGuildContext(id: string) {
  const token = ++bootstrapToken
  guildId.value = id
  makeKeys(id)
  shareUrl.value = ''
  planIsPublic.value = false
  planName.value = 'My Raid Plan'
  planRaidName.value = ''
  planFolder.value = ''

  let storedPlanId: number | null = null
  try {
    const stored = localStorage.getItem(planIdKey(id))
    if (stored) {
      const parsed = parseInt(stored, 10)
      if (Number.isFinite(parsed)) storedPlanId = parsed
    }
  } catch {}

  planId.value = storedPlanId
  runWithoutHistory(() => {
    blocks.value = []
  })
  loadDraft()
  recalcNextId()
  resetHistory()

  loadingChars.value = true
  charsError.value = null

  try {
    const g = await getGameGuild(id)
    if (token !== bootstrapToken) return
    if (g.ok) {
      guild.value = g.data
    } else {
      guild.value = null
    }
  } catch {
    if (token !== bootstrapToken) return
    guild.value = null
  }

  const res = await getCharactersByGuildId(id).catch((e) => ({ ok: false, error: e?.message || 'Network error' } as const))
  if (token !== bootstrapToken) return
  loadingChars.value = false
  if (!res.ok) {
    characters.value = generateMockRoster()
    charsError.value = res.error || 'Using local mock roster'
  } else {
    characters.value = res.data
    charsError.value = null
  }

  if (planId.value != null) {
    await loadPlanFromServer(planId.value, token)
    if (token !== bootstrapToken) return
  }
}

function handleCreateNew() {
  const confirmed = confirm('Create a new plan? Current unsaved changes will be lost.');
  if (!confirmed) return;

  planId.value = null
  planName.value = 'New Raid Plan'
  planRaidName.value = ''
  planFolder.value = ''
  planIsPublic.value = false
  shareUrl.value = ''
  runWithoutHistory(() => {
    blocks.value = []
  })
  try { if (guildId.value) localStorage.removeItem(planIdKey(guildId.value)) } catch {}
  resetHistory()
  window.dispatchEvent(new CustomEvent('app:toast', { detail: { type: 'success', message: 'New plan created' } }))
}

const preset = ref<'raid40' | 'raid25' | 'raid20' | 'raid10' | 'custom'>('custom');
const expected = ref<{ tanks: number; healers: number; dps: number }>({ tanks: 0, healers: 0, dps: 0 });
const summary = ref<{ tanks: number; healers: number; melee: number; ranged: number; missingText: string | null }>({ tanks: 0, healers: 0, melee: 0, ranged: 0, missingText: null });

function makeKeys(id: string) {
  draftKey.value = `raidPlan:draft:${id}`;
  versionsKey.value = `raidPlan:versions:${id}`;
}

function cloneBlocks(): RaidPlanBlock[] {
  return JSON.parse(JSON.stringify(blocks.value));
}

function runWithoutHistory(mutator: () => void) {
  historyMuteDepth++;
  try {
    mutator();
  } finally {
    Promise.resolve().then(() => {
      historyMuteDepth = Math.max(0, historyMuteDepth - 1);
    });
  }
}

function pushHistorySnapshot(force = false) {
  const serialized = JSON.stringify(blocks.value);
  if (!force && historySignatures[historyIndex] === serialized) {
    return;
  }

  const snapshot = JSON.parse(serialized);

  if (historyIndex < history.length - 1) {
    history.splice(historyIndex + 1);
    historySignatures.splice(historyIndex + 1);
  }

  history.push(snapshot);
  historySignatures.push(serialized);

  if (history.length > HISTORY_LIMIT) {
    history.shift();
    historySignatures.shift();
    historyIndex = history.length - 1;
  } else {
    historyIndex = history.length - 1;
  }
}

function undo() {
  if (historyIndex <= 0) return;
  historyIndex--;
  const snapshot = history[historyIndex] ?? [];
  runWithoutHistory(() => {
    blocks.value = JSON.parse(JSON.stringify(snapshot));
  })
}

function redo() {
  if (historyIndex >= history.length - 1) return;
  historyIndex++;
  const snapshot = history[historyIndex] ?? [];
  runWithoutHistory(() => {
    blocks.value = JSON.parse(JSON.stringify(snapshot));
  })
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
  if (!canEdit.value) {
    window.dispatchEvent(new CustomEvent('app:toast', { detail: { type: 'error', message: 'You do not have permission to save this plan' } }))
    return
  }
  try {
    if (planId.value == null) {
      const created = await createRaidPlan({
        guildId: guildId.value,
        name: planName.value || 'Untitled Raid Plan',
        blocks: cloneBlocks(),
        raidName: planRaidName.value || undefined,
        metadata: { folder: planFolder.value || null },
      })
      planId.value = created.id
      planIsPublic.value = Boolean(created.isPublic)
      if (!planIsPublic.value) shareUrl.value = ''
      localStorage.setItem(planIdKey(guildId.value), String(created.id))
      window.dispatchEvent(new CustomEvent('app:toast', { detail: { type: 'success', message: 'Raid plan created' } }))
    } else {
      const updated: RaidPlanDTO = await updateRaidPlan(planId.value, {
        name: planName.value || 'Untitled Raid Plan',
        blocks: cloneBlocks(),
        raidName: planRaidName.value || null,
        metadata: { folder: planFolder.value || null },
      })
      planIsPublic.value = Boolean(updated.isPublic)
      if (!planIsPublic.value) shareUrl.value = ''
      window.dispatchEvent(new CustomEvent('app:toast', { detail: { type: 'success', message: 'Raid plan saved' } }))
    }
  } catch (e: any) {
    window.dispatchEvent(new CustomEvent('app:toast', { detail: { type: 'error', message: e?.message || 'Failed to save plan' } }))
  }
}

async function ensureShareLink(): Promise<string | null> {
  if (!guildId.value) return null
  if (planId.value == null) {
    await saveToDatabase()
  }
  if (planId.value == null) return null
  const { shareUrl: url } = await generateShareLink(planId.value)
  shareUrl.value = url
  planIsPublic.value = true
  return url
}

async function openShare() {
  if (!guildId.value) return
  if (!canEdit.value) {
    window.dispatchEvent(new CustomEvent('app:toast', { detail: { type: 'error', message: 'You do not have permission to share this plan' } }))
    return
  }
  try {
    const url = await ensureShareLink()
    if (!url) return
    shareOpen.value = true
  } catch (e: any) {
    window.dispatchEvent(new CustomEvent('app:toast', { detail: { type: 'error', message: e?.message || 'Failed to generate share link' } }))
  }
}

async function previewPublic() {
  try {
    if (!guildId.value) return
    if (!canEdit.value) {
      window.dispatchEvent(new CustomEvent('app:toast', { detail: { type: 'error', message: 'You do not have permission to share this plan' } }))
      return
    }
    if (!planIsPublic.value) {
      const confirmed = confirm('Previewing will enable a public link that anyone can view. Continue?')
      if (!confirmed) return
    }
    const url = await ensureShareLink()
    if (!url) return
    window.open(url, '_blank', 'noopener')
  } catch (e: any) {
    window.dispatchEvent(new CustomEvent('app:toast', { detail: { type: 'error', message: e?.message || 'Failed to open preview' } }))
  }
}

async function handleRevokeShare() {
  if (!planId.value) return
  try {
    await revokeShareLink(planId.value)
    planIsPublic.value = false
    shareUrl.value = ''
    window.dispatchEvent(new CustomEvent('app:toast', { detail: { type: 'success', message: 'Public link revoked' } }))
  } catch (e: any) {
    window.dispatchEvent(new CustomEvent('app:toast', { detail: { type: 'error', message: e?.message || 'Failed to revoke link' } }))
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
      runWithoutHistory(() => {
        blocks.value = json.blocks;
      })
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

  runWithoutHistory(() => {
    blocks.value = JSON.parse(JSON.stringify(v.blocks ?? []));
  })
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

async function quickStartCreate(p?: 'raid40' | 'raid25' | 'raid20' | 'raid10') {
  try {
    if (p) applyPreset(p)
    if (!planName.value.trim()) planName.value = 'New Raid Plan'
    await saveToDatabase()
    window.scrollTo({ top: 0, behavior: 'smooth' })
  } catch (e: any) {
    window.dispatchEvent(new CustomEvent('app:toast', { detail: { type: 'error', message: e?.message || 'Failed to create plan' } }))
  }
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
    case 'COOLDOWN_ROTATION':
    case 'INTERRUPT_ROTATION':
    case 'BOSS_GRID':
      return 12;

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

function resetToOnboarding() {
  planId.value = null
  planName.value = 'My Raid Plan'
  planRaidName.value = ''
  planFolder.value = ''
  planIsPublic.value = false
  shareUrl.value = ''
  runWithoutHistory(() => {
    blocks.value = []
  })
  try { if (guildId.value) localStorage.removeItem(planIdKey(guildId.value)) } catch {}
  resetHistory()
  window.dispatchEvent(new CustomEvent('app:toast', { detail: { type: 'success', message: 'Plan deleted — Onboarding reset' } }))
}

onMounted(async () => {
  window.addEventListener('keydown', onKey);
  try {
    const l = localStorage.getItem('raidPlan:ui:leftOpen');
    const r = localStorage.getItem('raidPlan:ui:rightOpen');
    if (l !== null) showLeftPanel.value = l === '1';
    if (r !== null) showRightPanel.value = r === '1';
  } catch {}

  const onDocClick = (e: MouseEvent) => {
    const el = toolsMenuRef.value as HTMLElement | null
    if (!el) return
    const target = e.target as Node
    if (toolsMenuOpen.value && el && !el.contains(target)) toolsMenuOpen.value = false
  }
  const onDocKey = (e: KeyboardEvent) => {
    if (e.key === 'Escape' && toolsMenuOpen.value) toolsMenuOpen.value = false
  }
  document.addEventListener('click', onDocClick)
  document.addEventListener('keydown', onDocKey)
  ;(window as any).__raid_onDocClick = onDocClick
  ;(window as any).__raid_onDocKey = onDocKey

  const initialGuildId = currentRouteGuildId()
  if (initialGuildId) {
    await bootstrapGuildContext(initialGuildId)
  }
});

onUnmounted(() => {
  window.removeEventListener('keydown', onKey);
  const onDocClick = (window as any).__raid_onDocClick as ((e: MouseEvent) => void) | undefined
  const onDocKey = (window as any).__raid_onDocKey as ((e: KeyboardEvent) => void) | undefined
  if (onDocClick) document.removeEventListener('click', onDocClick)
  if (onDocKey) document.removeEventListener('keydown', onDocKey)
});

watch(showLeftPanel, (v) => {
  try { localStorage.setItem('raidPlan:ui:leftOpen', v ? '1' : '0') } catch {}
});
watch(showRightPanel, (v) => {
  try { localStorage.setItem('raidPlan:ui:rightOpen', v ? '1' : '0') } catch {}
});

watch(blocks, () => {
  if (historyMuteDepth > 0) return;
  pushHistorySnapshot();
  scheduleAutosave();
}, { deep: true });
watch(planName, () => scheduleAutosave());
watch([blocks, characters, expected], () => computeSummary(), { deep: true });
watch(() => route.params.id, (param) => {
  const next = resolveGuildId(param)
  if (!next || next === guildId.value) return
  bootstrapGuildContext(next)
});

function recalcNextId() {
  const maxId = blocks.value.reduce((acc, b) => {
    const n = parseInt(b.id, 10);
    return Number.isFinite(n) ? Math.max(acc, n) : acc;
  }, 0);
  nextId = maxId + 1;
}

function loadTemplate(b: RaidPlanBlock[]) {
  runWithoutHistory(() => {
    blocks.value = JSON.parse(JSON.stringify(b));
  })
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

const benchedIds = computed<Set<string>>(() => {
  const set = new Set<string>();
  for (const b of blocks.value) {
    if (b.type === 'BENCH_ROSTER') {
      for (const id of ((b.data as any)?.bench ?? []) as string[]) set.add(String(id));
    }
  }
  return set;
});

const sidebarCharacters = computed(() => {
  const benched = benchedIds.value;
  return characters.value.filter(c => !benched.has(c.id));
});


function computeSummary() {
  let t = 0, h = 0, m = 0, r = 0;

  // Map charId -> Role (prefer ROLE_MATRIX assignment, then derived from override in GROUPS_GRID)
  const roleByChar = new Map<string, Role>();

  for (const b of blocks.value) {
    if (b.type === 'ROLE_MATRIX') {
      const ra = (b.data?.roleAssignments ?? {}) as Record<Role, string[]>;
      (['Tanks','Healers','Melee','Ranged'] as Role[]).forEach((rr) => {
        for (const id of (ra?.[rr] ?? [])) {
          roleByChar.set(String(id), rr);
        }
      });
    }
  }

  for (const b of blocks.value) {
    if (b.type === 'GROUPS_GRID') {
      const groups = (b.data?.groups ?? []) as { id: string; title: string; members: string[] }[];
      for (const g of groups) {
        for (const id of g.members || []) {
          const key = String(id);
          if (roleByChar.has(key)) continue; // keep ROLE_MATRIX priority
          const c = characters.value.find((cc) => cc.id === key);
          if (!c) continue;
          const ov = (b.data?.specOverrides as Record<string, string> | undefined)?.[key];
          const sp = ov || c.spec;
          let derived: Role | undefined;
          try { if (sp && c.class) derived = getRoleByClassAndSpec(c.class, sp) as Role | undefined } catch {}
          const finalRole = derived || (c.role as Role | undefined);
          if (finalRole) roleByChar.set(key, finalRole);
        }
      }
    }
  }

  for (const [, role] of roleByChar) {
    if (role === 'Tanks') t++;
    else if (role === 'Healers') h++;
    else if (role === 'Melee') m++;
    else if (role === 'Ranged') r++;
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

function applyPreset(p: 'raid40' | 'raid25' | 'raid20' | 'raid10') {
  preset.value = p;
  if (p === 'raid40') {
    expected.value = { tanks: 5, healers: 10, dps: 25 };
    ensureGroupsBlock(8, 5);
  } else if (p === 'raid25') {
    expected.value = { tanks: 2, healers: 6, dps: 17 };
    ensureGroupsBlock(5, 5);
  } else if (p === 'raid20') {
    expected.value = { tanks: 2, healers: 4, dps: 14 };
    ensureGroupsBlock(4, 5);
  } else if (p === 'raid10') {
    expected.value = { tanks: 2, healers: 3, dps: 5 };
    ensureGroupsBlock(2, 5);
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
  switch (preset.value) {
    case 'raid40': return { tank: 1, healer: 2 };
    case 'raid25': return { tank: 0, healer: 1 };
    case 'raid20': return { tank: 0, healer: 1 };
    case 'raid10': return { tank: 0, healer: 1 };
    default: return null;
  }
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

function refreshBlockTemplates() {
  blockTemplates.value = getBlockTemplates();
}

function saveBlockAsTemplate() {
  if (!canvasRef.value?.selectedBlockId) {
    window.dispatchEvent(new CustomEvent('app:toast', { detail: { type: 'error', message: 'Select a block first' } }))
    return;
  }

  const blockId = canvasRef.value.selectedBlockId;
  const block = blocks.value.find(b => b.id === blockId);
  if (!block) return;

  showSaveTemplate.value = true;
}

function confirmSaveTemplate() {
  if (!templateName.value.trim()) {
    window.dispatchEvent(new CustomEvent('app:toast', { detail: { type: 'error', message: 'Please enter a template name' } }))
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
  refreshBlockTemplates();
  window.dispatchEvent(new CustomEvent('app:toast', { detail: { type: 'success', message: `Template "${newTemplate.name}" saved` } }))
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
  refreshBlockTemplates();
  window.dispatchEvent(new CustomEvent('app:toast', { detail: { type: 'success', message: 'Template removed' } }))
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
    <header class="relative z-40 border-b border-slate-800 bg-slate-950/90 backdrop-blur-sm shadow-lg">
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
            <div class="flex items-center gap-1 px-1 py-1 rounded-lg bg-slate-900/60 border border-slate-700/50">
              <button
                class="px-2 py-1 rounded hover:bg-slate-800 text-xs transition-colors"
                @click="showLeftPanel = !showLeftPanel"
                :title="showLeftPanel ? 'Hide Blocks panel' : 'Show Blocks panel'"
              >
                🧱 {{ showLeftPanel ? 'Hide Blocks' : 'Show Blocks' }}
              </button>
              <div class="w-px h-6 bg-slate-700"></div>
              <button
                class="px-2 py-1 rounded hover:bg-slate-800 text-xs transition-colors"
                @click="showRightPanel = !showRightPanel"
                :title="showRightPanel ? 'Hide Roster panel' : 'Show Roster panel'"
              >
                🧑‍🤝‍🧑 {{ showRightPanel ? 'Hide Roster' : 'Show Roster' }}
              </button>
            </div>
            <button
              class="px-4 py-2 rounded-lg border border-slate-700 bg-slate-900/60 hover:bg-slate-800 text-sm font-medium transition-colors"
              @click="openPlanManager"
              title="Manage your saved plans"
            >
              📂 My Plans
            </button>
            <button
              class="px-4 py-2 rounded-lg border border-emerald-600/50 bg-emerald-900/30 text-emerald-300 hover:bg-emerald-900/50 text-sm font-medium transition-colors"
              @click="saveToDatabase"
              :disabled="!canEdit"
              :class="{ 'opacity-40 cursor-not-allowed': !canEdit }"
              title="Save to database"
            >
              💾 Save
            </button>
            <button
              class="px-4 py-2 rounded-lg border border-blue-600/50 bg-blue-900/30 text-blue-300 hover:bg-blue-900/50 text-sm font-medium transition-colors"
              @click="openShare"
              :disabled="!canEdit"
              :class="{ 'opacity-40 cursor-not-allowed': !canEdit }"
              title="Share this plan"
            >
              🔗 Share
            </button>
          </div>

          <!-- Tools menu -->
          <div class="relative" ref="toolsMenuRef">
            <button
              class="px-3 py-2 rounded-lg border border-slate-700 bg-slate-900/60 hover:bg-slate-800 text-sm font-medium transition-colors flex items-center gap-2"
              @click="toolsMenuOpen = !toolsMenuOpen"
              title="Open tools and utilities"
            >
              🧰 Tools <span class="text-xs">▼</span>
            </button>
            <div v-if="toolsMenuOpen" class="absolute right-0 mt-2 w-56 rounded-lg border border-slate-700 bg-slate-900/98 backdrop-blur shadow-2xl z-[999] overflow-hidden">
              <div class="px-3 py-2 text-[10px] uppercase tracking-wide text-slate-500">Compose</div>
              <button class="w-full text-left px-4 py-2 text-sm hover:bg-slate-800 transition-colors" @click="showBossLibrary = true; toolsMenuOpen = false">📚 Boss Library</button>
              <button class="w-full text-left px-4 py-2 text-sm hover:bg-slate-800 transition-colors" @click="showTemplates = true; toolsMenuOpen = false">📋 Templates</button>
              <button v-if="canEdit" class="w-full text-left px-4 py-2 text-sm hover:bg-slate-800 transition-colors" @click="saveBlockAsTemplate(); toolsMenuOpen = false">💾 Save Block</button>
              <button v-if="canEdit" class="w-full text-left px-4 py-2 text-sm hover:bg-slate-800 transition-colors" @click="openSmartAssign(); toolsMenuOpen = false">🧠 Smart Assign</button>
              <div class="border-t border-slate-800 my-1"></div>
              <div class="px-3 py-2 text-[10px] uppercase tracking-wide text-slate-500">Manage</div>
              <button class="w-full text-left px-4 py-2 text-sm hover:bg-slate-800 transition-colors" @click="saveVersion(); toolsMenuOpen = false">📸 Snapshot</button>
              <button class="w-full text-left px-4 py-2 text-sm hover:bg-slate-800 transition-colors" @click="showHistory = true; toolsMenuOpen = false">📜 History</button>
              <div class="border-t border-slate-800 my-1"></div>
              <div class="px-3 py-2 text-[10px] uppercase tracking-wide text-slate-500">Presets</div>
              <button class="w-full text-left px-4 py-2 text-sm hover:bg-slate-800 transition-colors" @click="applyPreset('raid40'); toolsMenuOpen = false">🏛️ Raid 40</button>
              <button class="w-full text-left px-4 py-2 text-sm hover:bg-slate-800 transition-colors" @click="applyPreset('raid25'); toolsMenuOpen = false">🏛️ Raid 25</button>
              <button class="w-full text-left px-4 py-2 text-sm hover:bg-slate-800 transition-colors" @click="applyPreset('raid20'); toolsMenuOpen = false">⚔️ Raid 20</button>
              <button class="w-full text-left px-4 py-2 text-sm hover:bg-slate-800 transition-colors" @click="applyPreset('raid10'); toolsMenuOpen = false">⚔️ Raid 10</button>
              <div class="border-t border-slate-800 my-1"></div>
              <div class="px-3 py-2 text-[10px] uppercase tracking-wide text-slate-500">Export</div>
              <button class="w-full text-left px-4 py-2 text-sm hover:bg-slate-800 transition-colors" @click="copyMarkdown(); toolsMenuOpen = false">📝 Copy Markdown</button>
              <button class="w-full text-left px-4 py-2 text-sm hover:bg-slate-800 transition-colors" @click="downloadJson(); toolsMenuOpen = false">{ } Download JSON</button>
              <button class="w-full text-left px-4 py-2 text-sm hover:bg-slate-800 transition-colors" @click="copyImage(); toolsMenuOpen = false">🖼️ Copy as Image</button>
              <button class="w-full text-left px-4 py-2 text-sm hover:bg-slate-800 transition-colors" @click="downloadPng(); toolsMenuOpen = false">💾 Download PNG</button>
              <div class="border-t border-slate-800 my-1"></div>
              <button
                class="w-full text-left px-4 py-2 text-sm hover:bg-slate-800 transition-colors"
                :class="{ 'opacity-40 cursor-not-allowed': !canEdit }"
                :disabled="!canEdit"
                @click="previewPublic(); toolsMenuOpen = false"
              >🔗 Preview public</button>
              <button class="w-full text-left px-4 py-2 text-sm hover:bg-slate-800 transition-colors" @click="showHelp = true; toolsMenuOpen = false">❓ Help & Shortcuts</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Tools row removed; consolidated into Tools menu -->

      <!-- Ligne meta plan -->
      <div class="flex items-center gap-2 px-4 py-2 border-y border-slate-800 bg-slate-950/60">
        <input v-model="planRaidName" class="px-3 py-1.5 rounded-md bg-slate-900/60 border border-slate-700 text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500" placeholder="Raid name" title="Used to group plans"/>
        <input v-model="planFolder" class="px-3 py-1.5 rounded-md bg-slate-900/60 border border-slate-700 text-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500" placeholder="Folder (optional)" title="Custom folder to organize plans"/>
      </div>
    </header>

    <!-- Onboarding banner: visible until plan is saved (no planId) -->
    <div v-if="!planId" class="mx-4 mt-3 mb-2 p-4 rounded-lg border border-amber-600/40 bg-amber-900/15 text-sm text-amber-200">
      <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div class="space-y-1">
          <div class="font-semibold text-amber-300">Create your plan to enable sharing and management</div>
          <div class="text-amber-200/90">First save creates the plan on the server. You can then Share, organize in My Plans, and collaborate.</div>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
          <button class="px-3 py-1.5 rounded-md bg-emerald-600 hover:bg-emerald-500 text-white" @click="quickStartCreate()">💾 Create Plan</button>
          <span class="text-amber-200/70 text-xs">or start with a preset:</span>
          <button class="px-2 py-1 rounded-md border border-slate-700 hover:bg-slate-800" @click="quickStartCreate('raid25')">Raid 25</button>
          <button class="px-2 py-1 rounded-md border border-slate-700 hover:bg-slate-800" @click="quickStartCreate('raid20')">Raid 20</button>
          <button class="px-2 py-1 rounded-md border border-slate-700 hover:bg-slate-800" @click="quickStartCreate('raid10')">Raid 10</button>
          <button class="px-2 py-1 rounded-md border border-slate-700 hover:bg-slate-800" @click="quickStartCreate('raid40')">Raid 40</button>
        </div>
      </div>
    </div>

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

    

    
    <div class="relative flex flex-1 overflow-hidden">
      
      <aside v-if="showLeftPanel" class="relative w-72 border-r border-slate-800 bg-slate-950/60 p-3 overflow-y-auto space-y-4">
        <BlockSidebar @add-block="addBlock" />
        <section v-if="blockTemplates.length">
          <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">💾 Saved Templates</h3>
          <div class="space-y-1">
            <div v-for="tpl in blockTemplates" :key="tpl.id" class="group flex items-center justify-between px-2 py-1.5 rounded-md hover:bg-slate-800/80 text-sm">
              <button class="flex-1 text-left truncate" @click="loadBlockTemplate(tpl)">{{ tpl.name }}</button>
              <button class="opacity-0 group-hover:opacity-100 text-red-400 hover:text-red-300 text-xs px-1" @click="deleteBlockTemplate(tpl.id)" title="Delete template">✕</button>
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
          :readonly="!canEdit"
          @update-block="updateBlock"
          @remove-block="removeBlock"
          @reorder-blocks="reorderBlocks"
        />
      </main>

      
      <aside v-if="showRightPanel" class="relative w-80 border-l border-slate-800 bg-slate-950/60 p-3 overflow-y-auto">
        <CharacterSidebar ref="sidebarRef" :characters="sidebarCharacters" :loading="loadingChars" :error="charsError" @quick-assign="(c) => canvasRef?.assignToSelected?.(c.id)" />
      </aside>
    </div>


    <SmartAssignModal
      v-model="showSmartAssign"
      :blocks="blocks"
      :characters="characters"
      :selected-block-id="(canvasRef?.selectedBlockId as string | null) || null"
      @apply="updateBlock"
    />


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

    
    <BaseModal v-model="showHelp" title="Raid Planner Guide" size="lg">
      <div class="space-y-5 text-sm">
        <section>
          <h3 class="text-base font-semibold text-emerald-400 mb-2">🚀 Getting Started</h3>
          <ol class="list-decimal list-inside space-y-1 text-slate-300">
            <li>Add <strong>Raid Groups</strong> from the left sidebar (full width).</li>
            <li>Pick a <strong>Preset</strong> in Tools → Presets (Raid 40/25/20/10).</li>
            <li>Drag players from the roster into blocks; set specs per block if needed.</li>
            <li>Use <strong>Smart Assign</strong> to auto-fill Groups/Matrix according to roles.</li>
            <li>Add <strong>Boss Assignments</strong> and <strong>Rotations</strong> (Cooldown/Interrupt).</li>
            <li><strong>Save</strong> to persist, then <strong>Share</strong> for a public read-only link.</li>
          </ol>
        </section>

        <section>
          <h3 class="text-base font-semibold text-emerald-400 mb-2">🧭 Layout Rules</h3>
          <ul class="space-y-1 text-slate-300">
            <li>Fixed-width (12): Groups, Role Matrix, Boss Assignments, Rotations, Heading/Divider.</li>
            <li>Other blocks have sensible minimum widths to keep the layout readable.</li>
            <li>Scroll inside large tables; headers stick for readability.</li>
          </ul>
        </section>

        <section>
          <h3 class="text-base font-semibold text-emerald-400 mb-2">🧰 Tools Menu</h3>
          <ul class="space-y-1 text-slate-300">
            <li><strong>Compose</strong>: Boss Library, Templates, Save Block, Smart Assign.</li>
            <li><strong>Manage</strong>: My Plans (Folder/Raid), Snapshot, History.</li>
            <li><strong>Presets</strong>: Raid 40/25/20/10.</li>
            <li><strong>Export</strong>: Copy Markdown/JSON/Image/PNG.</li>
            <li><strong>Preview public</strong>: open read-only view for sharing.</li>
          </ul>
        </section>

        <section>
          <h3 class="text-base font-semibold text-emerald-400 mb-2">⌨️ Shortcuts</h3>
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
          <h3 class="text-base font-semibold text-emerald-400 mb-2">🧭 Best Practices</h3>
          <ul class="space-y-1 text-slate-300">
            <li>Bench exclusivity: benched players sont retirés des autres assignations.</li>
            <li>Fixed-width blocks (12) gardent une lisibilité constante.</li>
            <li>Utilisez Boss Library pour pré-remplir positions et timings.</li>
            <li>Snapshots/History: enregistrez et restaurez des versions locales rapidement.</li>
          </ul>
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
            :disabled="!planId || !planIsPublic"
            @click="handleRevokeShare"
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
      @deleted-current="resetToOnboarding"
    />
  </div>
</template>
