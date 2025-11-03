<template>
  <section class="mx-auto max-w-6xl px-4 py-8">
    <div v-if="loading" class="text-center text-slate-400 py-12">Loading...</div>

    <div v-else-if="error" class="text-center text-red-400 py-12">
      {{ error }}
    </div>

    <div v-else>
      <header class="flex items-center justify-between mb-8">
        <div>
          <h1 class="text-2xl md:text-3xl font-bold text-white mb-2">👑 Roles System</h1>
          <p class="text-slate-300">
            Manage roles and permissions for {{ guild?.name }} members
          </p>
          <!-- Dev Mode: Simulate GM -->
          <div v-if="isDev" class="mt-2 p-2 rounded-lg bg-yellow-500/10 border border-yellow-500/20">
            <div class="flex items-center gap-2 text-xs text-yellow-300">
              <span>🔧 DEV MODE:</span>
              <label class="flex items-center gap-1">
                <input type="checkbox" v-model="debugSimulateGM" class="rounded">
                <span>Simulate you are the first GM in the list</span>
              </label>
            </div>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <button
            type="button"
            @click="openInviteModal"
            class="text-sm px-4 py-2 rounded-lg bg-emerald-500/20 text-emerald-200 ring-1 ring-inset ring-emerald-500/30 hover:bg-emerald-500/30 transition"
          >
            ➕ Invite Player
          </button>
          <RouterLink
            :to="`/guild/${guildId}`"
            class="text-sm px-4 py-2 rounded-lg ring-1 ring-inset ring-white/10 hover:ring-white/20 text-slate-200 hover:text-white transition"
          >
            ← Back to guild hub
          </RouterLink>
        </div>
      </header>

      <div class="space-y-6">
        <div class="flex gap-4 items-center">
          <div class="flex-1">
            <input
              v-model="searchTerm"
              type="text"
              placeholder="Search for a member..."
              class="w-full px-4 py-2 rounded-lg bg-white/5 border border-white/10 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500"
            />
          </div>
          <div class="text-sm text-slate-400">
            {{ paginatedMembers.length }} / {{ filteredMembers.length }} members
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
                  Name
                  <span v-if="sortColumn === 'name'" class="ml-1">
                    {{ sortDirection === 'asc' ? '↑' : '↓' }}
                  </span>
                </th>
                <th
                  @click="toggleSort('role')"
                  class="px-6 py-4 text-center text-sm font-medium text-slate-300 hover:text-white cursor-pointer"
                  style="width: 30%"
                >
                  Role
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
                  <div class="flex items-center gap-2">
                    <span>{{ member.name }}</span>
                    <span v-if="isCurrentUserGM(member)" class="text-xs px-2 py-0.5 rounded-md bg-indigo-500/20 text-indigo-300">You</span>
                  </div>
                </td>
                <td class="px-6 py-4 text-center" style="width: 30%">
                  <select
                    :value="member.role"
                    @change="updateRole(member.id, ($event.target as HTMLSelectElement).value)"
                    :disabled="isCurrentUserGM(member)"
                    class="bg-white/5 border border-white/10 rounded-lg px-3 py-1 text-white text-sm text-center focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed"
                  >
                    <option value="GM">GM</option>
                    <option value="Officer">Officer</option>
                    <option value="Member">Member</option>
                  </select>
                </td>
                <td class="px-6 py-4 text-center" style="width: 30%">
                  <button
                    @click="deleteMember(member.id)"
                    :disabled="isCurrentUserGM(member)"
                    class="text-red-400 hover:text-red-300 text-sm px-3 py-1 rounded-lg hover:bg-red-500/10 transition disabled:opacity-50 disabled:cursor-not-allowed disabled:hover:bg-transparent disabled:hover:text-red-400"
                  >
                    Delete
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div v-if="totalPages > 1" class="flex items-center justify-between">
          <div class="text-sm text-slate-400">Page {{ currentPage }} of {{ totalPages }}</div>
          <div class="flex gap-2">
            <button
              @click="currentPage = Math.max(1, currentPage - 1)"
              :disabled="currentPage <= 1"
              class="px-4 py-2 rounded-lg text-sm border border-white/10 text-slate-300 hover:bg-white/5 hover:text-white disabled:opacity-50 disabled:cursor-not-allowed transition"
            >
              Previous
            </button>
            <button
              @click="currentPage = Math.min(totalPages, currentPage + 1)"
              :disabled="currentPage >= totalPages"
              class="px-4 py-2 rounded-lg text-sm border border-white/10 text-slate-300 hover:bg-white/5 hover:text-white disabled:opacity-50 disabled:cursor-not-allowed transition"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </div>
  </section>

  <BaseModal v-model="inviteOpen" title="Invite a player">
    <div class="space-y-4">
      <div class="text-sm text-slate-300">Create an invitation link to let a player join the guild with a predefined role.</div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
          <label class="block text-sm font-medium text-slate-200 mb-1">Role to assign</label>
          <select
            v-model="inviteRole"
            class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
          >
            <option value="Member">Member</option>
            <option value="Officer">Officer</option>
            <option value="GM">GM</option>
          </select>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-200 mb-1">Expiration (days)</label>
          <input
            v-model.number="inviteDays"
            type="number"
            min="1"
            max="30"
            class="w-full bg-white/5 border border-white/10 rounded-lg px-3 py-2 text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
          />
          <p class="mt-1 text-xs text-slate-400">Default: 7 days (min 1, max 30)</p>
        </div>
      </div>

      <div v-if="inviteError" class="text-sm text-red-400">{{ inviteError }}</div>

      <div v-if="inviteResult" class="rounded-lg border border-emerald-500/20 bg-emerald-500/10 p-3 text-sm">
        <div class="text-emerald-300 mb-1">Invitation created ✔</div>
        <div class="text-slate-200 break-all"><span class="text-slate-400">Link:</span> {{ inviteLink }}</div>
        <div class="text-slate-400 mt-1">Expires: {{ formatDate(inviteResult.expiresAt) }}</div>
        <div class="mt-3 flex gap-2">
          <button type="button" @click="copy(inviteLink)" class="px-3 py-1.5 text-xs rounded-lg ring-1 ring-inset ring-white/10 hover:ring-white/20">Copy link</button>
          <button type="button" @click="closeInviteModal" class="px-3 py-1.5 text-xs rounded-lg bg-white/10 hover:bg-white/20">Close</button>
        </div>
      </div>
    </div>

    <template #footer>
      <div class="flex items-center justify-end gap-2">
        <button
          type="button"
          class="px-4 py-2 text-sm rounded-lg ring-1 ring-inset ring-white/10 hover:ring-white/20"
          @click="closeInviteModal"
          :disabled="inviteLoading"
        >
          Cancel
        </button>
        <button
          type="button"
          class="px-4 py-2 text-sm rounded-lg bg-indigo-500/20 text-indigo-200 ring-1 ring-inset ring-indigo-500/30 hover:bg-indigo-500/30 disabled:opacity-60"
          @click="submitInvitation"
          :disabled="inviteLoading || !!inviteResult"
        >
          <span v-if="!inviteLoading">Generate invitation</span>
          <span v-else>Creating…</span>
        </button>
      </div>
    </template>
  </BaseModal>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import type { GameGuild } from '@/interfaces/GameGuild.interface'
import type { GuildMembership } from '@/interfaces/GuildMembership.interface.ts'
import {
  deleteMemberRole,
  getAllMembership,
  updateMemberRole,
} from '@/services/guildMembership.service.ts'
import BaseModal from '@/components/ui/BaseModal.vue'
import { createGuildInvitation } from '@/services/guildInvitation.service'
import { useUserStore } from '@/stores/userStore'

defineOptions({ name: 'GuildRolesView' })

const userStore = useUserStore()

const route = useRoute()
const guildId = ref<string | null>(null)
const loading = ref(true)
const error = ref<string | null>(null)

// Dev mode: simulate being GM
const isDev = import.meta.env.DEV
const debugSimulateGM = ref(false)
const debugGMMember = ref<GuildMembership | null>(null)
const guild = ref<GameGuild | null>(null)
const guildMemberships = ref<GuildMembership[]>([])
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
      const aValue = a[sortColumn.value as keyof GuildMembership]
      const bValue = b[sortColumn.value as keyof GuildMembership]
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

// Check if a member is the current user and is GM
const isCurrentUserGM = (member: GuildMembership): boolean => {
  // Dev mode: simulate being the first GM
  if (isDev && debugSimulateGM.value && debugGMMember.value) {
    return member.id === debugGMMember.value.id
  }

  if (!userStore.user) return false

  // Check by userId if available
  if (member.userId && userStore.user.id) {
    return member.userId === userStore.user.id && member.role === 'GM'
  }

  // Fallback: check by username (Discord username)
  return member.name === userStore.user.username && member.role === 'GM'
}

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

    // Prevent GM from changing their own role
    if (member && isCurrentUserGM(member)) {
      error.value = 'You cannot modify your own role as GM'
      setTimeout(() => {
        if (error.value === 'You cannot modify your own role as GM') {
          error.value = null
        }
      }, 3000)
      return
    }

    if (member) {
      member.role = newRole
    }

    const result = await updateMemberRole(memberId, newRole)

    if (!result.ok) {
      if (member && oldRole) {
        member.role = oldRole
      }
      error.value = `Error updating role: ${result.error}`

      setTimeout(() => {
        if (error.value?.startsWith('Error updating role:')) {
          error.value = null
        }
      }, 5000)
    } else {
      console.log('Role updated successfully:', result.data)
    }
  } catch (e: any) {
    error.value = 'An unexpected error occurred'

    await load()
  }
}

const deleteMember = async (memberId: string) => {
  try {
    const member = guildMemberships.value.find((m) => m.id === memberId)

    if (!member) {
      error.value = 'An unexpected error occurred'
      return
    }

    // Prevent GM from deleting themselves
    if (isCurrentUserGM(member)) {
      error.value = 'You cannot delete yourself as GM'
      setTimeout(() => {
        if (error.value === 'You cannot delete yourself as GM') {
          error.value = null
        }
      }, 3000)
      return
    }

    await deleteMemberRole(memberId);
  } catch (e: any) {
    error.value = 'An unexpected error occurred'
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

    // Dev mode: find first GM for simulation
    if (isDev) {
      debugGMMember.value = res.data.find(m => m.role === 'GM') || null
    }
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

const inviteOpen = ref(false)
const inviteRole = ref<'GM' | 'Officer' | 'Member'>('Member')
const inviteDays = ref<number>(7)
const inviteLoading = ref(false)
const inviteError = ref<string | null>(null)
const inviteResult = ref<{ token: string; expiresAt?: string } | null>(null)
const inviteLink = ref('')

function openInviteModal() {
  inviteOpen.value = true
  inviteRole.value = 'Member'
  inviteDays.value = 7
  inviteError.value = null
  inviteResult.value = null
  inviteLink.value = ''
}

function closeInviteModal() {
  inviteOpen.value = false
}

function formatDate(iso?: string) {
  if (!iso) return '—'
  try {
    const d = new Date(iso)
    return d.toLocaleString()
  } catch {
    return iso
  }
}

async function submitInvitation() {
  if (!guildId.value) return
  inviteLoading.value = true
  inviteError.value = null
  inviteResult.value = null
  try {
    const res = await createGuildInvitation(guildId.value, {
      role: inviteRole.value,
      expiresInDays: Math.max(1, Math.min(30, Number(inviteDays.value) || 7)),
    })
    if (!res.ok) {
      inviteError.value = res.error
      return
    }
    inviteResult.value = { token: res.data.token, expiresAt: res.data.expiresAt }
    inviteLink.value = buildInviteUrl(res.data.token)
  } catch (e: any) {
    inviteError.value = e?.message ?? 'Unexpected error'
  } finally {
    inviteLoading.value = false
  }
}

async function copy(text: string) {
  try {
    await navigator.clipboard.writeText(text)
  } catch {
    const ta = document.createElement('textarea')
    ta.value = text
    ta.style.position = 'fixed'
    ta.style.opacity = '0'
    document.body.appendChild(ta)
    ta.select()
    try { document.execCommand('copy') } catch {}
    document.body.removeChild(ta)
  }
}

function buildInviteUrl(token: string) {
  const base = (import.meta.env.BASE_URL || '/') as string
  const trimmed = base.endsWith('/') ? base.slice(0, -1) : base
  const path = `${trimmed}/invite/${token}`
  try {
    return new URL(path, window.location.origin).toString()
  } catch {
    return `${window.location.origin}${path}`
  }
}
</script>

<style scoped></style>
