<script setup lang="ts">
import { onMounted } from 'vue'
import { RouterView } from 'vue-router'
import Sidebar from '@/components/layout/Sidebar.vue'
import { useUserStore } from '@/stores/userStore'
import { checkAuthStatus } from '@/services/auth'

const userStore = useUserStore()

onMounted(async () => {
  console.log('🔄 Vérification de l\'authentification au démarrage...')

  userStore.setLoading(true)

  try {
    const authStatus = await checkAuthStatus()

    if (authStatus.isAuthenticated) {
      console.log('✅ Utilisateur authentifié:', authStatus.user)
      userStore.setUser(authStatus.user)
    } else {
      console.log('ℹ️ Utilisateur non authentifié')
      userStore.logout()
    }
  } catch (error) {
    console.warn('⚠️ Erreur lors de la vérification d\'authentification:', error)
    userStore.logout()
  }
})
</script>

<template>
  <div class="h-screen flex bg-background text-default">
    <Sidebar />
    <main class="flex-1 overflow-auto p-4">
      <RouterView />
    </main>
  </div>
</template>
