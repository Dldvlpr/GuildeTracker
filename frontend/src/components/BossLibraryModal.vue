<script setup lang="ts">
import { ref, computed } from 'vue';
import BaseModal from '@/components/ui/BaseModal.vue';
import { RAID_BOSSES, getRaidsByExpansion, getAllExpansions, type RaidBoss } from '@/data/raidData';

const props = defineProps<{
  modelValue: boolean;
}>();

const emit = defineEmits<{
  (e: 'update:modelValue', value: boolean): void;
  (e: 'select-boss', boss: RaidBoss): void;
}>();

function close() {
  emit('update:modelValue', false);
}

const selectedExpansion = ref<string>('Classic');
const selectedRaid = ref<string | null>(null);
const searchQuery = ref('');

const expansions = getAllExpansions();

const filteredRaids = computed(() => {
  return getRaidsByExpansion(selectedExpansion.value);
});

const filteredBosses = computed(() => {
  let bosses = RAID_BOSSES.filter((b) => b.expansion === selectedExpansion.value);

  if (selectedRaid.value) {
    bosses = bosses.filter((b) => b.raid === selectedRaid.value);
  }

  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    bosses = bosses.filter((b) =>
      b.name.toLowerCase().includes(query) ||
      b.raid.toLowerCase().includes(query)
    );
  }

  return bosses;
});

function selectBoss(boss: RaidBoss) {
  emit('select-boss', boss);
  close();
}

function openWowhead(url: string | undefined) {
  if (url) window.open(url, '_blank');
}
</script>

<template>
  <BaseModal :model-value="modelValue" @update:model-value="close" title="📚 Boss Library" size="xl">
    <div class="space-y-4">
      
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Search bosses or raids..."
        class="w-full bg-slate-900 border border-slate-700 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"
      />

      
      <div class="flex flex-wrap gap-1">
        <button
          v-for="exp in expansions"
          :key="exp"
          @click="selectedExpansion = exp; selectedRaid = null"
          class="px-3 py-1.5 text-xs font-medium rounded-md transition-all"
          :class="selectedExpansion === exp
            ? 'bg-emerald-600 text-white'
            : 'bg-slate-800 text-slate-300 hover:bg-slate-700'"
        >
          {{ exp }}
        </button>
      </div>

      
      <div v-if="filteredRaids.length" class="flex flex-wrap gap-1">
        <button
          @click="selectedRaid = null"
          class="px-2 py-1 text-[11px] rounded border"
          :class="!selectedRaid
            ? 'border-emerald-500 bg-emerald-500/20 text-emerald-300'
            : 'border-slate-700 bg-slate-800 text-slate-400 hover:bg-slate-700'"
        >
          All Raids
        </button>
        <button
          v-for="raid in filteredRaids"
          :key="raid"
          @click="selectedRaid = raid"
          class="px-2 py-1 text-[11px] rounded border"
          :class="selectedRaid === raid
            ? 'border-emerald-500 bg-emerald-500/20 text-emerald-300'
            : 'border-slate-700 bg-slate-800 text-slate-400 hover:bg-slate-700'"
        >
          {{ raid }}
        </button>
      </div>

      
      <div class="max-h-96 overflow-y-auto space-y-2 pr-2">
        <div
          v-for="boss in filteredBosses"
          :key="boss.id"
          class="group rounded-lg border border-slate-700 bg-slate-900/60 p-3 hover:bg-slate-800/80 hover:border-slate-600 transition-all cursor-pointer"
          @click="selectBoss(boss)"
        >
          <div class="flex items-start justify-between">
            <div class="flex-1 min-w-0">
              <div class="flex items-center gap-2 mb-1">
                <h3 class="text-sm font-semibold text-slate-200 truncate">{{ boss.name }}</h3>
                <span class="text-[10px] px-1.5 py-0.5 rounded bg-slate-800 border border-slate-700 text-slate-400 flex-shrink-0">
                  {{ boss.raid }}
                </span>
              </div>

              <div class="flex flex-wrap items-center gap-2 text-[10px] text-slate-400">
                <span class="flex items-center gap-1">
                  🆔 NPC {{ boss.npcId }}
                </span>
                <span class="flex items-center gap-1">
                  📦 {{ boss.expansion }}
                </span>
              </div>
            </div>

            <div class="flex items-center gap-1 ml-2 flex-shrink-0">
              <button
                v-if="boss.wowheadUrl"
                @click.stop="openWowhead(boss.wowheadUrl)"
                class="px-2 py-1 text-[10px] rounded border border-slate-700 bg-slate-800 text-slate-300 hover:bg-slate-700 opacity-0 group-hover:opacity-100 transition-opacity"
                title="View on Wowhead"
              >
                🔗 Wowhead
              </button>
              <button
                class="px-2 py-1 text-[10px] rounded border border-emerald-700 bg-emerald-900/40 text-emerald-300 hover:bg-emerald-900/60"
              >
                Use
              </button>
            </div>
          </div>
        </div>

        <div v-if="!filteredBosses.length" class="text-center py-8 text-slate-500">
          <div class="text-3xl mb-2">🔍</div>
          <div class="text-sm">No bosses found</div>
          <div class="text-xs mt-1">Try a different expansion or search term</div>
        </div>
      </div>

      
      <div class="border-t border-slate-800 pt-3 flex items-center justify-between text-[11px] text-slate-500">
        <div>
          💡 Click a boss to auto-fill positions and cooldown timings
        </div>
        <div>
          {{ filteredBosses.length }} boss{{ filteredBosses.length !== 1 ? 'es' : '' }}
        </div>
      </div>
    </div>
  </BaseModal>
</template>
