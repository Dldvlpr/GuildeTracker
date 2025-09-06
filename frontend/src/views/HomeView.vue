<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useUserStore } from '@/stores/userStore'
import { checkAuthStatus } from '@/services/auth'

const route = useRoute()
const router = useRouter()
const store = useUserStore()

const msg = ref<string | null>(null)
const type = ref<'success' | 'error' | null>(null)

function clearQuery() {
  router.replace({ name: 'home' })
}

onMounted(async () => {
  const status = route.query.status as string | undefined
  const reason = route.query.reason as string | undefined

  if (status === 'success') {
    type.value = 'success'
    if (!store.isAuthenticated) {
      const { isAuthenticated, user } = await checkAuthStatus()
      if (isAuthenticated && user) store.setUser(user)
      else {
        type.value = 'error'
        msg.value = "Session introuvable après l'authentification."
      }
    } else {
      msg.value = 'Connexion réussie.'
    }
    clearQuery()
  } else if (status === 'error') {
    type.value = 'error'
    msg.value = reason ?? "Échec de l'authentification."
    clearQuery()
  }
})
</script>

<template>
  <main
    class="min-h-[100svh] bg-gradient-to-b from-slate-950 via-slate-900 to-slate-950 text-slate-100 selection:bg-indigo-600/30 selection:text-white"
  >
    <div class="mx-auto max-w-2xl px-4 pt-4">
      <Transition
        enter-active-class="transition duration-300"
        enter-from-class="opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-200"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-2"
      >
        <div v-if="type === 'success'"
             class="rounded-xl border border-green-500/30 bg-green-500/10 text-green-200 px-4 py-3 text-sm shadow-md shadow-green-500/10">
          {{ msg || 'Opération réussie.' }}
        </div>
      </Transition>

      <Transition
        enter-active-class="transition duration-300"
        enter-from-class="opacity-0 -translate-y-2"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-200"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 -translate-y-2"
      >
      </Transition>
    </div>

    <section class="relative">
      <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(60rem_60rem_at_50%_-10%,rgba(99,102,241,0.20),transparent_60%)]"></div>

      <div class="mx-auto max-w-6xl px-4 py-16 md:py-24">
        <div class="grid gap-10 md:grid-cols-2 md:items-center">
          <div class="space-y-6">
            <h1 class="text-4xl md:text-5xl font-extrabold tracking-tight">
              Gérez votre <span class="text-indigo-400">guilde</span> en toute simplicité
            </h1>
            <p class="text-slate-300/90 leading-relaxed">
              Centralisez les informations de vos joueurs, suivez leurs personnages, et gardez une
              organisation claire par factions — le tout dans une interface rapide et moderne.
            </p>
          </div>

          <div class="relative">
            <div class="absolute -inset-4 -z-10 rounded-3xl bg-gradient-to-tr from-indigo-600/30 via-fuchsia-500/20 to-cyan-400/20 blur-2xl"></div>
            <div class="rounded-3xl border border-white/10 bg-white/5 p-4 shadow-2xl backdrop-blur">
              <div class="rounded-2xl border border-white/10 bg-slate-900/60 p-5">
                <div class="flex items-center justify-between">
                  <div class="font-semibold">Tableau de guilde</div>
                  <div class="text-xs text-slate-400">aperçu</div>
                </div>
                <div class="mt-4 grid grid-cols-2 gap-3 text-center">
                  <div class="rounded-xl bg-white/5 p-4">
                    <div class="text-2xl font-bold">128</div>
                    <div class="text-xs text-slate-400">Membres</div>
                  </div>
                  <div class="rounded-xl bg-white/5 p-4">
                    <div class="text-2xl font-bold">342</div>
                    <div class="text-xs text-slate-400">Personnages</div>
                  </div>
                </div>
                <div class="mt-5 h-24 rounded-xl bg-gradient-to-r from-indigo-600/20 via-white/10 to-transparent"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section id="features" class="mx-auto max-w-6xl px-4 pb-16 md:pb-24">
      <h2 class="text-2xl md:text-3xl font-bold">Fonctionnalités principales</h2>
      <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <article class="rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur-sm">
          <div class="text-2xl">👥</div>
          <h3 class="mt-3 font-semibold">Gestion des joueurs</h3>
          <p class="mt-1 text-sm text-slate-300/80">Profils, tags, disponibilités et rôles.</p>
        </article>
        <article class="rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur-sm">
          <div class="text-2xl">📊</div>
          <h3 class="mt-3 font-semibold">Suivi des personnages</h3>
          <p class="mt-1 text-sm text-slate-300/80">Classes, niveaux, équipements par jeu.</p>
        </article>
        <article class="rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur-sm">
          <div class="text-2xl">🏰</div>
          <h3 class="mt-3 font-semibold">Organisation par faction</h3>
          <p class="mt-1 text-sm text-slate-300/80">Hiérarchies, permissions et canaux.</p>
        </article>
        <article class="rounded-2xl border border-white/10 bg-white/5 p-5 backdrop-blur-sm">
          <div class="text-2xl">⚡</div>
          <h3 class="mt-3 font-semibold">Outils de raid</h3>
          <p class="mt-1 text-sm text-slate-300/80">Planif, présences, groupes & rôles.</p>
        </article>
      </div>
    </section>
  </main>
</template>
