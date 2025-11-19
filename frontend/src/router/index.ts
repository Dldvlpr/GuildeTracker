import { createRouter, createWebHistory } from 'vue-router'
import HomeView from '../views/HomeView.vue'
import FeaturesView from '../views/FeaturesView.vue'
import AddPlayerView from '../views/AddPlayerView.vue'
import ListPlayerView from '@/views/ListPlayerView.vue'
import AssignementView from '../views/AssignementView.vue'
import CreateGuildView from '@/views/CreateGuildView.vue'
import ClaimGuildView from '@/views/ClaimGuildView.vue'
import AcceptInvitationView from '@/views/AcceptInvitationView.vue'
import OAuthCallbackView from '@/views/OAuthCallbackView.vue'
import { useUserStore } from '@/stores/userStore'

const API_BASE = import.meta.env.VITE_API_BASE_URL as string

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [

    { path: '/', name: 'home', component: HomeView, meta: { requiresGuest: true } },

    { path: '/app', name: 'appHome', component: HomeView, meta: { requiresAuth: true } },

    { path: '/features', name: 'features', component: FeaturesView },

    { path: '/player/add', name: 'addPlayer', component: AddPlayerView, meta: { requiresAuth: true } },
    { path: '/guild/create', name: 'guildCreate', component: CreateGuildView, meta: { requiresAuth: true } },
    { path: '/guild/claim', name: 'guildClaim', component: ClaimGuildView, meta: { requiresAuth: true } },

    { path: '/invite/:token', name: 'acceptInvitation', component: AcceptInvitationView, props: true },
    { path: '/oauth/callback', name: 'oauthCallback', component: OAuthCallbackView },
    { path: '/guild/:id', name: 'guildDetails', component: FeaturesView, props: true, meta: { requiresAuth: true } },
    { path: '/guild/:id/characters', name: 'listPlayer', component: ListPlayerView, meta: { requiresAuth: true } },
    { path: '/guild/:id/members', name: 'guildMembers', component: () => import('@/views/GuildRolesView.vue'), props: true, meta: { requiresAuth: true } },
    { path: '/guild/:id/dashboard', name: 'guildDashboard', component: () => import('@/views/GuildDashboardView.vue'), props: true, meta: { requiresAuth: true } },
    { path: '/guild/:id/raid-assignments', name: 'raidAssignments', component: () => import('@/views/RaidAssignments.vue'), props: true, meta: { requiresAuth: true } },
    { path: '/guild/:id/dkp-system', name: 'dkpSystem', component: () => import('@/views/DkpSystemView.vue'), props: true, meta: { requiresAuth: true } },
    { path: '/guild/:id/raid-calendar', name: 'raidCalendar', component: () => import('@/views/RaidCalendarView.vue'), props: true, meta: { requiresAuth: true } },
    { path: '/guild/:id/raid-stats', name: 'raidStats', component: () => import('@/views/RaidStatsView.vue'), props: true, meta: { requiresAuth: true } },
    { path: '/guild/:id/guild-reports', name: 'guildReports', component: () => import('@/views/GuildReportsView.vue'), props: true, meta: { requiresAuth: true } },
    { path: '/guild/:id/discord-notifications', name: 'discordNotifications', component: () => import('@/views/DiscordNotificationsView.vue'), props: true, meta: { requiresAuth: true } },
    { path: '/assignments', name: 'assignments', component: AssignementView, meta: { requiresAuth: true } },

    { path: '/raid-plan/:shareToken', name: 'publicRaidPlan', component: () => import('@/views/PublicRaidPlanView.vue') },
  ],
})

  router.beforeEach(async (to) => {
    const userStore = useUserStore()

  if (!userStore.isReady) {
    try {
      await userStore.initFromApi(API_BASE)
    } catch {

    }
  }

  if (to.meta?.requiresAuth && !userStore.isAuthenticated) {
    return { name: 'home', query: { redirect: to.fullPath } }
  }

  if (to.meta?.requiresGuest && userStore.isAuthenticated) {
    return { name: 'appHome' }
  }

  return true
})

export default router
