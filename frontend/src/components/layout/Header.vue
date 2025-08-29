<template>
  <header class="flex items-center justify-center p-4 text-default">
    <RouterLink to="/" class="picture focus:outline-none focus:ring-0">
      <img :src="logo" alt="Logo" class="image" />
    </RouterLink>
  </header>
</template>

<script setup lang="ts">
defineOptions({ name: 'AppHeader' })
import { RouterLink } from 'vue-router'
import logo from '@/assets/image/logo.png'
</script>

<style scoped>
.image {
  max-width: clamp(3rem, 15vw, 6rem);
  height: auto;
  object-fit: contain;
}

@media (max-width: 480px) {
  header {
    flex-direction: column;
    text-align: center;
    gap: 1rem;
  }
  .picture {
    margin-bottom: 0.5rem;
  }
}
</style>
