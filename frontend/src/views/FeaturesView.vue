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
      <!-- Alerte si guilde non valide (pas de personnages) -->
      <div v-if="!guild?.isValid" class="mb-8 p-4 rounded-lg bg-amber-500/10 border border-amber-500/20">
        <div class="flex items-start gap-3">
          <div class="text-2xl">⚠️</div>
          <div>
            <h3 class="text-amber-400 font-semibold mb-1">Guilde incomplète</h3>
            <p class="text-amber-200/80 text-sm mb-3">
              Vous devez ajouter au moins un personnage à votre guilde pour pouvoir accéder aux fonctionnalités.
            </p>
            <RouterLink
              :to="`/guild/${guild?.id}/characters`"
              class="inline-flex items-center gap-2 px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg transition text-sm font-medium"
            >
              Ajouter mon premier personnage →
            </RouterLink>
          </div>
        </div>
      </div>

      <div class="mb-12">
        <div class="flex items-center gap-3 mb-6">
          <div class="flex items-center justify-center w-8 h-8 rounded-full bg-red-500/20 text-red-400">
            P1
          </div>
          <h2 class="text-xl font-semibold text-white">Organisation de la guilde</h2>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <FeatureCard
            title="Gestion des membres"
            description="Gérer les membres et leurs rôles (GM, Officier, Membre)"
            icon="MEMBRES"
            :available="!!guild?.id && !!guild?.isValid"
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
        </div>
      </div>

      <div class="mb-12">
        <div class="flex items-center gap-3 mb-6">
          <div class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-500/20 text-indigo-400">
            P2
          </div>
          <h2 class="text-xl font-semibold text-white">Raids & Événements</h2>
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <FeatureCard
            title="Import d'événements"
            description="Importer des événements depuis des fichiers JSON"
            icon="IMPORT"
            :available="!!guild?.isValid"
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
          <h2 class="text-xl font-semibold text-white">Statistiques & Suivi</h2>
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

