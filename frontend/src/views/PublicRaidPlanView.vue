<script setup lang="ts">
import { ref, onMounted, computed } from 'vue';
import { useRoute } from 'vue-router';
import type { RaidPlanBlock } from '@/interfaces/raidPlan.interface';

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
const hoveredCol: Record<number, number | null> = {};

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

    const data = await res.json();
    planName.value = data.name;
    guildName.value = data.guild;
    blocks.value = data.blocks;
    createdAt.value = new Date(data.createdAt).toLocaleDateString();
    updatedAt.value = new Date(data.updatedAt).toLocaleDateString();
  } catch (e: any) {
    error.value = e.message;
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  loadPlan();
  try { const p = new URL(window.location.href).searchParams.get('p'); if (p) highlight.value = p } catch {}
});

function chip(row: any): { text: string, cls: string } | null {
  const lbl = ((row?.label as string) || '').toLowerCase()
  if (lbl.includes('infusion') || lbl.includes('pi')) return { text: 'PI', cls: 'bg-pink-500/15 text-pink-300 ring-pink-400/30' }
  if (lbl.includes('barrier')) return { text: 'Barrier', cls: 'bg-blue-500/15 text-blue-300 ring-blue-400/30' }
  if (lbl.includes('rally')) return { text: 'Rally', cls: 'bg-amber-500/15 text-amber-300 ring-amber-400/30' }
  if (lbl.includes('amz')) return { text: 'AMZ', cls: 'bg-teal-500/15 text-teal-300 ring-teal-400/30' }
  if (lbl.includes('spirit link') || lbl.includes('slt') || lbl.includes('link')) return { text: 'SLT', cls: 'bg-emerald-500/15 text-emerald-300 ring-emerald-400/30' }
  return null
}

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

function isPairRow(row: any): boolean {
  return (row?.mode === 'pair');
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

            
            
            <div v-else-if="block.type === 'ROLE_MATRIX'" class="grid grid-cols-1 md:grid-cols-4 gap-3">
              <div v-for="role in ['Tanks','Healers','Melee','Ranged']" :key="role" class="rounded border border-slate-800 bg-slate-900/60 p-2">
                <div class="text-[11px] font-semibold text-slate-300 mb-1">{{ role }}</div>
                <ul class="space-y-1">
                  <li v-for="name in (block.data?.roleAssignments?.[role] || [])" :key="name" class="rounded bg-slate-800/70 px-2 py-1" :class="matchName(name) ? 'ring-2 ring-emerald-500/70' : ''">{{ name }}</li>
                  <li v-if="!(block.data?.roleAssignments?.[role]?.length)" class="text-slate-500 italic">No assignments</li>
                </ul>
              </div>
            </div>

            
            <div v-else-if="block.type === 'GROUPS_GRID'" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
              <div v-for="g in (block.data?.groups || [])" :key="g.id" class="rounded border border-slate-800 bg-slate-900/60 p-2">
                <div class="text-[11px] font-semibold text-slate-300 mb-1 flex items-center justify-between">
                  <span>{{ g.title }}</span>
                  <span class="text-slate-500">{{ (g.members || []).length }}/{{ block.data?.playersPerGroup ?? 5 }}</span>
                </div>
                <ul class="space-y-1">
                  <li v-for="name in (g.members || [])" :key="name" class="rounded bg-slate-800/70 px-2 py-1" :class="matchName(name) ? 'ring-2 ring-emerald-500/70' : ''">{{ name }}</li>
                  <li v-if="!(g.members?.length)" class="text-slate-500 italic">Empty</li>
                </ul>
              </div>
            </div>

            
            <div v-else-if="block.type === 'BOSS_GRID'" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
              <div v-for="pos in (block.data?.positions || [])" :key="pos.id" class="rounded border border-slate-800 bg-slate-900/60 p-2">
                <div class="text-[11px] font-semibold text-slate-300 mb-1">{{ pos.label }}</div>
                <ul class="space-y-1">
                  <li v-for="name in (block.data?.assignments?.[pos.id] || [])" :key="name" class="rounded bg-slate-800/70 px-2 py-1" :class="matchName(name) ? 'ring-2 ring-emerald-500/70' : ''">{{ name }}</li>
                  <li v-if="!(block.data?.assignments?.[pos.id]?.length)" class="text-slate-500 italic">Unassigned</li>
                </ul>
              </div>
            </div>

            
            <div v-else-if="block.type === 'COOLDOWN_ROTATION'" class="overflow-auto">
              <table class="min-w-full text-[11px] border border-slate-800 rota-table">
                <thead>
                  <tr class="bg-slate-900/70 sticky top-0">
                    <th class="border-b border-slate-800 p-1 text-left w-40">{{ block.data?.rowHeaderLabel || 'Cooldown' }}</th>
                    <th v-for="(col, ci) in (block.data?.columns || [])" :key="col.id" class="border-b border-l border-slate-800 p-1" @mouseenter="hoveredCol[blocks.indexOf(block)] = ci" @mouseleave="hoveredCol[blocks.indexOf(block)] = null" :class="hoveredCol[blocks.indexOf(block)] === ci ? 'bg-slate-800/60' : ''">
                      <div>{{ col.label }}<span v-if="col.sublabel" class="text-slate-500"> — {{ col.sublabel }}</span></div>
                    </th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="row in (block.data?.rows || [])" :key="row.id" class="odd:bg-slate-900/40">
                    <td class="border-t border-slate-800 p-1">
                      <span v-if="chip(row)" class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[9px] font-semibold ring-1 ring-inset mr-1" :class="chip(row)!.cls">{{ chip(row)!.text }}</span>
                      {{ row.label }}
                    </td>
                    <td v-for="(col, ci) in (block.data?.columns || [])" :key="col.id" class="border-t border-l border-slate-800 p-1" @mouseenter="hoveredCol[blocks.indexOf(block)] = ci" @mouseleave="hoveredCol[blocks.indexOf(block)] = null" :class="hoveredCol[blocks.indexOf(block)] === ci ? 'bg-slate-800/30' : ''">
                      <div class="space-y-1">
                        <template v-if="!isPairRow(row)">
                          <div v-for="name in ((block.data?.cells?.[row.id]?.[col.id]) || [])" :key="name" class="rounded bg-slate-800/70 px-2 py-1" :class="matchName(name) ? 'ring-2 ring-emerald-500/70' : ''">{{ name }}</div>
                        </template>
                        <template v-else>
                          <div class="grid grid-cols-2 gap-1">
                            <div class="rounded bg-slate-900/40 p-1 min-h-6">
                              <div class="text-[10px] text-slate-500 mb-0.5">{{ row.fromLabel || 'Priest' }}</div>
                              <div v-if="(block.data?.cells?.[row.id]?.[col.id]?.from)" class="rounded bg-slate-800/70 px-2 py-1" :class="matchName(block.data?.cells?.[row.id]?.[col.id]?.from) ? 'ring-2 ring-emerald-500/70' : ''">{{ block.data?.cells?.[row.id]?.[col.id]?.from }}</div>
                              <div v-else class="text-slate-500 text-[11px]">—</div>
                            </div>
                            <div class="rounded bg-slate-900/40 p-1 min-h-6">
                              <div class="text-[10px] text-slate-500 mb-0.5">{{ row.toLabel || 'Target' }}</div>
                              <div v-if="(block.data?.cells?.[row.id]?.[col.id]?.to)" class="rounded bg-slate-800/70 px-2 py-1" :class="matchName(block.data?.cells?.[row.id]?.[col.id]?.to) ? 'ring-2 ring-emerald-500/70' : ''">{{ block.data?.cells?.[row.id]?.[col.id]?.to }}</div>
                              <div v-else class="text-slate-500 text-[11px]">—</div>
                            </div>
                          </div>
                        </template>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            
            <div v-else-if="block.type === 'INTERRUPT_ROTATION'" class="overflow-auto">
              <table class="min-w-full text-[11px] border border-slate-800 rota-table">
                <thead>
                  <tr class="bg-slate-900/70 sticky top-0">
                    <th class="border-b border-slate-800 p-1 text-left w-40">{{ block.data?.rowHeaderLabel || 'Target' }}</th>
                    <th v-for="(col, ci) in (block.data?.columns || [])" :key="col.id" class="border-b border-l border-slate-800 p-1" @mouseenter="hoveredCol[blocks.indexOf(block)] = ci" @mouseleave="hoveredCol[blocks.indexOf(block)] = null" :class="hoveredCol[blocks.indexOf(block)] === ci ? 'bg-slate-800/60' : ''">{{ col.label }}</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="row in (block.data?.rows || [])" :key="row.id" class="odd:bg-slate-900/40">
                    <td class="border-t border-slate-800 p-1">{{ row.label }}</td>
                    <td v-for="(col, ci) in (block.data?.columns || [])" :key="col.id" class="border-t border-l border-slate-800 p-1" @mouseenter="hoveredCol[blocks.indexOf(block)] = ci" @mouseleave="hoveredCol[blocks.indexOf(block)] = null" :class="hoveredCol[blocks.indexOf(block)] === ci ? 'bg-slate-800/30' : ''">
                      <div class="space-y-1">
                        <div v-for="name in ((block.data?.cells?.[row.id]?.[col.id]) || [])" :key="name" class="rounded bg-slate-800/70 px-2 py-1" :class="matchName(name) ? 'ring-2 ring-emerald-500/70' : ''">{{ name }}</div>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            
            <div v-else-if="block.type === 'BENCH_ROSTER'">
              <div class="text-[11px] text-slate-400 mb-1">Benched players</div>
              <ul class="space-y-1">
                <li v-for="name in (block.data?.bench || [])" :key="name" class="rounded bg-slate-800/70 px-2 py-1">{{ name }}</li>
                <li v-if="!(block.data?.bench?.length)" class="text-slate-500 italic">No bench</li>
              </ul>
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
              @click="window.print()"
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
