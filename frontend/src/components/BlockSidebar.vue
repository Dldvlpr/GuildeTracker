<script setup lang="ts">
import type { BlockType } from '@/interfaces/raidPlan.interface.ts';

const emit = defineEmits<{
  (e: 'add-block', type: BlockType): void;
}>();

function add(type: BlockType) {
  emit('add-block', type);
}
</script>

<template>
  <div class="space-y-4 text-sm">
    
    <section>
      <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">
        Boss Assignments
      </h3>
      <div class="space-y-1">
        <button class="w-full text-left px-2 py-1.5 rounded-md hover:bg-slate-800/80"
                @click="add('BOSS_GRID')">
          ⚔️ Boss Grid
        </button>
        <button class="w-full text-left px-2 py-1.5 rounded-md hover:bg-slate-800/80"
                @click="add('PHASE_STRATEGY')">
          📜 Phase Strategy
        </button>
        
      </div>
    </section>

    
    <section>
      <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">
        Group Management
      </h3>
      <div class="space-y-1">
        <button class="w-full text-left px-2 py-1.5 rounded-md hover:bg-slate-800/80" @click="add('GROUPS_GRID')">
          👥 Raid Groups
        </button>
        <button class="w-full text-left px-2 py-1.5 rounded-md hover:bg-slate-800/80"
                @click="add('ROLE_MATRIX')">
          🧩 Role Matrix
        </button>
        <button class="w-full text-left px-2 py-1.5 rounded-md hover:bg-slate-800/80"
                @click="add('BENCH_ROSTER')">
          🪑 Bench Roster
        </button>
      </div>
    </section>

    
    <section>
      <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">
        Spreadsheet Blocks
      </h3>
      <div class="space-y-1">
        <button class="w-full text-left px-2 py-1.5 rounded-md hover:bg-slate-800/80" @click="add('COOLDOWN_ROTATION')">
          ⏱️ Cooldown Rotation
        </button>
        <button class="w-full text-left px-2 py-1.5 rounded-md hover:bg-slate-800/80" @click="add('INTERRUPT_ROTATION')">
          🗡️ Interrupt Rotation
        </button>
        <button class="w-full text-left px-2 py-1.5 rounded-md hover:bg-slate-800/80" @click="add('CUSTOM_SECTION')">
          🧩 Custom Section
        </button>
      </div>
    </section>

    
    <section>
      <h3 class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-1">
        Content Blocks
      </h3>
      <div class="space-y-1">
        <button class="w-full text-left px-2 py-1.5 rounded-md hover:bg-slate-800/80"
                @click="add('IMAGE')">
          🖼️ Image
        </button>
        <button class="w-full text-left px-2 py-1.5 rounded-md hover:bg-slate-800/80"
                @click="add('TEXT')">
          📝 Text / Notes
        </button>
        <button class="w-full text-left px-2 py-1.5 rounded-md hover:bg-slate-800/80"
                @click="add('HEADING')">
          🔠 Heading
        </button>
        <button class="w-full text-left px-2 py-1.5 rounded-md hover:bg-slate-800/80"
                @click="add('DIVIDER')">
          ━ Divider
        </button>
        <button class="w-full text-left px-2 py-1.5 rounded-md hover:bg-slate-800/80"
                @click="add('CHECKLIST')">
          ✅ Checklist
        </button>
      </div>
    </section>

    
  </div>
</template>
