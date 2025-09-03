<template>
  <aside class="w-56 min-h-screen bg-gray-100 p-4">
    <h1 class="text-xl font-bold mb-4">GuildeTracker</h1>
    <button @click="redirectToDiscordAuth" class="w-full px-3 py-2 bg-blue-600 text-white rounded">
      Se connecter
    </button>
  </aside>
</template>

<script setup lang="ts">
import { redirectToDiscordAuth } from '@/services/auth'
</script>

<style scoped>
</style>
