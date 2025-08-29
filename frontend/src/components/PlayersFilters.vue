<template>
  <div class="filters" role="region" aria-label="Filters">
    <div class="filter-group">
      <label for="filter-class">Class</label>
      <select
        id="filter-class"
        class="filter-select"
        :value="modelValue.class"
        @change="onClass(($event.target as HTMLSelectElement).value)"
      >
        <option value="">All classes</option>
        <option v-for="cls in classes" :key="cls" :value="cls">{{ cls }}</option>
      </select>
    </div>

    <div class="filter-group">
      <label for="filter-role">Role</label>
      <select
        id="filter-role"
        class="filter-select"
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

    <button class="btn btn-outline btn-sm" @click="$emit('clear')">Clear filters</button>
  </div>
</template>

<script setup lang="ts">
const props = defineProps<{
  classes: string[]
  modelValue: { class: string; role: string }
}>()
const emit = defineEmits<{
  (e: 'update:modelValue', v: { class: string; role: string }): void
  (e: 'clear'): void
}>()

function onClass(v: string) {
  emit('update:modelValue', { ...props.modelValue, class: v })
}
function onRole(v: string) {
  emit('update:modelValue', { ...props.modelValue, role: v })
}
</script>

<style scoped>
.filters {
  display: flex;
  gap: 1rem;
  align-items: end;
  padding: 1rem 2rem;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}
.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}
.filter-group label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
}
.filter-select {
  padding: 0.6rem;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  background: white;
  min-width: 160px;
  font-size: 0.95rem;
}
.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.45rem 0.9rem;
  border-radius: 10px;
  font-size: 0.85rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-outline {
  background: transparent;
  color: #334155;
  border: 1px solid #cbd5e1;
}
.btn-outline:hover {
  background: #f8fafc;
}

@media (max-width: 768px) {
  .filters {
    flex-direction: column;
    align-items: stretch;
  }
}
</style>
