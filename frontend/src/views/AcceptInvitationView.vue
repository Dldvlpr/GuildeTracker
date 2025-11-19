<template>
  <section class="mx-auto max-w-xl px-4 py-12 text-center">
    <div class="mb-6">
      <h1 class="text-2xl md:text-3xl font-bold text-white">Guild Invitation</h1>
      <p class="text-slate-300 mt-2">Join the guild in one click.</p>
    </div>

    <div v-if="state === 'loading'" class="text-slate-400 py-8">Validating invitation…</div>

    <div v-else-if="state === 'unauth'" class="rounded-xl border border-amber-500/20 bg-amber-500/10 p-6">
      <div class="text-2xl mb-2">🔐</div>
      <h2 class="text-amber-300 font-semibold mb-1">Login required</h2>
      <p class="text-amber-100/80 text-sm mb-4">You must log in with Discord to accept the invitation.</p>
      <button @click="login()" class="px-4 py-2 rounded-lg bg-indigo-500/20 text-indigo-200 ring-1 ring-inset ring-indigo-500/30 hover:bg-indigo-500/30">Login with Discord</button>
    </div>

    <div v-else-if="state === 'error'" class="rounded-xl border border-red-500/20 bg-red-500/10 p-6">
      <div class="text-2xl mb-2">❌</div>
      <h2 class="text-red-300 font-semibold mb-1">Invalid invitation</h2>
      <p class="text-red-100/80 text-sm">{{ errorMessage }}</p>
      <RouterLink :to="{ name: 'home', query: { stay: 'true' } }" class="inline-block mt-4 text-sm px-4 py-2 rounded-lg ring-1 ring-inset ring-white/10 hover:ring-white/20 text-slate-200 hover:text-white">← Back to home</RouterLink>
    </div>

    <div v-else-if="state === 'success'" class="rounded-xl border border-emerald-500/20 bg-emerald-500/10 p-6">
      <div class="text-2xl mb-2">✅</div>
      <h2 class="text-emerald-300 font-semibold mb-1">You joined the guild</h2>
      <p class="text-emerald-100/80 text-sm">Welcome to <span class="font-semibold text-emerald-200">{{ result?.guild.name }}</span> with role <span class="font-semibold text-emerald-200">{{ result?.role }}</span>.</p>
      <p class="mt-3 text-emerald-200/80 text-sm">Auto-redirect in {{ countdown }}s…</p>
      <div class="mt-4 flex items-center justify-center gap-2">
        <RouterLink :to="`/guild/${result?.guild.id}/dashboard?onboarding=1`" class="inline-block text-sm px-4 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-white">Open dashboard →</RouterLink>
        <RouterLink :to="`/guild/${result?.guild.id}`" class="inline-block text-sm px-4 py-2 rounded-lg ring-1 ring-inset ring-white/10 hover:ring-white/20 text-slate-200 hover:text-white">Guild hub</RouterLink>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, ref } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { acceptInvitation, type AcceptInvitationSuccess } from '@/services/guildInvitation.service'
import { redirectToDiscordAuth } from '@/services/auth'
import { resolvePostLoginTarget, DEFAULT_POST_LOGIN_REDIRECT } from '@/utils/redirect'

type State = 'loading' | 'success' | 'error' | 'unauth'

const route = useRoute()
const router = useRouter()
const token = ref<string | null>(null)
const state = ref<State>('loading')
const result = ref<AcceptInvitationSuccess | null>(null)
const errorMessage = ref('')
const countdown = ref(3)
let timer: number | null = null

function login() {
  const target = resolvePostLoginTarget(window.location.pathname + window.location.search, DEFAULT_POST_LOGIN_REDIRECT)
  redirectToDiscordAuth(target)
}

async function run() {
  if (!token.value) { state.value = 'error'; errorMessage.value = 'Invalid link.'; return }
  state.value = 'loading'
  const res = await acceptInvitation(token.value)
  if (!res.ok) {
    if (res.status === 401) { state.value = 'unauth'; return }
    state.value = 'error'
    errorMessage.value = res.error
    return
  }
  result.value = res.data
  state.value = 'success'
  countdown.value = 3
  timer = window.setInterval(() => {
    countdown.value -= 1
    if (countdown.value <= 0) {
      if (timer) window.clearInterval(timer)
      router.push(`/guild/${result.value?.guild.id}/dashboard?onboarding=1`)
    }
  }, 1000)
}

onMounted(() => {
  token.value = (route.params.token as string) || null
  run()
})

onUnmounted(() => {
  if (timer) window.clearInterval(timer)
})
</script>

<style scoped></style>
