<template>
  <div class="list-header" role="region" aria-label="Players header and stats">
    <h2>Players</h2>
    <div class="list-stats">
      <span class="stat-item">Total: {{ total }}</span>
      <span class="stat-item">Tanks: {{ tanks }}</span>
      <span class="stat-item">Healers: {{ healers }}</span>
      <span class="stat-item">DPS: {{ dps }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  total: number
  tanks: number
  healers: number
  dps: number
}>()
</script>

<style scoped>
.list-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 2rem 2rem 1rem;
  border-bottom: 1px solid #e2e8f0;
}
.list-header h2 {
  font-size: 1.75rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}
.list-stats {
  display: flex;
  gap: 0.75rem;
  flex-wrap: wrap;
}
.stat-item {
  padding: 0.5rem 0.9rem;
  background: #f1f5f9;
  border-radius: 9999px;
  font-size: 0.85rem;
  font-weight: 700;
  color: #334155;
}

@media (max-width: 768px) {
  .list-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 0.9rem;
  }
}
</style>
