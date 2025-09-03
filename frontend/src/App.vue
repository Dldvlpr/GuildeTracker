<script setup lang="ts">
import Sidebar from './components/layout/Sidebar.vue'
</script>

<template>
  <div class="min-h-screen flex">
    <Sidebar />
    <main class="flex-1 flex items-center justify-center">
      <!-- Contenu principal -->
    </main>
  </div>
</template>

<style>
body {
  margin: 0;
  font-family: sans-serif;
}
</style>
