<template>
  <aside class="flex flex-col h-full w-56 bg-primary text-default">
    <div class="p-4 text-xl font-bold">
      <RouterLink to="/" class="block">GuildeTracker</RouterLink>
    </div>
    <nav class="flex-1 overflow-y-auto">
      <ul v-if="userStore.isLoading" class="p-2">
        <li class="text-secondary">Chargement...</li>
      </ul>
      <ul v-else-if="userStore.isAuthenticated" class="space-y-2 p-2">
        <li>
          <RouterLink
            to="/add"
            class="block px-3 py-2 rounded hover:bg-accent hover:text-background"
          >
            Ajouter un joueur
          </RouterLink>
        </li>
        <li>
          <RouterLink
            to="/list"
            class="block px-3 py-2 rounded hover:bg-accent hover:text-background"
          >
            Liste des joueurs
          </RouterLink>
        </li>
      </ul>
      <ul v-else class="p-2">
        <li>
          <button
            @click="loginWithDiscord"
            class="w-full text-left px-3 py-2 rounded hover:bg-accent hover:text-background"
          >
            Connexion
          </button>
        </li>
      </ul>
    </nav>
    <div v-if="userStore.isAuthenticated && !userStore.isLoading" class="p-4 border-t border-secondary">
      <button
        @click="logoutWithDiscord"
        class="w-full text-left px-3 py-2 rounded hover:bg-accent hover:text-background"
      >
        Déconnexion
      </button>
    </div>
  </aside>
</template>

<script setup lang="ts">
import { RouterLink } from 'vue-router'
import { redirectToDiscordAuth } from '@/services/auth'
import { useUserStore } from '@/stores/userStore'

const userStore = useUserStore()

function loginWithDiscord() {
  try {
    redirectToDiscordAuth()
  } catch (error) {
    console.error('Erreur lors de la redirection Discord:', error)
  }
}

async function logoutWithDiscord() {
  try {
    const response = await fetch('/api/logout', {
      method: 'GET',
      credentials: 'include',
      headers: {
        'Content-Type': 'application/json',
      },
    })

    if (response.ok) {
      console.log('Déconnexion réussie')
      userStore.logout()
    } else {
      console.error('Erreur lors de la déconnexion, status:', response.status)
    }
  } catch (error) {
    console.error('Erreur lors de la déconnexion :', error)
  }
}
</script>

<style scoped>
</style>

