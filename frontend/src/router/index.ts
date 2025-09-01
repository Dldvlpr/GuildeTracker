// src/router/index.ts
import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '@/views/HomeView.vue'
import AddPlayerView from '@/views/AddPlayerView.vue'
import ListPlayerView from '@/views/ListPlayerView.vue'
import AssignementView from '@/views/AssignementView.vue'
import { useUserStore } from '@/stores/userStore'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/', name: 'home', component: HomeView },
    { path: '/add', name: 'addPlayer', component: AddPlayerView, meta: { requiresAuth: true } },
    { path: '/list', name: 'listPlayer', component: ListPlayerView, meta: { requiresAuth: true } },
    { path: '/assignments', name: 'assignments', component: AssignementView, meta: { requiresAuth: true } },
  ],
})

router.beforeEach((to) => {
  if (!to.meta.requiresAuth) return true
  const store = useUserStore()
  if (store.isAuthenticated) return true
  return { name: 'home', query: { status: 'error', reason: 'unauthorized' } }
})

export default router
