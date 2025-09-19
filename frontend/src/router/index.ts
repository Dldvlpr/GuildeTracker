import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import AddPlayerView from '../views/AddPlayerView.vue'
import ListPlayerView from '@/views/ListPlayerView.vue'
import AssignementView from '../views/AssignementView.vue'
import CreateGuildView from '@/views/CreateGuildView.vue'
import GuildDetailView from '@/views/GuildDetailView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/', name: 'home', component: HomeView },
    { path: '/player/add', name: 'addPlayer', component: AddPlayerView },
    { path: '/guild/create', name: 'guildCreate', component: CreateGuildView },
    { path: '/guild/:id', name: 'guildDetails', component: GuildDetailView, props: true },
    { path: '/list', name: 'listPlayer', component: ListPlayerView },
    { path: '/assignments', name: 'assignments', component: AssignementView },
  ],
})

export default router
