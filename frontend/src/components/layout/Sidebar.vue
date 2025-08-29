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
    <div class="p-4 border-t border-secondary space-y-4">
      <a
        href="https://github.com/dldvlpr"
        target="_blank"
        rel="noopener noreferrer"
        class="flex items-center space-x-2 hover:text-secondary"
      >
        <svg
          class="w-5 h-5"
          fill="currentColor"
          viewBox="0 0 24 24"
          aria-hidden="true"
        >
          <path
            fill-rule="evenodd"
            clip-rule="evenodd"
            d="M12 0C5.373 0 0 5.373 0 12c0 5.303 3.438 9.8 8.205 11.387.6.111.82-.261.82-.58 0-.287-.01-1.045-.016-2.05-3.338.726-4.042-1.61-4.042-1.61-.546-1.387-1.333-1.756-1.333-1.756-1.09-.745.082-.73.082-.73 1.205.085 1.84 1.237 1.84 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.76-1.605-2.665-.304-5.467-1.332-5.467-5.93 0-1.31.468-2.38 1.235-3.22-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.3 1.23a11.52 11.52 0 013.003-.404 11.5 11.5 0 013.003.404c2.29-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.233 1.91 1.233 3.22 0 4.61-2.807 5.625-5.48 5.921.43.372.814 1.102.814 2.222 0 1.606-.015 2.903-.015 3.293 0 .32.218.695.825.577C20.565 21.796 24 17.299 24 12c0-6.627-5.373-12-12-12z"
          />
        </svg>
        <span>GitHub</span>
      </a>
      <button
        v-if="userStore.isAuthenticated && !userStore.isLoading"
        @click="logoutWithDiscord"
        class="w-full text-left px-3 py-2 rounded hover:bg-accent hover:text-background"
      >
        Déconnexion
      </button>
    </div>
  </aside>
</template>

<script setup lang="ts">
defineOptions({ name: 'AppSidebar' })
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

