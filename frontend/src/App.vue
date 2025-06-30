<script setup lang="ts">
import { RouterView } from 'vue-router'
</script>

<template>
  <div class="h-screen grid grid-rows-[auto_1fr_auto] md:grid-rows-[1fr_auto] md:grid-cols-[250px_1fr]">
    <aside class="p-4 md:row-span-2 ring-inset ring-8 ring-blue-500">
      Sidebar
    </aside>

    <main class="overflow-auto p-0 ring-inset ring-8 ring-amber-400">
      <div class="w-full h-full p-4">
        <RouterView />
      </div>
    </main>

    <footer class="p-4 text-center ring-inset ring-8 ring-fuchsia-500">
      Footer
    </footer>
  </div>
</template>
