<template>
  <section class="mx-auto max-w-6xl px-4 py-8">
    <div
      :class="[
        'grid gap-6',
        gameGuilds.length > 0
          ? 'justify-items-start grid-cols-2 sm:grid-cols-3 lg:grid-cols-4'
          : 'place-items-center grid-cols-1',
      ]"
    >
      <RouterLink
        v-for="g in gameGuilds"
        :key="g.id"
        to="/selectGuild"
        v-slot="{ isActive }"
        class="block w-full max-w-[280px] group"
      >
        <span
          :class="[
            'block rounded-2xl p-[1px] transition',
            isActive
              ? 'bg-gradient-to-br from-indigo-500/40 via-fuchsia-500/30 to-cyan-400/30 shadow-[0_0_0_1px_rgba(255,255,255,0.08)]'
              : 'bg-gradient-to-br from-white/10 via-white/5 to-transparent hover:from-indigo-500/20 hover:via-fuchsia-500/10 hover:to-transparent',
          ]"
        >
          <span
            :class="[
              'block rounded-[calc(theme(borderRadius.2xl)-1px)] px-4 py-4 ring-1 ring-inset transition backdrop-blur-sm',
              isActive
                ? 'bg-indigo-600/15 text-white ring-white/10 shadow-inner shadow-indigo-600/20'
                : 'bg-slate-900/50 text-slate-200 ring-white/10 group-hover:bg-white/5 group-hover:text-white',
            ]"
          >
            <div class="flex items-center gap-3">
              <div
                class="relative h-11 w-11 shrink-0 rounded-xl grid place-items-center text-base font-semibold bg-white/5 ring-1 ring-inset ring-white/10 group-hover:bg-white/10"
                :aria-label="g.name + ' logo'"
              >
                {{ initials(g.name) }}
                <div
                  class="pointer-events-none absolute -inset-0.5 rounded-[inherit] opacity-0 group-hover:opacity-100 transition bg-[radial-gradient(18rem_18rem_at_30%_-20%,rgba(99,102,241,0.35),transparent_60%)]"
                ></div>
              </div>

              <div class="min-w-0 flex-1">
                <h3 class="truncate font-semibold tracking-tight">
                  {{ g.name }}
                </h3>
                <div class="mt-1 flex items-center gap-2">
                  <span
                    class="inline-flex items-center gap-1 rounded-lg px-2 py-0.5 text-[11px] leading-5 bg-white/5 ring-1 ring-inset ring-white/10 text-slate-300"
                    v-if="g.faction"
                  >
                    🏰 {{ g.faction }}
                  </span>
                  <span
                    v-else
                    class="inline-flex items-center gap-1 rounded-lg px-2 py-0.5 text-[11px] leading-5 bg-white/5 ring-1 ring-inset ring-white/10 text-slate-400"
                  >
                    🏷️ Guilde
                  </span>
                </div>
              </div>

              <div
                class="ml-auto h-8 w-8 grid place-items-center rounded-lg ring-1 ring-inset ring-white/10 bg-white/0 group-hover:bg-white/5"
                aria-hidden="true"
              >
                <svg
                  class="h-4 w-4"
                  viewBox="0 0 24 24"
                  fill="none"
                  stroke="currentColor"
                  stroke-width="2"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                </svg>
              </div>
            </div>

            <div class="mt-4 border-t border-white/10 pt-3">
              <div class="grid grid-cols-2 gap-2 text-center text-xs text-slate-400">
                <div
                  class="rounded-lg bg-white/5 py-2 ring-1 ring-inset ring-white/10 group-hover:bg-white/10"
                >
                  <div class="text-sm font-semibold text-slate-200">{{ g.nbrGuildMembers }}</div>
                  <div>Membres</div>
                </div>
                <div
                  class="rounded-lg bg-white/5 py-2 ring-1 ring-inset ring-white/10 group-hover:bg-white/10"
                >
                  <div class="text-sm font-semibold text-slate-200">{{ g.nbrGuildMembers }}</div>
                  <div>Persos</div>
                </div>
              </div>
            </div>
          </span>
        </span>
      </RouterLink>

      <button
        :class="[
          'block w-full max-w-[280px] rounded-2xl p-[1px] transition cursor-pointer',
          open
            ? 'bg-gradient-to-br from-indigo-500/40 via-fuchsia-500/30 to-cyan-400/30 shadow-[0_0_0_1px_rgba(255,255,255,0.08)]'
            : 'bg-gradient-to-br from-white/10 via-white/5 to-transparent hover:from-indigo-500/20 hover:via-fuchsia-500/10 hover:to-transparent',
        ]"
        aria-label="Ajouter une guilde"
        @click="open = true"
        @keyup.enter="open = true"
        @keyup.space.prevent="open = true"
      >
        <span
          :class="[
            'flex aspect-[4/3] items-center justify-center rounded-[calc(theme(borderRadius.2xl)-1px)] ring-1 ring-inset transition backdrop-blur-sm',
            open
              ? 'bg-indigo-600/15 text-white ring-white/10 shadow-inner shadow-indigo-600/20'
              : 'bg-slate-900/50 text-slate-200 ring-white/10 hover:bg-white/5 hover:text-white',
          ]"
        >
          <span class="text-3xl leading-none">➕</span>
        </span>
      </button>
    </div>

    <GuildCreateModal v-model="open" @created="onGuildCreated" />
    <Toaster :items="notifications" />
  </section>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import Toaster from '@/components/Toaster.vue'
import GuildCreateModal from '@/components/guilds/GuildCreateModal.vue'
import type { GameGuild } from '@/interfaces/GameGuild.interface'
import { getAllGameGuild, getMyGuild } from '@/services/gameGuild.service.ts'

type ToastType = 'success' | 'error' | 'warning' | 'info'
interface Notification {
  id: string
  message: string
  type: ToastType
}

const notifications = ref<Notification[]>([])
const gameGuilds = ref<GameGuild[]>([])
const open = ref(false)
const loading = ref(true)
const error = ref<string | null>(null)

function initials(name?: string) {
  if (!name) return 'GT'
  const parts = name.split(' ').filter(Boolean)
  const a = parts[0]?.[0] ?? name[0]
  const b = parts[1]?.[0] ?? ''
  return (a + b).toUpperCase()
}

function onGuildCreated(newGuild: { id: string }) {
  gameGuilds.value.unshift({ guildId: newGuild.id } as any)
  toast(`Guild was created!`, 'success')
}

const genId = () => Date.now().toString(36) + Math.random().toString(36).slice(2)
const toast = (m: string, t: ToastType = 'info') => {
  const id = genId()
  notifications.value.push({ id, message: m, type: t })
  setTimeout(() => (notifications.value = notifications.value.filter((n) => n.id !== id)), 3000)
}

onMounted(async () => {
  try {
    const res = await getMyGuild()
    if (res.ok) {
      gameGuilds.value = res.data
    } else {
      error.value = res.error
    }
  } catch (e: any) {
    error.value = e?.message ?? 'Network error'
  } finally {
    loading.value = false
  }
})
</script>
