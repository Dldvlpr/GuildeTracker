<template>
  <section class="mx-auto max-w-6xl px-4 py-8">
    <header class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-2xl md:text-3xl font-bold text-white mb-2">
          {{ guild?.name || 'Guilde' }}
        </h1>
        <p class="text-slate-300">
          Que souhaitez-vous faire avec cette guilde ?
        </p>
      </div>
      <div class="text-right">
        <RouterLink to="/" class="text-sm px-4 py-2 rounded-lg ring-1 ring-inset ring-white/10 hover:ring-white/20 text-slate-200 hover:text-white transition mb-2 block">
          ← Retour à l'accueil
        </RouterLink>
        <div class="text-sm text-slate-400">{{ guild?.faction }}</div>
        <div class="text-xs text-slate-500">{{ guild?.nbrGuildMembers }} membres</div>
      </div>
    </header>

    <div v-if="loading" class="text-center text-slate-400 py-12">
      Chargement de la guilde...
    </div>
    <div v-else-if="error" class="text-center text-red-400 py-12">
      {{ error }}
    </div>

    <div v-else>
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 mb-12">
        <div class="rounded-xl border border-white/10 bg-white/5 p-4">
          <div class="text-sm text-slate-400">Faction</div>
          <div class="text-lg font-semibold text-white">{{ guild?.faction }}</div>
        </div>
        <div class="rounded-xl border border-white/10 bg-white/5 p-4">
          <div class="text-sm text-slate-400">Membres</div>
          <div class="text-lg font-semibold text-white">{{ guild?.nbrGuildMembers ?? '—' }}</div>
        </div>
        <div class="rounded-xl border border-white/10 bg-white/5 p-4">
          <div class="text-sm text-slate-400">Personnages</div>
          <div class="text-lg font-semibold text-white">{{ guild?.nbrCharacters ?? '—' }}</div>
        </div>
      </div>

      <div class="mb-12">
        <div class="flex items-center gap-3 mb-6">
          <div class="flex items-center justify-center w-8 h-8 rounded-full bg-red-500/20 text-red-400">
            P1
          </div>
          <h2 class="text-xl font-semibold text-white">Fonctionnalités principales</h2>
          <span class="text-xs px-2 py-1 bg-red-500/15 text-red-300 rounded-full ring-1 ring-red-400/20">
            PRIORITÉ 1
          </span>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <FeatureCard
            title="Gestion des membres"
            description="Gérer les membres et leurs rôles (GM, Officier, Membre)"
            icon="MEMBRES"
            :available="!!guild?.id"
            :route="guild?.id ? `/guild/${guild.id}/members` : '#'"
            priority="high"
          />
          <FeatureCard
            title="Liste des personnages"
            description="Voir et gérer tous les personnages des membres"
            icon="CHARS"
            :available="true"
            :route="`/guild/${guild?.id}/characters`"
            priority="high"
          />
          <FeatureCard
            title="Dashboard guilde"
            description="Vue d'ensemble avec statistiques et membres actifs"
            icon="DASH"
            :available="true"
            :route="`/guild/${guild?.id}/dashboard`"
            priority="high"
          />
        </div>
      </div>

      <div class="mb-12">
        <div class="flex items-center gap-3 mb-6">
          <div class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-500/20 text-indigo-400">
            P2
          </div>
          <h2 class="text-xl font-semibold text-white">Fonctionnalités de jeu</h2>
          <span class="text-xs px-2 py-1 bg-indigo-500/15 text-indigo-300 rounded-full ring-1 ring-indigo-400/20">
            PRIORITÉ 2
          </span>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <FeatureCard
            title="Import d'événements"
            description="Importer des événements depuis des fichiers JSON"
            icon="IMPORT"
            :available="true"
            :route="`/guild/${guild?.id}/import-events`"
            priority="medium"
          />
          <FeatureCard
            title="Système DKP/Points"
            description="Gérer la répartition équitable du loot"
            icon="DKP"
            :available="false"
            :route="`/guild/${guild?.id}/dkp-system`"
            priority="medium"
          />
          <FeatureCard
            title="Calendrier raids"
            description="Planifier et organiser vos événements de guilde"
            icon="CAL"
            :available="false"
            :route="`/guild/${guild?.id}/raid-calendar`"
            priority="medium"
          />
        </div>
      </div>

      <div class="mb-8">
        <div class="flex items-center gap-3 mb-6">
          <div class="flex items-center justify-center w-8 h-8 rounded-full bg-emerald-500/20 text-emerald-400">
            P3
          </div>
          <h2 class="text-xl font-semibold text-white">Analytics & Rapports</h2>
          <span class="text-xs px-2 py-1 bg-emerald-500/15 text-emerald-300 rounded-full ring-1 ring-emerald-400/20">
            PRIORITÉ 3
          </span>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <FeatureCard
            title="Stats de raid"
            description="Analyser participation, performance et loot"
            icon="STATS"
            :available="false"
            :route="`/guild/${guild?.id}/raid-stats`"
            priority="low"
          />
          <FeatureCard
            title="Rapports de guilde"
            description="Suivre l'activité et la progression"
            icon="REPORTS"
            :available="false"
            :route="`/guild/${guild?.id}/guild-reports`"
            priority="low"
          />
          <FeatureCard
            title="Notifications Discord"
            description="Webhooks pour les événements importants"
            icon="NOTIF"
            :available="false"
            :route="`/guild/${guild?.id}/discord-notifications`"
            priority="low"
          />
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { getGameGuild } from '@/services/gameGuild.service'
import type { GameGuild } from '@/interfaces/GameGuild.interface'
import FeatureCard from '@/components/FeatureCard.vue'

const route = useRoute()
const id = ref<string | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)
const guild = ref<GameGuild | null>(null)

const load = async () => {
  if (!id.value) return
  loading.value = true
  error.value = null
  guild.value = null
  const res = await getGameGuild(id.value)
  if (res.ok) {
    guild.value = res.data
  } else {
    error.value = res.error
  }
  loading.value = false
}

onMounted(() => {
  id.value = (route.params.id as string) || null
  load()
})

watch(
  () => route.params.id,
  (v) => {
    id.value = (v as string) || null
    load()
  },
)
</script>

<style scoped>
</style>

