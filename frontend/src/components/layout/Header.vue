<template>
  <header class="flex items-center justify-between p-4 text-default">
    <router-link to="/" class="picture focus:outline-none focus:ring-0">
        <img src="../../../public/logo.png" alt="Logo" class="image">
    </router-link>
    <nav>
      <ul class="flex items-center space-x-6">
        <li>
          <a href="#"
             class="cursor-pointer text-secondary hover:text-accent transition-colors">
            Connexion
          </a>
        </li>
      </ul>
    </nav>
  </header>
</template>

<script setup lang="ts">
</script>

<style scoped>
.image {
  max-width: clamp(3rem, 15vw, 6rem);
  height: auto;
  object-fit: contain;
}

@media (max-width: 480px) {
  header {
    flex-direction: column;
    text-align: center;
  }
  .picture {
    margin-bottom: 0.5rem;
  }
}
</style>
