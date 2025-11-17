<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import BaseModal from './ui/BaseModal.vue';
import { getRaidPlansByGuild, deleteRaidPlan, type RaidPlanDTO } from '@/services/raidPlan.service';

interface Props {
  guildId: string;
  currentPlanId: number | null;
}

const props = defineProps<Props>();

const emit = defineEmits<{
  close: [];
  load: [plan: RaidPlanDTO];
  createNew: [];
}>();

const plans = ref<RaidPlanDTO[]>([]);
const loading = ref(false);
const error = ref<string | null>(null);
const deletingId = ref<number | null>(null);
const confirmDeleteId = ref<number | null>(null);
const sortBy = ref<'updated' | 'created' | 'name'>('updated');
const searchQuery = ref('');

onMounted(async () => {
  await loadPlans();
});

async function loadPlans() {
  loading.value = true;
  error.value = null;
  try {
    plans.value = await getRaidPlansByGuild(props.guildId);
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
  let filtered = plans.value;

  // Search filter
  if (searchQuery.value.trim()) {
    const query = searchQuery.value.toLowerCase();
    filtered = filtered.filter(p =>
      p.name.toLowerCase().includes(query) ||
      p.raidName?.toLowerCase().includes(query)
    );
  }

  // Sort
  if (sortBy.value === 'updated') {
    filtered.sort((a, b) => new Date(b.updatedAt).getTime() - new Date(a.updatedAt).getTime());
  } else if (sortBy.value === 'created') {
    filtered.sort((a, b) => new Date(b.createdAt).getTime() - new Date(a.createdAt).getTime());
  } else if (sortBy.value === 'name') {
    filtered.sort((a, b) => a.name.localeCompare(b.name));
  }

  return filtered;
});
</script>

<template>
  <BaseModal :model-value="true" @close="emit('close')" size="xl" title="Manage Raid Plans">
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

      <!-- Plans List -->
      <div v-else class="space-y-2 max-h-[500px] overflow-y-auto pr-2">
        <div
          v-for="plan in filteredPlans"
          :key="plan.id"
          class="group rounded-lg border transition-all"
          :class="[
            plan.id === currentPlanId
              ? 'border-emerald-500/50 bg-emerald-500/10'
              : 'border-slate-700 bg-slate-900/40 hover:bg-slate-900/60 hover:border-slate-600'
          ]"
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
