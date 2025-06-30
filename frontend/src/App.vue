<script setup lang="ts">
import { RouterView } from 'vue-router';
import Header from '@/components/layout/Header.vue';
import Footer from '@/components/layout/Footer.vue';
</script>

<template>
  <div class="h-screen grid grid-rows-[auto_1fr_auto] bg-background text-default">
    <Header />

    <main class="overflow-auto p-4">
      <RouterView />
    </main>

    <Footer />
  </div>
</template>
