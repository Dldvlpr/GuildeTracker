<template>
  <div
    class="min-h-[100svh] flex flex-col bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950 text-slate-100 selection:bg-indigo-600/30 selection:text-white"
  >
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

    <div class="flex flex-1">
      <Sidebar
        :open="sidebarOpen"
        @close="sidebarOpen = false"
        @create-guild="router.push({ path: '/guild/create'})"
        @search-guild="router.push({ path: '/guild'})"
        @add-character="router.push('/add')"
      />

      <main class="flex-1 overflow-auto md:pl-64">
        <div class="pointer-events-none -z-10 h-0">
          <div class="relative" aria-hidden="true">
            <div
              class="absolute inset-x-0 -top-10 h-0 bg-[radial-gradient(60rem_60rem_at_50%_-10%,rgba(99,102,241,0.15),transparent_60%)]"
            />
          </div>
        </div>
        <div class="px-4 py-4 md:px-6 md:py-6 max-w-7xl mx-auto">
          <RouterView />
        </div>
      </main>
    </div>

    <footer class="border-t border-white/5">
      <div
        class="mx-auto max-w-7xl px-4 py-6 text-xs text-slate-400 flex items-center justify-between"
      >
        <span>© {{ currentYear }} GuildeTracker</span>
        <div class="flex gap-4">
          <p>privacy</p>
          <p>condition</p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, computed } from 'vue'
import { RouterView, useRouter } from 'vue-router'
import Sidebar from '@/components/layout/Sidebar.vue'
import { useUserStore } from '@/stores/userStore'
import { checkAuthStatus } from '@/services/auth'

const router = useRouter()
const userStore = useUserStore()
const sidebarOpen = ref(false)
const currentYear = computed(() => new Date().getFullYear())

onMounted(async () => {
  userStore.setLoading(true)
  try {
    const a = await checkAuthStatus()
    if (a.isAuthenticated) userStore.setUser(a.user)
    else userStore.logout()
  } catch {
    userStore.logout()
  }
})
</script>
