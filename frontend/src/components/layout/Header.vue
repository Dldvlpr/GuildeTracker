<script setup lang="ts">
import { fetchDiscordAuthUrl } from '@/services/auth';

async function loginWithDiscord() {
  try {
    const res = await fetchDiscordAuthUrl();
    console.log('response: ' + res.data.url);
  } catch (err) {
    console.error('Impossible de récupérer l’URL d’auth Discord', err);
  }
}
</script>

<template>
  <header class="flex items-center justify-between p-4 text-default">
    <router-link to="/" class="picture focus:outline-none focus:ring-0">
        <img src="../../../public/logo.png" alt="Logo" class="image">
    </router-link>
    <nav>
      <ul class="flex items-center space-x-6">
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
