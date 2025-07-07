<script setup lang="ts">
import { onMounted } from 'vue';
import { RouterView } from 'vue-router';
import Header from '@/components/layout/Header.vue';
import Footer from '@/components/layout/Footer.vue';
import { useUserStore } from '@/stores/userStore';
import { checkAuthStatus } from '@/services/auth';

const userStore = useUserStore();

onMounted(async () => {
  console.log('🔄 Vérification de l\'authentification au démarrage...');

  userStore.setLoading(true);

  try {
    const authStatus = await checkAuthStatus();

    if (authStatus.isAuthenticated) {
      console.log('✅ Utilisateur authentifié:', authStatus.user);
      userStore.setUser(authStatus.user);
    } else {
      console.log('ℹ️ Utilisateur non authentifié');
      userStore.logout();
    }
  } catch (error) {
    console.warn('⚠️ Erreur lors de la vérification d\'authentification:', error);
    userStore.logout();
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
