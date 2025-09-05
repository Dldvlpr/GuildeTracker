<template>
  <section class="mx-auto max-w-6xl px-4 py-8">
    <div
      :class="[
        'grid gap-6',
        gameGuilds.length > 0
          ? 'justify-items-start grid-cols-2 sm:grid-cols-3 lg:grid-cols-4'
          : 'place-items-center grid-cols-1'
      ]"
    >
      <RouterLink
        v-for="gameGuild in gameGuilds"
        :key="gameGuild.guildId"
        to="/selectGuild"
        v-slot="{ isActive }"
        class="block w-full max-w-[260px]"
      >
        <span
          :class="[
            'block w-full rounded-xl px-4 py-3 text-sm ring-1 ring-inset transition',
            'text-center select-none',
            isActive
              ? 'bg-indigo-600/20 text-white ring-white/10 shadow-inner shadow-indigo-600/20'
              : 'bg-white/0 text-slate-200 ring-white/10 hover:bg-white/5 hover:text-white'
          ]"
        >
          {{ gameGuild.guildName }}
        </span>
      </RouterLink>

      <span
        :class="[
          'block rounded-xl ring-1 ring-inset transition cursor-pointer',
          'flex items-center justify-center',
          'aspect-square min-h-[8rem] sm:min-h-[9rem] lg:min-h-[10rem]',
          open
            ? 'bg-indigo-600/20 text-white ring-white/10 shadow-inner shadow-indigo-600/20'
            : 'bg-white/0 text-slate-200 ring-white/10 hover:bg-white/5 hover:text-white'
        ]"
        aria-label="Add a guild"
        role="button"
        tabindex="0"
        @click="open = true"
        @keyup.enter="open = true"
        @keyup.space="open = true"
      >
        <span class="text-3xl leading-none">➕</span>
      </span>
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

type ToastType = 'success' | 'error' | 'warning' | 'info'
interface Notification { id: string; message: string; type: ToastType }

const notifications = ref<Notification[]>([])
const gameGuilds = ref<GameGuild[]>([])
const open = ref(false)

function onGuildCreated(newGuild: { id: string; name: string }) {
  gameGuilds.value.unshift({ guildId: newGuild.id, guildName: newGuild.name } as any)
  toast(`Guild “${newGuild.name}” created!`, 'success')
}

const genId = () => Date.now().toString(36) + Math.random().toString(36).slice(2)
const toast = (m: string, t: ToastType = 'info') => {
  const id = genId()
  notifications.value.push({ id, message: m, type: t })
  setTimeout(() => (notifications.value = notifications.value.filter(n => n.id !== id)), 3000)
}

onMounted(() => {
})
</script>
