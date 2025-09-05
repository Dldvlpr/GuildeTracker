import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import AddPlayerView from '../views/AddPlayerView.vue'
import ListPlayerView from '@/views/ListPlayerView.vue'
import AssignementView from '../views/AssignementView.vue'
import SelectOrAddGuild from '@/views/SelectOrAddGuild.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/', name: 'home', component: HomeView },
    { path: '/add', name: 'addPlayer', component: AddPlayerView },
    { path: '/guild', name: 'SelectOrAddGuild', component: SelectOrAddGuild },
    { path: '/list', name: 'listPlayer', component: ListPlayerView },
    { path: '/assignments', name: 'assignments', component: AssignementView },
  ],
})

export default router
