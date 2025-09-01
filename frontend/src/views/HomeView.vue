<!-- src/views/HomeView.vue -->
<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useUserStore } from '@/stores/userStore'
import { checkAuthStatus } from '@/services/auth'

const route = useRoute()
const router = useRouter()
const store = useUserStore()
const msg = ref<string | null>(null)
const type = ref<'success' | 'error' | null>(null)

function clearQuery() {
  router.replace({ name: 'home' })
}

onMounted(async () => {
  const status = route.query.status as string | undefined
  const reason = route.query.reason as string | undefined

  if (status === 'success') {
    type.value = 'success'
    if (!store.isAuthenticated) {
      const { isAuthenticated, user } = await checkAuthStatus()
      if (isAuthenticated && user) store.setUser(user)
      else {
        type.value = 'error'
        msg.value = "Session introuvable après l'authentification."
      }
    } else {
      msg.value = 'Connexion réussie.'
    }
    clearQuery()
  } else if (status === 'error') {
    type.value = 'error'
    msg.value = reason ?? "Échec de l'authentification."
    clearQuery()
  }
})
</script>

<template>
  <section class="max-w-screen-xl mx-auto flex flex-col gap-6 p-4">
    <h1 class="text-2xl font-bold text-primary">Dashboard</h1>
    <p>Bienvenue sur GuildeTracker.</p>

    <div v-if="type === 'success'" class="rounded border border-green-400 bg-green-50 text-green-800 px-4 py-2">
      {{ msg || 'Opération réussie.' }}
    </div>
    <div v-else-if="type === 'error'" class="rounded border border-red-400 bg-red-50 text-red-800 px-4 py-2">
      {{ msg }}
    </div>
  </section>
</template>
