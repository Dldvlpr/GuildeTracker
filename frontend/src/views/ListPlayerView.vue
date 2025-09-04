<template>
  <section class="mx-auto max-w-6xl flex flex-col gap-4">
    <PlayersHeaderStats
      :total="characters.length"
      :tanks="getCharactersByRole('Tanks').length"
      :healers="getCharactersByRole('Healers').length"
      :dps="getDpsCount()"
    />

    <PlayersFilters :classes="availableClasses" v-model="filters" @clear="clearFilters" />

    <div v-if="filteredCharacters.length === 0" class="flex items-center justify-center min-h-[280px] p-8" role="status" aria-live="polite">
      <div class="max-w-md text-center">
        <span class="mb-3 block text-4xl">👤</span>
        <h3 class="mb-2 text-xl font-bold text-white">No characters found</h3>
        <p v-if="characters.length === 0" class="text-slate-400">You haven’t created any characters yet.</p>
        <p v-else class="text-slate-400">No characters match the selected filters.</p>
      </div>
    </div>

    <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <CharacterCard
        v-for="c in filteredCharacters"
        :key="c.id"
        :character="c"
        @edit="editCharacter"
        @delete="deleteCharacter"
      />
    </div>

    <Toaster :items="notifications" />
  </section>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import PlayersHeaderStats from '@/components/PlayersHeaderStats.vue'
import PlayersFilters from '@/components/PlayersFilters.vue'
import CharacterCard from '@/components/CharacterCard.vue'
import Toaster from '@/components/Toaster.vue'

import type { Character } from '@/interfaces/game.interface'

type ToastType = 'success' | 'error' | 'warning' | 'info'
interface Notification {
  id: string
  message: string
  type: ToastType
}

const characters = ref<Character[]>([])
const notifications = ref<Notification[]>([])

const filters = ref<{ class: string; role: string }>({ class: '', role: '' })

const availableClasses = computed<string[]>(() => {
  const s = new Set(characters.value.map((c) => c.class))
  return Array.from(s).sort()
})

const filteredCharacters = computed<Character[]>(() => {
  let out = [...characters.value]
  if (filters.value.class) out = out.filter((c) => c.class === filters.value.class)
  if (filters.value.role) out = out.filter((c) => c.role === filters.value.role)
  return out.sort((a, b) => a.name.localeCompare(b.name))
})

const getCharactersByRole = (role: string) => characters.value.filter((c) => c.role === role)

const getDpsCount = () => getCharactersByRole('Melee').length + getCharactersByRole('Ranged').length

const genId = () => Date.now().toString(36) + Math.random().toString(36).slice(2)

const pushToast = (message: string, type: ToastType = 'info') => {
  const n = { id: genId(), message, type }
  notifications.value.push(n)
  setTimeout(() => {
    const i = notifications.value.findIndex((x) => x.id === n.id)
    if (i >= 0) notifications.value.splice(i, 1)
  }, 3000)
}

const editCharacter = () => {
  pushToast('Edit feature to be implemented.', 'info')
}

const deleteCharacter = (id: string) => {
  const c = characters.value.find((x) => x.id === id)
  if (!c) return
  if (confirm(`Are you sure you want to delete "${c.name}"?`)) {
    characters.value = characters.value.filter((x) => x.id !== id)
    saveToLocalStorage()
    pushToast(`Character "${c.name}" deleted.`, 'success')
  }
}

const clearFilters = () => {
  filters.value = { class: '', role: '' }
}

const saveToLocalStorage = () => {
  try {
    localStorage.setItem('wow-characters', JSON.stringify(characters.value))
  } catch {
    pushToast('An error occurred while saving.', 'error')
  }
}

const loadFromLocalStorage = () => {
  try {
    const saved = localStorage.getItem('wow-characters')
    if (saved) {
      const parsed = JSON.parse(saved)
      if (Array.isArray(parsed)) {
        characters.value = parsed
      }
    }
  } catch {
    pushToast('An error occurred while loading saved data.', 'warning')
  }
}

onMounted(loadFromLocalStorage)
</script>

<style scoped>
</style>
