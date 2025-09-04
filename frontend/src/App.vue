<template>
  <div class="min-h-screen flex bg-background text-default">
    <div
      v-if="sidebarOpen"
      class="fixed inset-0 bg-black/50 z-40 md:hidden"
      @click="sidebarOpen = false"
    />
    <Sidebar :open="sidebarOpen" @close="sidebarOpen = false" />

    <div class="flex-1 flex flex-col">
      <header
        class="flex items-center gap-3 p-4 border-b border-secondary md:hidden"
      >
        <button
          @click="toggleSidebar"
          aria-label="Ouvrir le menu"
          class="text-default"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2"
            class="w-6 h-6"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
          </svg>
        </button>
        <span class="font-bold">GuildeTracker</span>
      </header>

      <main class="flex-1 overflow-auto p-4">
        <RouterView />
      </main>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterView } from 'vue-router'
import Sidebar from '@/components/layout/Sidebar.vue'
import { useUserStore } from '@/stores/userStore'
import { checkAuthStatus } from '@/services/auth'

const userStore = useUserStore()
const sidebarOpen = ref(false)

function toggleSidebar() {
  sidebarOpen.value = !sidebarOpen.value
}

onMounted(async () => {
  userStore.setLoading(true)

  try {
    const authStatus = await checkAuthStatus()

    if (authStatus.isAuthenticated) {
      userStore.setUser(authStatus.user)
    } else {
      userStore.logout()
    }
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
  } catch (error) {
    userStore.logout()
  }
})
</script>
