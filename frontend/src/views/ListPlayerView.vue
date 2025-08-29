<template>
  <div class="app">
    <main class="app-main">
      <PlayersHeaderStats
        :total="characters.length"
        :tanks="getCharactersByRole('Tanks').length"
        :healers="getCharactersByRole('Healers').length"
        :dps="getDpsCount()"
      />

      <PlayersFilters :classes="availableClasses" v-model="filters" @clear="clearFilters" />

      <div v-if="filteredCharacters.length === 0" class="empty-state">
        <div class="empty-content" role="status" aria-live="polite">
          <span class="empty-icon">👤</span>
          <h3>No characters found</h3>
          <p v-if="characters.length === 0">You haven’t created any characters yet.</p>
          <p v-else>No characters match the selected filters.</p>
        </div>
      </div>

      <div v-else class="characters-grid">
        <CharacterCard
          v-for="c in filteredCharacters"
          :key="c.id"
          :character="c"
          @edit="editCharacter"
          @delete="deleteCharacter"
        />
      </div>
    </main>

    <!-- Toaster -->
    <Toaster :items="notifications" />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import PlayersHeaderStats from '@/components/PlayersHeaderStats.vue'
import PlayersFilters from '@/components/PlayersFilters.vue'
import CharacterCard from '@/components/CharacterCard.vue'
import Toaster from '@/components/Toaster.vue'

import type { Character, Role } from '@/interfaces/game.interface'
import { ROLE_ICONS } from '@/interfaces/game.interface'

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

const getRoleDisplay = (role: Role) => `${ROLE_ICONS[role]} ${role}`

const formatDate = (dateString: string) =>
  new Date(dateString).toLocaleDateString('en-GB', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })

const genId = () => Date.now().toString(36) + Math.random().toString(36).slice(2)

const pushToast = (message: string, type: ToastType = 'info') => {
  const n = { id: genId(), message, type }
  notifications.value.push(n)
  setTimeout(() => {
    const i = notifications.value.findIndex((x) => x.id === n.id)
    if (i >= 0) notifications.value.splice(i, 1)
  }, 3000)
}

const editCharacter = (c: Character) => {
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
        pushToast(`${parsed.length} character(s) loaded.`, 'info')
      }
    }
  } catch {
    pushToast('An error occurred while loading saved data.', 'warning')
  }
}

onMounted(loadFromLocalStorage)
</script>

<style scoped>
.app {
  max-height: 100vh;
  font-family: 'Inter', 'Segoe UI', sans-serif;
}
.app-main {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem 2rem;
}

.empty-state {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 280px;
  padding: 2rem;
}
.empty-content {
  text-align: center;
  max-width: 420px;
}
.empty-icon {
  font-size: 3.2rem;
  display: block;
  margin-bottom: 0.75rem;
}
.empty-content h3 {
  font-size: 1.3rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 0.5rem 0;
}
.empty-content p {
  color: #64748b;
  margin: 0;
}

.characters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 1.25rem;
  padding: 1.25rem;
}

@media (max-width: 768px) {
  .characters-grid {
    grid-template-columns: 1fr;
    padding: 1rem;
  }
}
</style>
