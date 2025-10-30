import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import FeaturesView from '../views/FeaturesView.vue'
import AddPlayerView from '../views/AddPlayerView.vue'
import ListPlayerView from '@/views/ListPlayerView.vue'
import AssignementView from '../views/AssignementView.vue'
import CreateGuildView from '@/views/CreateGuildView.vue'
import ClaimGuildView from '@/views/ClaimGuildView.vue'
import AcceptInvitationView from '@/views/AcceptInvitationView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    { path: '/', name: 'home', component: HomeView },
    { path: '/features', name: 'features', component: FeaturesView },
    { path: '/player/add', name: 'addPlayer', component: AddPlayerView },
    { path: '/guild/create', name: 'guildCreate', component: CreateGuildView },
    { path: '/guild/claim', name: 'guildClaim', component: ClaimGuildView },
    { path: '/invite/:token', name: 'acceptInvitation', component: AcceptInvitationView, props: true },
    { path: '/guild/:id', name: 'guildDetails', component: FeaturesView, props: true },
    { path: '/guild/:id/characters', name: 'listPlayer', component: ListPlayerView },
    { path: '/guild/:id/members', name: 'guildMembers', component: () => import('@/views/GuildRolesView.vue'), props: true },
    { path: '/guild/:id/dashboard', name: 'guildDashboard', component: () => import('@/views/GuildDashboardView.vue'), props: true },
    { path: '/guild/:id/import-events', name: 'importEvents', component: () => import('@/views/ImportEventsView.vue'), props: true },
    { path: '/guild/:id/dkp-system', name: 'dkpSystem', component: () => import('@/views/DkpSystemView.vue'), props: true },
    { path: '/guild/:id/raid-calendar', name: 'raidCalendar', component: () => import('@/views/RaidCalendarView.vue'), props: true },
    { path: '/guild/:id/raid-stats', name: 'raidStats', component: () => import('@/views/RaidStatsView.vue'), props: true },
    { path: '/guild/:id/guild-reports', name: 'guildReports', component: () => import('@/views/GuildReportsView.vue'), props: true },
    { path: '/guild/:id/discord-notifications', name: 'discordNotifications', component: () => import('@/views/DiscordNotificationsView.vue'), props: true },
    { path: '/assignments', name: 'assignments', component: AssignementView },
  ],
})

export default router
