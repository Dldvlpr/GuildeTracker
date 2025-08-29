<template>
  <header class="flex items-center justify-between p-4 text-default">
    <router-link to="/" class="picture focus:outline-none focus:ring-0">
      <img :src=logo alt="Logo" class="image">
    </router-link>
    <nav>
      <ul class="flex items-center space-x-6" v-if="userStore.isLoading">
        <li>
          <div class="cursor-default text-secondary opacity-50">
            Chargement...
          </div>
        </li>
      </ul>
      <ul class="flex items-center space-x-6" v-else-if="userStore.isAuthenticated">
        <li>
          <button class="cursor-pointer text-secondary hover:text-accent transition-colors">
            <router-link to="/add">add player</router-link>

          </button>
        </li>
        <li>
          <button class="cursor-pointer text-secondary hover:text-accent transition-colors">
            <router-link to="/list">player list</router-link>

          </button>
        </li>
        <li>
          <button @click="logoutWithDiscord"
                  class="cursor-pointer text-secondary hover:text-accent transition-colors">
            Deconnexion
          </button>
        </li>
      </ul>
      <ul class="flex items-center space-x-6" v-else>
        <li>
          <button @click="loginWithDiscord"
                  class="cursor-pointer text-secondary hover:text-accent transition-colors">
            Connexion
          </button>
        </li>
      </ul>
    </nav>
  </header>
</template>

<script setup lang="ts">
import { redirectToDiscordAuth } from '@/services/auth';
import logo from '@/assets/image/logo.png'
import { useUserStore } from '@/stores/userStore';

const userStore = useUserStore();

function loginWithDiscord() {
  try {
    redirectToDiscordAuth();
  } catch (error) {
    console.error('Erreur lors de la redirection Discord:', error);
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
    });

    if (response.ok) {
      console.log('Déconnexion réussie');
      userStore.logout();
    } else {
      console.error('Erreur lors de la déconnexion, status:', response.status);
    }
  } catch (error) {
    console.error('Erreur lors de la déconnexion :', error);
  }
}
</script>

<style scoped>
.image {
  max-width: clamp(3rem, 15vw, 6rem);
  height: auto;
  object-fit: contain;
}

button {
  font-weight: 500;
  border: none;
  outline: none;
}

button:hover {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

button:active {
  transform: translateY(0);
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
