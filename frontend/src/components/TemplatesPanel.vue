<script setup lang="ts">
import { computed, ref } from 'vue'
import type { RaidPlanBlock } from '@/interfaces/raidPlan.interface.ts'

const props = defineProps<{ currentName: string; blocks: RaidPlanBlock[] }>()
const emit = defineEmits<{ (e: 'close'): void; (e: 'load', blocks: RaidPlanBlock[]): void }>()

const name = ref(props.currentName || 'New Template')
const description = ref('')

const storageKey = 'raidPlan:templates'

type Template = { id: string; name: string; description?: string; ts: number; blocks: RaidPlanBlock[] }

function getTemplates(): Template[] {
  try {
    const t = localStorage.getItem(storageKey)
    const arr = t ? JSON.parse(t) : []
    return Array.isArray(arr) ? arr : []
  } catch { return [] }
}

function setTemplates(list: Template[]) {
  localStorage.setItem(storageKey, JSON.stringify(list))
}

const templates = ref<Template[]>(getTemplates())
const hasAny = computed(() => templates.value.length > 0)

function saveTemplate() {
  const list = getTemplates()
  const tpl: Template = {
    id: String(Date.now()),
    name: name.value.trim() || 'Untitled',
    description: description.value.trim() || undefined,
    ts: Date.now(),
    blocks: JSON.parse(JSON.stringify(props.blocks)),
  }
  list.unshift(tpl)
  setTemplates(list.slice(0, 50))
  templates.value = list
}

function loadTemplate(tpl: Template) {
  emit('load', JSON.parse(JSON.stringify(tpl.blocks)))
}

function removeTemplate(id: string) {
  const list = getTemplates().filter(t => t.id !== id)
  setTemplates(list)
  templates.value = list
}
</script>

<template>
  <div class="text-sm space-y-4">
    <section class="space-y-2">
      <h3 class="text-slate-200 font-semibold">Save Current as Template</h3>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
        <input v-model="name" type="text" placeholder="Template name" class="bg-slate-900/80 border border-slate-700 rounded-md px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500" />
        <input v-model="description" type="text" placeholder="Description (optional)" class="bg-slate-900/80 border border-slate-700 rounded-md px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500" />
      </div>
      <div class="text-right">
        <button class="px-3 py-1.5 text-sm rounded-md border border-slate-700 hover:bg-slate-800" @click="saveTemplate">Save Template</button>
      </div>
    </section>

    <section class="space-y-2">
      <h3 class="text-slate-200 font-semibold">My Templates</h3>
      <div v-if="hasAny" class="space-y-2 max-h-80 overflow-y-auto pr-1">
        <div v-for="tpl in templates" :key="tpl.id" class="rounded border border-slate-700 bg-slate-900/60 p-3 flex items-center justify-between">
          <div>
            <div class="text-slate-100">{{ tpl.name }}</div>
            <div class="text-xs text-slate-400">{{ new Date(tpl.ts).toLocaleString() }}<span v-if="tpl.description"> — {{ tpl.description }}</span></div>
          </div>
          <div class="flex items-center gap-2">
            <button class="px-2 py-1 text-xs rounded border border-slate-700 hover:bg-slate-800" @click="loadTemplate(tpl)">Use</button>
            <button class="px-2 py-1 text-xs rounded border border-red-800/50 text-red-300 hover:bg-red-900/30" @click="removeTemplate(tpl.id)">Delete</button>
          </div>
        </div>
      </div>
      <div v-else class="text-xs text-slate-500 italic">No templates yet</div>
    </section>

    <div class="text-right">
      <button class="px-3 py-1.5 text-sm rounded-md border border-slate-700 hover:bg-slate-800" @click="$emit('close')">Close</button>
    </div>
  </div>
</template>
