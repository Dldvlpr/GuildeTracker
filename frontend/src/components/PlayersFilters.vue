<template>
  <div
    class="flex flex-col gap-4 md:flex-row md:items-end md:justify-between rounded-2xl border border-white/10 bg-white/5 p-4"
    role="region"
    aria-label="Filters"
  >
    <div class="flex flex-1 flex-col gap-4 sm:flex-row">
      <div class="flex min-w-[200px] flex-1 flex-col gap-1.5">
        <label for="filter-class" class="text-xs font-semibold text-slate-300">Class</label>
        <select
          id="filter-class"
          class="rounded-xl bg-slate-900/60 px-3 py-2 text-sm text-slate-100 ring-1 ring-inset ring-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-300"
          :value="modelValue.class"
          @change="onClass(($event.target as HTMLSelectElement).value)"
        >
          <option value="">All classes</option>
          <option v-for="cls in classes" :key="cls" :value="cls">{{ cls }}</option>
        </select>
      </div>

      <div class="flex min-w-[200px] flex-1 flex-col gap-1.5">
        <label for="filter-role" class="text-xs font-semibold text-slate-300">Role</label>
        <select
          id="filter-role"
          class="rounded-xl bg-slate-900/60 px-3 py-2 text-sm text-slate-100 ring-1 ring-inset ring-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-300"
          :value="modelValue.role"
          @change="onRole(($event.target as HTMLSelectElement).value)"
        >
          <option value="">All roles</option>
          <option value="Tanks">🛡️ Tanks</option>
          <option value="Healers">💚 Healers</option>
          <option value="Melee">⚔️ Melee</option>
          <option value="Ranged">🏹 Ranged</option>
        </select>
      </div>

      <div class="flex min-w-[200px] flex-1 flex-col gap-1.5">
        <label for="filter-spec" class="text-xs font-semibold text-slate-300">Specialization</label>
        <select
          id="filter-spec"
          class="rounded-xl bg-slate-900/60 px-3 py-2 text-sm text-slate-100 ring-1 ring-inset ring-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-300"
          :value="modelValue.spec"
          @change="onSpec(($event.target as HTMLSelectElement).value)"
        >
          <option value="">All specializations</option>
          <option v-for="sp in specs" :key="sp" :value="sp">{{ sp }}</option>
        </select>
      </div>
    </div>

    <div class="flex justify-start md:justify-end">
      <button
        class="inline-flex items-center gap-2 rounded-xl bg-white/0 px-3 py-2 text-sm font-semibold text-slate-200 ring-1 ring-inset ring-white/10 transition hover:bg-white/5 hover:text-white"
        @click="$emit('clear')"
      >
        Clear filters
      </button>
    </div>
  </div>
  
</template>

<script setup lang="ts">
const props = defineProps<{
  classes: string[]
  specs: string[]
  modelValue: { class: string; role: string; spec: string }
}>()
const emit = defineEmits<{
  (e: 'update:modelValue', v: { class: string; role: string; spec: string }): void
  (e: 'clear'): void
}>()

function onClass(v: string) {
  emit('update:modelValue', { ...props.modelValue, class: v })
}
function onRole(v: string) {
  emit('update:modelValue', { ...props.modelValue, role: v })
}
function onSpec(v: string) {
  emit('update:modelValue', { ...props.modelValue, spec: v })
}
</script>
<style scoped>
</style>
