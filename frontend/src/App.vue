<script setup lang="ts">
import { onMounted } from 'vue';
import axios from 'axios';
import { RouterView } from 'vue-router';
import Header from '@/components/layout/Header.vue';
import Footer from '@/components/layout/Footer.vue';
import { useUserStore } from '@/stores/userStore';

const userStore = useUserStore();

onMounted(async () => {
  try {
    const { data } = await axios.get('/api/me', {
      withCredentials: true,
    });
    userStore.setUser(data);
  } catch (error) {
    console.warn('Utilisateur non authentifié ou erreur lors de la récupération :', error);
  }
});
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
