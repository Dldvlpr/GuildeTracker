<script setup lang="ts">
import { RouterLink } from 'vue-router'
</script>

<template>
  <section class="page home">
    <h1 class="page-title">Dashboard</h1>
    <div class="quick-links">
      <RouterLink to="/list" class="nav-card">View Players</RouterLink>
      <RouterLink to="/add" class="nav-card">Add Player</RouterLink>
      <RouterLink to="/assignments" class="nav-card">Assignments</RouterLink>
    </div>
  </section>
</template>

<style scoped>
.page {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}
.page-title {
  font-size: 1.8rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}
.quick-links {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}
.nav-card {
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  font-weight: 600;
  color: #334155;
  transition: background 0.2s;
}
.nav-card:hover {
  background: #f8fafc;
}
</style>
