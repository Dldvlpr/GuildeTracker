<template>
  <section class="mx-auto max-w-6xl px-4 py-8">
    <div v-if="loading" class="text-center text-slate-400 py-12">Chargement...</div>

    <div v-else-if="error" class="text-center text-red-400 py-12">
      {{ error }}
    </div>

    <div v-else>
      <header class="flex items-center justify-between mb-8">
        <div>
          <h1 class="text-2xl md:text-3xl font-bold text-white mb-2">👑 Système de rôles</h1>
          <p class="text-slate-300">
            Gérer les rôles et permissions des membres de {{ guild?.name }}
          </p>
        </div>
        <RouterLink
          :to="`/guild/${guildId}`"
          class="text-sm px-4 py-2 rounded-lg ring-1 ring-inset ring-white/10 hover:ring-white/20 text-slate-200 hover:text-white transition"
        >
          ← Retour aux fonctionnalités
        </RouterLink>
      </header>

      <div class="space-y-6">
        <!-- Barre de recherche -->
        <div class="flex gap-4 items-center">
          <div class="flex-1">
            <input
              v-model="searchTerm"
              type="text"
              placeholder="Rechercher un membre..."
              class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div class="text-sm text-slate-400">
            {{ paginatedMembers.length }} / {{ filteredMembers.length }} membres
          </div>
        </div>

        <div class="rounded-2xl border border-white/10 bg-white/5 overflow-hidden">
          <table class="w-full">
            <thead class="bg-white/5">
              <tr>
                <th
                  @click="toggleSort('name')"
                  class="px-6 py-4 text-left text-sm font-medium text-slate-300 hover:text-white cursor-pointer"
                  style="width: 40%"
                >
                  Nom
                  <span v-if="sortColumn === 'name'" class="ml-1">
                    {{ sortDirection === 'asc' ? '↑' : '↓' }}
                  </span>
                </th>
                <th
                  @click="toggleSort('role')"
                  class="px-6 py-4 text-center text-sm font-medium text-slate-300 hover:text-white cursor-pointer"
                  style="width: 30%"
                >
                  Rôle
                  <span v-if="sortColumn === 'role'" class="ml-1">
                    {{ sortDirection === 'asc' ? '↑' : '↓' }}
                  </span>
                </th>
                <th
                  class="px-6 py-4 text-center text-sm font-medium text-slate-300"
                  style="width: 30%"
                >
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="divide-y divide-white/10">
              <tr v-for="member in paginatedMembers" :key="member.id" class="hover:bg-white/5">
                <td class="px-6 py-4 text-white" style="width: 40%">
                  {{ member.name }}
                </td>
                <td class="px-6 py-4 text-center" style="width: 30%">
                  <select
                    :value="member.role"
                    @change="updateRole(member.id, $event.target.value)"
                    class="bg-white/5 border border-white/10 rounded-lg px-3 py-1 text-white text-sm text-center focus:outline-none focus:ring-2 focus:ring-blue-500"
                  >
                    <option value="GM">GM</option>
                    <option value="Officer">Officier</option>
                    <option value="Member">Membre</option>
                  </select>
                </td>
                <td class="px-6 py-4 text-center" style="width: 30%">
                  <button
                    @click="deleteMember(member.id)"
                    class="text-red-400 hover:text-red-300 text-sm px-3 py-1 rounded-lg hover:bg-red-500/10 transition"
                  >
                    Supprimer
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="flex items-center justify-between">
          <div class="text-sm text-slate-400">Page {{ currentPage }} sur {{ totalPages }}</div>
          <div class="flex gap-2">
            <button
              @click="currentPage = Math.max(1, currentPage - 1)"
              :disabled="currentPage <= 1"
              class="px-4 py-2 rounded-lg text-sm border border-white/10 text-slate-300 hover:bg-white/5 hover:text-white disabled:opacity-50 disabled:cursor-not-allowed transition"
            >
              Précédent
            </button>
            <button
              @click="currentPage = Math.min(totalPages, currentPage + 1)"
              :disabled="currentPage >= totalPages"
              class="px-4 py-2 rounded-lg text-sm border border-white/10 text-slate-300 hover:bg-white/5 hover:text-white disabled:opacity-50 disabled:cursor-not-allowed transition"
            >
              Suivant
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import type { GameGuild } from '@/interfaces/GameGuild.interface'
import type { guildMembership } from '@/interfaces/guildMemebership.interface.ts'
import {
  deleteMemberRole,
  getAllMembership,
  updateMemberRole,
} from '@/services/guildMembership.service.ts'

defineOptions({ name: 'GuildRolesView' })

const route = useRoute()
const guildId = ref<string | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)
const guild = ref<GameGuild | null>(null)
const guildMemberships = ref<guildMembership[]>([])
const searchTerm = ref<string>('')
const currentPage = ref(1)
const sortColumn = ref<string | null>(null)
const sortDirection = ref<'asc' | 'desc'>('asc')
const filteredMembers = computed(() => {
  let result = guildMemberships.value
  if (searchTerm.value.trim()) {
    result = result.filter((member) => {
      return member.name.toLowerCase().includes(searchTerm.value.toLowerCase())
    })
  }
  if (sortColumn.value) {
    result = result.sort((a, b) => {
      const aValue = a[sortColumn.value as keyof guildMembership]
      const bValue = b[sortColumn.value as keyof guildMembership]
      if (sortDirection.value === 'asc') {
        return aValue < bValue ? -1 : aValue > bValue ? 1 : 0
      } else {
        return aValue > bValue ? -1 : aValue < bValue ? 1 : 0
      }
    })
  }

  return result
})
const totalPages = computed(() => {
  return Math.ceil(filteredMembers.value.length / 20)
})
const paginatedMembers = computed(() => {
  const startIndex = (currentPage.value - 1) * 20
  const endIndex = startIndex + 20
  return filteredMembers.value.slice(startIndex, endIndex)
})

const toggleSort = (column: 'name' | 'role') => {
  if (sortColumn.value === column) {
    sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
  } else {
    sortColumn.value = column
    sortDirection.value = 'asc'
  }
}

const updateRole = async (memberId: string, newRole: string) => {
  try {
    const member = guildMemberships.value.find((m) => m.id === memberId)
    const oldRole = member?.role

    if (member) {
      member.role = newRole
    }

    const result = await updateMemberRole(memberId, newRole)

    if (!result.ok) {
      if (member && oldRole) {
        member.role = oldRole
      }
      error.value = `Erreur lors de la mise à jour du rôle: ${result.error}`

      setTimeout(() => {
        if (error.value?.startsWith('Erreur lors de la mise à jour du rôle:')) {
          error.value = null
        }
      }, 5000)
    } else {
      console.log('Rôle mis à jour avec succès:', result.data)
    }
  } catch (e: any) {
    error.value = 'Une erreur inattendue est survenue'

    await load()
  }
}

const deleteMember = async (memberId: string) => {
  try {
    const member = guildMemberships.value.find((m) => m.id === memberId)

    if (!member) {
      error.value = 'Une erreur inattendue est survenue'
    }

    await deleteMemberRole(memberId);
  } catch (e: any) {
    error.value = 'Une erreur inattendue est survenue'
  }

  await load()
}

const load = async () => {
  if (!guildId.value) return
  loading.value = true
  error.value = null
  guild.value = null
  const res = await getAllMembership(guildId.value)
  if (res.ok) {
    guildMemberships.value = res.data
  } else {
    error.value = res.error
  }
  loading.value = false
}

onMounted(() => {
  guildId.value = (route.params.id as string) || null
  load()
})

watch(
  () => route.params.id,
  (v) => {
    guildId.value = (v as string) || null
    load()
  },
)
</script>

<style scoped></style>
