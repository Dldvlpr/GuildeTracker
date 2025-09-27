<template>
  <section class="mx-auto max-w-6xl flex flex-col gap-4">
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
  </section>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import PlayersHeaderStats from '@/components/PlayersHeaderStats.vue'
import PlayersFilters from '@/components/PlayersFilters.vue'
import CharacterCard from '@/components/CharacterCard.vue'
import Toaster from '@/components/Toaster.vue'
import { getCharactersByGuildId } from '@/services/character.service'
import { getRoleByClassAndSpec } from '@/data/gameData'
import { Role } from '@/interfaces/game.interface'
import type { Character } from '@/interfaces/game.interface'

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

    // Si pas de rôle ou rôle invalide, calculer à partir de classe/spé
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

const editCharacter = () => {
  pushToast('Edit feature to be implemented.', 'info')
}

const deleteCharacter = (id: string) => {
  pushToast('Delete feature to be implemented.', 'info')
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

onMounted(() => {
  getAllCharactersByGuild()
})
</script>

<style scoped>
</style>
