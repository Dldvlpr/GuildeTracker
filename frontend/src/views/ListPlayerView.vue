<template>
  <section class="mx-auto max-w-6xl flex flex-col gap-4">
    <header class="flex items-center justify-between">
      <h1 class="text-xl font-semibold">Guild Members</h1>
      <div class="flex items-center gap-2">
        <button
          @click="showCharacterForm = true"
          class="text-sm rounded-lg px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-medium transition cursor-pointer"
        >
          + Add a character
        </button>
        <button
          @click="handleSync"
          :disabled="syncing"
          class="text-sm rounded-lg px-4 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-800 disabled:cursor-not-allowed text-white font-medium transition cursor-pointer">
          {{ syncing ? 'Syncing...' : 'Sync' }}
        </button>
        <RouterLink
          :to="`/guild/${route.params.id}`"
          class="text-sm rounded-lg px-3 py-1.5 ring-1 ring-inset ring-white/10 hover:ring-white/20 transition"
        >
          ← Back to guild hub
        </RouterLink>
      </div>
    </header>

    <PlayersHeaderStats
      :total="charactersWithCalculatedRoles.length"
      :tanks="getCharactersByRole(Role.TANKS).length"
      :healers="getCharactersByRole(Role.HEALERS).length"
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

    <BaseModal
      v-model="showCharacterForm"
      title="Add a Character"
      size="xl"
    >
      <CharacterForm
        @submit="handleCharacterSubmit"
        @bulkImport="handleBulkImport"
      />
    </BaseModal>

    <CharacterEditModal
      :character="editingCharacter"
      :show="showEditModal"
      @close="closeEditModal"
      @save="handleCharacterUpdate"
    />
  </section>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import BaseModal from '@/components/ui/BaseModal.vue'
import PlayersHeaderStats from '@/components/PlayersHeaderStats.vue'
import PlayersFilters from '@/components/PlayersFilters.vue'
import CharacterCard from '@/components/CharacterCard.vue'
import CharacterForm from '@/components/CharacterForm.vue'
import CharacterEditModal from '@/components/CharacterEditModal.vue'
import Toaster from '@/components/Toaster.vue'
import { getCharactersByGuildId, createCharacter, deleteCharacter as deleteCharacterService, syncGuildRoster, updateCharacter } from '@/services/character.service'
import { getRoleByClassAndSpec } from '@/data/gameData'
import { Role } from '@/interfaces/game.interface'
import type { Character, FormSubmitEvent } from '@/interfaces/game.interface'

type ToastType = 'success' | 'error' | 'warning' | 'info'
interface Notification {
  id: string
  message: string
  type: ToastType
}

const route = useRoute()
const characters = ref<Character[]>([])
const notifications = ref<Notification[]>([])
const loading = ref(false)
const syncing = ref(false)
const showCharacterForm = ref(false)
const showEditModal = ref(false)
const editingCharacter = ref<Character | null>(null)

const filters = ref<{ class: string; role: string }>({ class: '', role: '' })

const availableClasses = computed<string[]>(() => {
  const s = new Set(charactersWithCalculatedRoles.value.map((c) => c.class).filter(Boolean))
  return Array.from(s).sort()
})

const normalizeRole = (role: string | undefined): Role | undefined => {
  if (!role) return undefined

  const roleMap: Record<string, Role> = {
    'melee': Role.MELEE,
    'ranged': Role.RANGED,
    'tank': Role.TANKS,
    'tanks': Role.TANKS,
    'healer': Role.HEALERS,
    'healers': Role.HEALERS,
    'Melee': Role.MELEE,
    'Ranged': Role.RANGED,
    'Tanks': Role.TANKS,
    'Healers': Role.HEALERS
  }

  return roleMap[role] || roleMap[role.toLowerCase()]
}

const charactersWithCalculatedRoles = computed<Character[]>(() => {
  return characters.value.map(character => {
    let finalRole = normalizeRole(character.role)

    if (!finalRole && character.class && character.spec) {
      finalRole = getRoleByClassAndSpec(character.class, character.spec)
    }

    return { ...character, role: finalRole }
  })
})

const filteredCharacters = computed<Character[]>(() => {
  let out = [...charactersWithCalculatedRoles.value]
  if (filters.value.class) out = out.filter((c) => c.class === filters.value.class)
  if (filters.value.role) out = out.filter((c) => c.role === filters.value.role)
  return out.sort((a, b) => a.name.localeCompare(b.name))
})

const getCharactersByRole = (role: string) => charactersWithCalculatedRoles.value.filter((c) => c.role === role)

const getDpsCount = () => getCharactersByRole(Role.MELEE).length + getCharactersByRole(Role.RANGED).length

const genId = () => Date.now().toString(36) + Math.random().toString(36).slice(2)

const pushToast = (message: string, type: ToastType = 'info') => {
  const n = { id: genId(), message, type }
  notifications.value.push(n)
  setTimeout(() => {
    const i = notifications.value.findIndex((x) => x.id === n.id)
    if (i >= 0) notifications.value.splice(i, 1)
  }, 3000)
}

const editCharacter = (character: Character) => {
  editingCharacter.value = character
  showEditModal.value = true
}

const closeEditModal = () => {
  showEditModal.value = false
  editingCharacter.value = null
}

const handleCharacterUpdate = async (updates: { role?: string; spec?: string }) => {
  const guildId = route.params.id as string
  const character = editingCharacter.value

  if (!character || !guildId) {
    pushToast('Invalid character or guild', 'error')
    return
  }

  loading.value = true
  try {
    const result = await updateCharacter(guildId, character.id, updates)

    if (result.ok) {
      pushToast('Character updated successfully', 'success')
      closeEditModal()
      await getAllCharactersByGuild()
    } else {
      pushToast(result.error, 'error')
    }
  } catch (error: any) {
    pushToast(error?.message ?? 'Failed to update character', 'error')
  } finally {
    loading.value = false
  }
}

const deleteCharacter = async (id: string) => {
  const character = characters.value.find(c => c.id === id)
  if (!character) {
    pushToast('Character not found', 'error')
    return
  }

  const confirmed = window.confirm(
    `Are you sure you want to delete "${character.name}"?\n\nThis action cannot be undone.`
  )

  if (!confirmed) return

  loading.value = true
  try {
    const result = await deleteCharacterService(id)

    if (result.ok) {
      pushToast(result.message || 'Character deleted successfully', 'success')
      const index = characters.value.findIndex(c => c.id === id)
      if (index >= 0) {
        characters.value.splice(index, 1)
      }
    } else {
      pushToast(result.error, 'error')
    }
  } catch (error: any) {
    pushToast(error?.message ?? 'Failed to delete character', 'error')
  } finally {
    loading.value = false
  }
}

const clearFilters = () => {
  filters.value = { class: '', role: '' }
}

const getAllCharactersByGuild = async () => {
  const guildId = route.params.id as string
  if (!guildId) {
    pushToast('Guild ID not found in URL', 'error')
    return
  }

  loading.value = true
  try {
    const result = await getCharactersByGuildId(guildId)
    if (result.ok) {
      characters.value = result.data
    } else {
      pushToast(result.error, 'error')
    }
  } catch (error: any) {
    pushToast(error?.message ?? 'Failed to load characters', 'error')
  } finally {
    loading.value = false
  }
}

const handleCharacterSubmit = async (event: FormSubmitEvent) => {
  const guildId = route.params.id as string
  if (!guildId) {
    pushToast('Guild ID not found', 'error')
    return
  }

  const result = await createCharacter(event.character, guildId)

  if (result.ok) {
    pushToast(`Character "${event.character.name}" created successfully!`, 'success')
    showCharacterForm.value = false
    await getAllCharactersByGuild()
  } else {
    pushToast(result.error, 'error')
  }
}

const handleBulkImport = async (charactersToImport: Omit<Character, 'id' | 'createdAt'>[]) => {
  const guildId = route.params.id as string
  if (!guildId) {
    pushToast('Guild ID not found', 'error')
    return
  }

  let successCount = 0
  let errorCount = 0

  for (const char of charactersToImport) {
    const result = await createCharacter(char, guildId)
    if (result.ok) {
      successCount++
    } else {
      errorCount++
      console.error(`Failed to import ${char.name}:`, result.error)
    }
  }

  if (successCount > 0) {
    pushToast(`${successCount} character(s) imported successfully!`, 'success')
  }
  if (errorCount > 0) {
    pushToast(`${errorCount} character(s) could not be imported`, 'error')
  }

  showCharacterForm.value = false
  await getAllCharactersByGuild()
}

const handleSync = async () => {
  const guildId = route.params.id as string
  if (!guildId) {
    pushToast('Guild ID not found', 'error')
    return
  }

  syncing.value = true
  try {
    const result = await syncGuildRoster(guildId)

    if (result.ok) {
      pushToast(
        `Sync completed! ${result.created} added, ${result.updated} updated`,
        'success'
      )
      await getAllCharactersByGuild()
    } else {
      if (result.error.includes('blizzard_token_expired')) {
        pushToast('Your Battle.net session expired. Please reconnect.', 'error')
      } else {
        pushToast(result.error, 'error')
      }
    }
  } catch (error: any) {
    pushToast(error?.message ?? 'Failed to sync roster', 'error')
  } finally {
    syncing.value = false
  }
}

onMounted(() => {
  getAllCharactersByGuild()
})
</script>

<style scoped>
</style>
