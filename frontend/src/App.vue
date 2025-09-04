<template>
  <div
    class="min-h-[100svh] flex bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950 text-slate-100 selection:bg-indigo-600/30 selection:text-white"
  >
    <!-- Overlay mobile -->
    <Transition
      enter-active-class="transition duration-200"
      enter-from-class="opacity-0"
      enter-to-class="opacity-100"
      leave-active-class="transition duration-150"
      leave-from-class="opacity-100"
      leave-to-class="opacity-0"
    >
      <div
        v-if="sidebarOpen"
        class="fixed inset-0 bg-black/50 backdrop-blur-sm z-40 md:hidden"
        @click="sidebarOpen = false"
        aria-hidden="true"
      />
    </Transition>

    <!-- Sidebar -->
    <Sidebar :open="sidebarOpen" @close="sidebarOpen = false" />

    <!-- Main column -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Top bar (mobile only) -->
      <header
        class="md:hidden sticky top-0 z-30 border-b border-white/5 bg-slate-950/60 backdrop-blur"
      >
        <div class="flex items-center gap-3 px-4 py-3">
          <button
            @click="toggleSidebar"
            aria-label="Ouvrir le menu"
            class="inline-flex items-center justify-center h-9 w-9 rounded-xl ring-1 ring-inset ring-white/10 hover:bg-white/5 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-300 transition"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              class="w-5 h-5"
              aria-hidden="true"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
          </button>

          <div class="flex items-center gap-2">
            <span
              class="inline-grid place-items-center h-8 w-8 rounded-xl bg-indigo-600/90 shadow-lg shadow-indigo-600/30"
            >
              🛡️
            </span>
            <span class="font-semibold tracking-tight">GuildeTracker</span>
          </div>
        </div>
      </header>

      <!-- Content -->
      <main class="flex-1 overflow-auto">
        <!-- halo décoratif pour matcher la Home -->
        <div class="pointer-events-none -z-10 h-0">
          <div
            class="relative"
            aria-hidden="true"
          >
            <div
              class="absolute inset-x-0 -top-10 h-0 bg-[radial-gradient(60rem_60rem_at_50%_-10%,rgba(99,102,241,0.15),transparent_60%)]"
            />
          </div>
        </div>

        <!-- le padding est géré ici pour uniformiser les pages -->
        <div class="px-4 py-4 md:px-6 md:py-6">
          <RouterView />
        </div>
      </main>

      <!-- Footer global (optionnel, cohérent avec Home) -->
      <footer class="border-t border-white/5">
        <div
          class="mx-auto max-w-6xl px-4 py-6 text-xs text-slate-400 flex items-center justify-between"
        >
          <span>© {{ new Date().getFullYear() }} GuildeTracker</span>
          <div class="flex gap-4">
            <p>privacy</p>
            <p>condition</p>
          </div>
        </div>
      </footer>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { RouterLink, RouterView } from 'vue-router'
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
