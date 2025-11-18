<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import BaseModal from './ui/BaseModal.vue';
import { getRaidPlansByGuild, deleteRaidPlan, updateRaidPlan, type RaidPlanDTO } from '@/services/raidPlan.service';

interface Props {
  guildId: string;
  currentPlanId: number | null;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  close: [];
  load: [plan: RaidPlanDTO];
  createNew: [];
  deletedCurrent: [];
}>();

const plans = ref<RaidPlanDTO[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);
const deletingId = ref<number | null>(null);
const confirmDeleteId = ref<number | null>(null);
const sortBy = ref<'updated' | 'created' | 'name'>('updated');
const searchQuery = ref('');
const folderOpen = ref<Record<string, boolean>>({});
const raidOpen = ref<Record<string, boolean>>({});
const knownFolders = ref<Set<string>>(new Set());

onMounted(async () => {
  await loadPlans();
});

async function loadPlans() {
  loading.value = true;
  error.value = null;
  try {
    plans.value = await getRaidPlansByGuild(props.guildId);
    // Initialize known folders and open states
    knownFolders.value = new Set(
      plans.value.map(p => ((p.metadata as any)?.folder || '— No Folder —') as string)
    );
    for (const f of knownFolders.value) folderOpen.value[f] = true;
  } catch (e: any) {
    error.value = e?.message || 'Failed to load plans';
  } finally {
    loading.value = false;
  }
}

async function handleDelete(id: number) {
  if (confirmDeleteId.value !== id) {
    confirmDeleteId.value = id;
    return;
  }

  deletingId.value = id;
  try {
    await deleteRaidPlan(id);
    plans.value = plans.value.filter(p => p.id !== id);
    confirmDeleteId.value = null;

    window.dispatchEvent(new CustomEvent('app:toast', {
      detail: { type: 'success', message: 'Plan deleted successfully' }
    }));
    if (props.currentPlanId === id) {
      emit('deletedCurrent')
    }
  } catch (e: any) {
    window.dispatchEvent(new CustomEvent('app:toast', {
      detail: { type: 'error', message: e?.message || 'Failed to delete plan' }
    }));
  } finally {
    deletingId.value = null;
  }
}

function cancelDelete() {
  confirmDeleteId.value = null;
}

function handleLoad(plan: RaidPlanDTO) {
  emit('load', plan);
  emit('close');
}

function formatDate(dateStr: string): string {
  const date = new Date(dateStr);
  const now = new Date();
  const diffMs = now.getTime() - date.getTime();
  const diffMins = Math.floor(diffMs / 60000);
  const diffHours = Math.floor(diffMs / 3600000);
  const diffDays = Math.floor(diffMs / 86400000);

  if (diffMins < 1) return 'Just now';
  if (diffMins < 60) return `${diffMins}m ago`;
  if (diffHours < 24) return `${diffHours}h ago`;
  if (diffDays < 7) return `${diffDays}d ago`;

  return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

const filteredPlans = computed(() => {
  let filtered = plans.value.slice();

  // Search filter
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(p =>
      p.name.toLowerCase().includes(query) ||
      (p.raidName?.toLowerCase().includes(query) ?? false) ||
      (typeof (p.metadata as any)?.folder === 'string' && ((p.metadata as any).folder as string).toLowerCase().includes(query))
    );
  }

  // Sort by folder > raid > (criteria)
  filtered.sort((a, b) => {
    const fa = ((a.metadata as any)?.folder || '').toLowerCase();
    const fb = ((b.metadata as any)?.folder || '').toLowerCase();
    if (fa !== fb) return fa.localeCompare(fb);
    const ra = (a.raidName || '').toLowerCase();
    const rb = (b.raidName || '').toLowerCase();
    if (ra !== rb) return ra.localeCompare(rb);
    if (sortBy.value === 'updated') {
      return new Date(b.updatedAt).getTime() - new Date(a.updatedAt).getTime();
    } else if (sortBy.value === 'created') {
      return new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime();
    } else {
      return a.name.localeCompare(b.name);
    }
  });

  return filtered;
});

const grouped = computed(() => {
  // folder -> raidName -> plans[]
  const map: Record<string, Record<string, RaidPlanDTO[]>> = {};
  const current = filteredPlans.value.slice();
  for (const p of current) {
    const folder = ((p.metadata as any)?.folder || '— No Folder —') as string;
    const raid = (p.raidName || '— No Raid —') as string;
    if (!map[folder]) map[folder] = {};
    if (!map[folder][raid]) map[folder][raid] = [];
    map[folder][raid].push(p);
  }
  // Ensure empty folders appear only when not searching
  if (!searchQuery.value.trim()) {
    for (const f of knownFolders.value) {
      if (!map[f]) map[f] = {};
    }
  }
  return map;
});

const allFolderOptions = computed(() => {
  const arr = Array.from(knownFolders.value);
  if (!arr.includes('— No Folder —')) arr.unshift('— No Folder —');
  return arr;
});

function addFolder() {
  const name = prompt('New folder name');
  if (!name) return;
  knownFolders.value.add(name);
  folderOpen.value[name] = true;
}

async function movePlanToFolder(planId: number, folder: string) {
  const plan = plans.value.find(p => p.id === planId);
  if (!plan) return;
  const meta = { ...(plan.metadata || {}) } as Record<string, any>;
  meta.folder = (folder === '— No Folder —') ? null : folder;
  try {
    const updated = await updateRaidPlan(plan.id, { metadata: meta });
    const idx = plans.value.findIndex(p => p.id === plan.id);
    if (idx >= 0) plans.value[idx] = updated;
    knownFolders.value.add(folder);
    window.dispatchEvent(new CustomEvent('app:toast', { detail: { type: 'success', message: `Moved to folder "${folder}"` } }));
  } catch (e: any) {
    window.dispatchEvent(new CustomEvent('app:toast', { detail: { type: 'error', message: e?.message || 'Failed to move plan' } }));
  }
}

async function movePlanToRaid(planId: number, raid: string) {
  const plan = plans.value.find(p => p.id === planId);
  if (!plan) return;
  const raidName = raid === '— No Raid —' ? null : raid;
  try {
    const updated = await updateRaidPlan(plan.id, { raidName });
    const idx = plans.value.findIndex(p => p.id === plan.id);
    if (idx >= 0) plans.value[idx] = updated;
    window.dispatchEvent(new CustomEvent('app:toast', { detail: { type: 'success', message: `Set raid to "${raid}"` } }));
  } catch (e: any) {
    window.dispatchEvent(new CustomEvent('app:toast', { detail: { type: 'error', message: e?.message || 'Failed to set raid' } }));
  }
}

async function renameFolder(oldName: string) {
  const next = prompt('Rename folder to:', oldName === '— No Folder —' ? '' : oldName);
  if (next === null) return;
  const newName = next.trim();
  // If renaming no-folder to empty again, nothing to do
  if (oldName !== '— No Folder —' && newName === '') return;
  const targets = plans.value.filter(p => ((p.metadata as any)?.folder || '— No Folder —') === oldName);
  for (const p of targets) {
    const meta = { ...(p.metadata || {}) } as Record<string, any>;
    if (newName) meta.folder = newName; else meta.folder = null;
    try {
      const updated = await updateRaidPlan(p.id, { metadata: meta });
      const idx = plans.value.findIndex(x => x.id === p.id);
      if (idx >= 0) plans.value[idx] = updated;
    } catch (e) {
      // continue; error toasts per item can be noisy; show one at end
    }
  }
  if (newName) knownFolders.value.add(newName);
  if (oldName !== '— No Folder —') knownFolders.value.delete(oldName);
  window.dispatchEvent(new CustomEvent('app:toast', { detail: { type: 'success', message: 'Folder renamed' } }));
}

async function renameRaid(folder: string, oldRaid: string) {
  const next = prompt('Rename raid to:', oldRaid === '— No Raid —' ? '' : oldRaid);
  if (next === null) return;
  const name = next.trim();
  const newRaid = name ? name : null;
  const targets = plans.value.filter(p => ((p.metadata as any)?.folder || '— No Folder —') === folder && (p.raidName || '— No Raid —') === oldRaid);
  for (const p of targets) {
    try {
      const updated = await updateRaidPlan(p.id, { raidName: newRaid });
      const idx = plans.value.findIndex(x => x.id === p.id);
      if (idx >= 0) plans.value[idx] = updated;
    } catch (e) {
      // ignore item-level failures
    }
  }
  window.dispatchEvent(new CustomEvent('app:toast', { detail: { type: 'success', message: 'Raid renamed' } }));
}

function onPlanDragStart(e: DragEvent, plan: RaidPlanDTO) {
  e.dataTransfer?.setData('text/plain', String(plan.id));
  e.dataTransfer?.setDragImage?.(document.createElement('div'), 0, 0);
}

function onFolderDrop(e: DragEvent, folder: string) {
  const text = e.dataTransfer?.getData('text/plain');
  if (!text) return;
  const id = Number(text);
  if (!Number.isFinite(id)) return;
  movePlanToFolder(id, folder);
}

function onRaidDrop(e: DragEvent, folder: string, raid: string) {
  const text = e.dataTransfer?.getData('text/plain');
  if (!text) return;
  const id = Number(text);
  if (!Number.isFinite(id)) return;
  movePlanToRaid(id, raid);
}
</script>

<template>
  <BaseModal :model-value="true" @update:model-value="() => emit('close')" @close="emit('close')" size="xl" title="Manage Raid Plans">
    <template #header>
      <div class="flex items-center justify-between w-full">
        <div class="flex items-center gap-2">
          <span class="text-2xl">📂</span>
          <h2 class="text-xl font-bold">Manage Raid Plans</h2>
        </div>
        <button
          @click="emit('createNew'); emit('close')"
          class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-medium transition-colors"
        >
          ➕ New Plan
        </button>
      </div>
    </template>

    <template #default>
      <!-- Search and Sort -->
      <div class="flex items-center gap-3 mb-4">
        <div class="flex-1 relative">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="🔍 Search plans..."
            class="w-full bg-slate-900/60 border border-slate-700 rounded-lg px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
          />
        </div>
        <div class="flex items-center gap-2">
          <span class="text-xs text-slate-400">Sort by:</span>
          <select
            v-model="sortBy"
            class="bg-slate-900/60 border border-slate-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
          >
            <option value="updated">Last Updated</option>
            <option value="created">Date Created</option>
            <option value="name">Name (A-Z)</option>
          </select>
        </div>
      </div>

      <!-- Loading State -->
      <div v-if="loading" class="flex items-center justify-center py-12">
        <div class="text-center">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-emerald-500"></div>
          <div class="mt-2 text-sm text-slate-400">Loading plans...</div>
        </div>
      </div>

      <!-- Error State -->
      <div v-else-if="error" class="rounded-lg border border-red-500/50 bg-red-500/10 p-4 text-center">
        <div class="text-red-400 font-medium">{{ error }}</div>
        <button
          @click="loadPlans"
          class="mt-2 px-3 py-1 text-sm rounded-md bg-red-500/20 hover:bg-red-500/30 transition-colors"
        >
          Retry
        </button>
      </div>

      <!-- Empty State -->
      <div v-else-if="filteredPlans.length === 0" class="text-center py-12">
        <div class="text-6xl mb-3">📭</div>
        <div class="text-lg font-medium text-slate-300 mb-1">
          {{ searchQuery ? 'No plans found' : 'No raid plans yet' }}
        </div>
        <div class="text-sm text-slate-400 mb-4">
          {{ searchQuery ? 'Try a different search term' : 'Create your first raid plan to get started' }}
        </div>
        <button
          v-if="!searchQuery"
          @click="emit('createNew'); emit('close')"
          class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-500 text-white text-sm font-medium transition-colors"
        >
          ➕ Create First Plan
        </button>
      </div>

      <!-- Plans Grouped: Folder > Raid -->
      <div v-else class="space-y-2 max-h-[500px] overflow-y-auto pr-2">
        <div class="flex items-center justify-between sticky top-0 bg-slate-950/80 backdrop-blur px-1 py-1 border-b border-slate-800 z-10">
          <div class="text-xs text-slate-400">Folders: {{ Object.keys(grouped).length }}</div>
          <div class="space-x-2">
            <button class="px-2 py-1 rounded-md border border-slate-700 hover:bg-slate-800 text-xs" @click="addFolder">+ New Folder</button>
          </div>
        </div>

        <div v-for="folder in Object.keys(grouped).sort((a,b)=>a.localeCompare(b))" :key="folder" class="rounded-lg border border-slate-700 overflow-hidden">
          <!-- Folder Header (drop target) -->
          <div
            class="flex items-center justify-between bg-slate-900/60 px-3 py-2 cursor-pointer select-none"
            @click="folderOpen[folder] = !folderOpen[folder]"
            @dragover.prevent
            @drop.prevent="(e) => onFolderDrop(e, folder)"
            title="Drop a plan here to move it to this folder"
          >
            <div class="flex items-center gap-2">
              <span class="text-slate-400">{{ folderOpen[folder] ? '▾' : '▸' }}</span>
              <span class="font-semibold text-slate-100">{{ folder }}</span>
              <span class="text-xs text-slate-400">({{ Object.values(grouped[folder]).reduce((a, arr) => a + arr.length, 0) }} plans)</span>
            </div>
            <div class="flex items-center gap-2">
              <button class="px-2 py-0.5 rounded-md border border-slate-600 hover:bg-slate-800 text-[11px]" @click.stop="renameFolder(folder)">Rename</button>
            </div>
          </div>

          <!-- Raids in Folder -->
          <div v-show="folderOpen[folder] !== false" class="divide-y divide-slate-800">
            <div v-for="raid in Object.keys(grouped[folder]).sort((a,b)=>a.localeCompare(b))" :key="folder + '::' + raid" class="">
              <!-- Raid Header (drop target) -->
              <div
                class="flex items-center justify-between bg-slate-900/30 px-3 py-2 cursor-pointer select-none"
                @click="raidOpen[folder + '::' + raid] = !raidOpen[folder + '::' + raid]"
                @dragover.prevent
                @drop.prevent="(e) => onRaidDrop(e, folder, raid)"
                title="Drop a plan here to set its raid"
              >
                <div class="flex items-center gap-2">
                  <span class="text-slate-500">{{ raidOpen[folder + '::' + raid] ? '▾' : '▸' }}</span>
                  <span class="text-slate-200">{{ raid }}</span>
                  <span class="text-xs text-slate-500">({{ grouped[folder][raid].length }})</span>
                </div>
                <div class="flex items-center gap-2">
                  <button class="px-2 py-0.5 rounded-md border border-slate-600 hover:bg-slate-800 text-[11px]" @click.stop="renameRaid(folder, raid)">Rename</button>
                </div>
              </div>

              <!-- Plans List for this Raid -->
              <div v-show="raidOpen[folder + '::' + raid] !== false" class="p-2 space-y-2">
                <div
                  v-for="plan in grouped[folder][raid]"
                  :key="plan.id"
                  class="group rounded-lg border transition-all"
                  :class="[
                    plan.id === currentPlanId
                      ? 'border-emerald-500/50 bg-emerald-500/10'
                      : 'border-slate-700 bg-slate-900/40 hover:bg-slate-900/60 hover:border-slate-600'
                  ]"
                  draggable="true"
                  @dragstart="(e) => onPlanDragStart(e, plan)"
                >
                  <div class="p-4">
                    <div class="flex items-start justify-between gap-3">
                      <!-- Plan Info -->
                      <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-1">
                          <h3 class="font-semibold text-slate-100 truncate">
                            {{ plan.name }}
                          </h3>
                          <span
                            v-if="plan.id === currentPlanId"
                            class="px-2 py-0.5 rounded-md bg-emerald-500/20 text-emerald-300 text-xs font-medium border border-emerald-500/30"
                          >
                            Current
                          </span>
                          <span
                            v-if="plan.isPublic"
                            class="px-2 py-0.5 rounded-md bg-blue-500/20 text-blue-300 text-xs font-medium border border-blue-500/30"
                            title="Publicly shared"
                          >
                            🔗 Shared
                          </span>
                        </div>

                        <div class="flex items-center gap-3 text-xs text-slate-400">
                          <span v-if="plan.raidName" class="flex items-center gap-1">
                            🎯 {{ plan.raidName }}
                          </span>
                          <span class="flex items-center gap-1">
                            📝 {{ plan.blocks?.length || 0 }} blocks
                          </span>
                          <span class="flex items-center gap-1">
                            🕒 Updated {{ formatDate(plan.updatedAt) }}
                          </span>
                        </div>

                        <div class="flex items-center gap-2 mt-2 text-xs text-slate-500">
                          <span>Created by {{ plan.createdBy.username }}</span>
                          <span>•</span>
                          <span>{{ formatDate(plan.createdAt) }}</span>
                        </div>
                      </div>

                      <!-- Actions -->
                      <div class="flex items-center gap-1">
                        <!-- Quick move to folder -->
                        <select
                          class="px-2 py-1 rounded-md border border-slate-700 bg-slate-900/60 text-xs"
                          :value="(((plan.metadata as any)?.folder) || '— No Folder —')"
                          @change="(ev) => movePlanToFolder(plan.id, (ev.target as HTMLSelectElement).value)"
                          title="Move to folder"
                        >
                          <option v-for="opt in allFolderOptions" :key="String(opt)" :value="opt">📁 {{ opt }}</option>
                        </select>
                        <button
                          v-if="plan.id !== currentPlanId"
                          @click="handleLoad(plan)"
                          class="px-3 py-1.5 rounded-md border border-emerald-600/50 bg-emerald-900/30 text-emerald-300 hover:bg-emerald-900/50 text-sm font-medium transition-colors"
                          title="Load this plan"
                        >
                          📂 Load
                        </button>

                        <!-- Delete Button -->
                        <button
                          v-if="confirmDeleteId === plan.id"
                          @click="handleDelete(plan.id)"
                          :disabled="deletingId === plan.id"
                          class="px-3 py-1.5 rounded-md bg-red-600 hover:bg-red-500 text-white text-sm font-medium transition-colors disabled:opacity-50"
                          title="Confirm delete"
                        >
                          {{ deletingId === plan.id ? '...' : '✓ Confirm' }}
                        </button>
                        <button
                          v-else
                          @click="handleDelete(plan.id)"
                          :disabled="deletingId !== null"
                          class="px-3 py-1.5 rounded-md border border-red-600/50 bg-red-900/30 text-red-300 hover:bg-red-900/50 text-sm font-medium transition-colors disabled:opacity-50"
                          title="Delete this plan"
                        >
                          🗑️ Delete
                        </button>

                        <!-- Cancel Delete -->
                        <button
                          v-if="confirmDeleteId === plan.id"
                          @click="cancelDelete"
                          class="px-2 py-1.5 rounded-md border border-slate-700 hover:bg-slate-800 text-xs transition-colors"
                          title="Cancel"
                        >
                          ✕
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Summary -->
      <div v-if="plans.length > 0" class="mt-4 pt-4 border-t border-slate-800 flex items-center justify-between text-xs text-slate-400">
        <span>
          Showing {{ filteredPlans.length }} of {{ plans.length }} plan{{ plans.length === 1 ? '' : 's' }}
        </span>
        <button
          @click="loadPlans"
          class="px-2 py-1 rounded-md hover:bg-slate-800 transition-colors"
          title="Refresh list"
        >
          🔄 Refresh
        </button>
      </div>
    </template>
  </BaseModal>
</template>
