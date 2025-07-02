<script setup lang="ts">
import { redirectToDiscordAuth } from '@/services/auth';
import logo from '@/assets/image/logo.png'
import { useUserStore } from '@/stores/userStore';
import axios from "axios";

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
    await axios.get('/api/logout', { withCredentials: true });
    userStore.logout();
  } catch (error) {
    console.error('Erreur lors de la déconnexion :', error);
  }
}
</script>

<template>
  <header class="flex items-center justify-between p-4 text-default">
    <router-link to="/" class="picture focus:outline-none focus:ring-0">
        <img :src=logo alt="Logo" class="image">
    </router-link>
    <nav>
      <ul class="flex items-center space-x-6">
        <li v-if="userStore.isAuthenticated">
          <button @click="logoutWithDiscord"
                  class="cursor-pointer text-secondary hover:text-accent transition-colors">
            Deconnexion
          </button>
        </li>
        <li v-else>
          <button @click="loginWithDiscord"
                  class="cursor-pointer text-secondary hover:text-accent transition-colors">
            Connexion
          </button>
        </li>
      </ul>
    </nav>
  </header>
</template>

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
