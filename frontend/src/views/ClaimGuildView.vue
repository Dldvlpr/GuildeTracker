<script setup lang="ts">
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useUserStore } from '@/stores/userStore'
import { redirectToBlizzardAuth } from '@/services/auth'
import { getBlizzardCharacters, claimGuild as claimGuildApi } from '@/services/blizzard.service'
import { resolvePostLoginTarget, DEFAULT_POST_LOGIN_REDIRECT } from '@/utils/redirect'

const router = useRouter()
const userStore = useUserStore()
const user = computed(() => userStore.user)
const hasBlizzardLinked = computed(() => user.value?.blizzardId)

interface Character {
  name: string
  level: number
  realm: { name: string; slug: string }
  playable_class?: { name: string }
  wow_type?: string
}

const step = ref<'link' | 'loading' | 'select' | 'claiming' | 'success'>('link')
const characters = ref<Character[]>([])
const selectedCharacter = ref<Character | null>(null)
const error = ref('')

onMounted(() => {
  if (hasBlizzardLinked.value) {
    loadCharacters()
  }
})

function handleBlizzardLink() {
  const target = resolvePostLoginTarget(window.location.pathname, DEFAULT_POST_LOGIN_REDIRECT)
  redirectToBlizzardAuth(target)
}

async function loadCharacters() {
  step.value = 'loading'
  error.value = ''

  try {
    const response = await getBlizzardCharacters()
    characters.value = response.characters || []

    if (characters.value.length === 0) {
      error.value = 'No characters found on your Battle.net account'
      step.value = 'link'
    } else {
      step.value = 'select'
    }
  } catch (e: any) {
    error.value = e?.message || 'Failed to load characters from Battle.net'
    step.value = 'link'
  }
}

function selectCharacter(char: Character) {
  selectedCharacter.value = char
  error.value = ''
}

async function handleClaimGuild() {
  if (!selectedCharacter.value) return

  step.value = 'claiming'
  error.value = ''

  try {
    const result = await claimGuildApi(
      selectedCharacter.value.realm.slug,
      selectedCharacter.value.name,
      selectedCharacter.value.wow_type
    )

    step.value = 'success'

    setTimeout(() => {
      if (result?.guild?.id) {
        router.push({ name: 'guildDetails', params: { id: result.guild.id } })
      } else {
        router.push({ name: 'home' })
      }
    }, 2000)
  } catch (e: any) {
    error.value = e?.message || 'Failed to claim guild'
    step.value = 'select'
  }
}

function goBack() {
  router.push({ name: 'home', query: { stay: 'true' } })
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-4xl">
      <div class="text-center mb-8">
        <button
          @click="goBack"
          class="inline-flex items-center gap-2 text-sm text-slate-400 hover:text-white transition mb-6"
        >
          ← Back to home
        </button>

        <h1 class="text-3xl md:text-4xl font-bold mb-3">
          Claim Your Guild from <span class="text-indigo-400">Blizzard</span>
        </h1>
        <p class="text-slate-400">Import your guild directly from Battle.net with real-time API sync</p>
      </div>

      <div v-if="error" class="mb-6 rounded-xl bg-red-500/10 border border-red-500/20 p-4">
        <div class="flex items-start gap-3">
          <svg class="w-5 h-5 text-red-400 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <div class="flex-1">
            <p class="text-sm text-red-200">{{ error }}</p>
          </div>
          <button @click="error = ''" class="text-red-300 hover:text-red-100">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
        </div>
      </div>

      <div v-if="step === 'link'" class="rounded-2xl border border-white/10 bg-white/5 backdrop-blur p-8 md:p-12 text-center">
        <div class="inline-flex h-20 w-20 rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 items-center justify-center mb-6 shadow-lg shadow-blue-600/30">
          <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 24 24">
            <path d="M11.93 1.93c-.51-.51-1.35-.51-1.86 0L2.21 9.79c-.59.59-.59 1.54 0 2.12l7.86 7.86c.51.51 1.35.51 1.86 0l7.86-7.86c.59-.59.59-1.54 0-2.12l-7.86-7.86zm.07 3.44l4.68 4.68-4.68 4.68-4.68-4.68L12 5.37z"/>
          </svg>
        </div>

        <h2 class="text-2xl font-bold mb-3">Connect Your Battle.net Account</h2>
        <p class="text-slate-400 mb-8 max-w-md mx-auto">
          To claim your guild, we need to access your character list from Blizzard's API. This is safe and secure.
        </p>

        <button
          @click="handleBlizzardLink"
          class="inline-flex items-center gap-3 px-8 py-4 rounded-xl font-semibold bg-gradient-to-r from-blue-600 to-blue-500 hover:from-blue-500 hover:to-blue-600 text-white shadow-lg shadow-blue-600/50 transition-all"
        >
          <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
            <path d="M11.93 1.93c-.51-.51-1.35-.51-1.86 0L2.21 9.79c-.59.59-.59 1.54 0 2.12l7.86 7.86c.51.51 1.35.51 1.86 0l7.86-7.86c.59-.59.59-1.54 0-2.12l-7.86-7.86zm.07 3.44l4.68 4.68-4.68 4.68-4.68-4.68L12 5.37z"/>
          </svg>
          Connect Battle.net
        </button>
      </div>

      <div v-else-if="step === 'loading'" class="rounded-2xl border border-white/10 bg-white/5 backdrop-blur p-12 text-center">
        <div class="inline-flex h-20 w-20 rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-600 items-center justify-center mb-6 animate-pulse">
          <svg class="w-10 h-10 text-white animate-spin" fill="none" viewBox="0 0 24 24">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
        </div>
        <h2 class="text-2xl font-bold mb-3">Loading Your Characters...</h2>
        <p class="text-slate-400">Fetching your character list from Blizzard API</p>
      </div>

      <div v-else-if="step === 'select'" class="space-y-6">
        <div class="rounded-xl border border-indigo-500/20 bg-indigo-500/5 p-4">
          <div class="flex gap-3">
            <div class="text-xl">ℹ️</div>
            <div class="text-sm text-slate-300">
              <strong>How it works:</strong> Select any character from your guild. Your role will be automatically assigned based on your rank in-game:
              <ul class="mt-2 ml-4 space-y-1 text-xs">
                <li><span class="font-semibold text-indigo-400">Rank 0</span> → Guild Master role</li>
                <li><span class="font-semibold text-indigo-400">Rank 1</span> → Officer role</li>
                <li><span class="font-semibold text-indigo-400">Rank 2+</span> → Member role</li>
              </ul>
              <p class="mt-2">The full guild roster will be imported from Blizzard, regardless of your rank.</p>
            </div>
          </div>
        </div>

        <div class="rounded-2xl border border-white/10 bg-white/5 backdrop-blur p-6">
          <h2 class="text-xl font-semibold mb-4">Select a Character from Your Guild</h2>
          <p class="text-sm text-slate-400 mb-6">Found {{ characters.length }} characters on your account</p>

          <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 max-h-[60vh] overflow-y-auto pr-2 custom-scrollbar">
            <button
              v-for="char in characters"
              :key="`${char.realm.slug}-${char.name}`"
              @click="selectCharacter(char)"
              class="group text-left rounded-xl border p-4 transition-all"
              :class="
                selectedCharacter?.name === char.name && selectedCharacter?.realm.slug === char.realm.slug
                  ? 'border-indigo-500 bg-indigo-500/10 ring-2 ring-indigo-500/50'
                  : 'border-white/10 bg-white/5 hover:border-indigo-500/50 hover:bg-white/10'
              "
            >
              <div class="flex items-start justify-between mb-3">
                <div class="flex-1 min-w-0">
                  <h3 class="font-semibold text-lg truncate" :class="selectedCharacter?.name === char.name ? 'text-indigo-300' : 'text-white'">
                    {{ char.name }}
                  </h3>
                </div>
                <div class="flex-shrink-0 ml-2">
                  <div class="text-xs font-medium px-2 py-1 rounded-md" :class="selectedCharacter?.name === char.name ? 'bg-indigo-500/20 text-indigo-300' : 'bg-white/5 text-slate-400'">
                    Lvl {{ char.level }}
                  </div>
                </div>
              </div>

              <div class="flex items-center justify-between text-xs">
                <span class="text-slate-400">{{ char.playable_class?.name || 'Unknown' }}</span>
                <span class="text-indigo-400 font-medium">{{ char.realm.name }}</span>
              </div>

              <div
                v-if="selectedCharacter?.name === char.name && selectedCharacter?.realm.slug === char.realm.slug"
                class="absolute top-2 right-2 h-6 w-6 rounded-full bg-indigo-500 flex items-center justify-center"
              >
                <svg class="w-4 h-4 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
              </div>
            </button>
          </div>
        </div>

        <div v-if="selectedCharacter" class="flex flex-col sm:flex-row gap-4 items-center justify-center">
          <button
            @click="handleClaimGuild"
            class="w-full sm:w-auto inline-flex items-center justify-center gap-3 px-8 py-4 rounded-xl font-semibold bg-gradient-to-r from-indigo-600 to-indigo-500 hover:from-indigo-500 hover:to-indigo-600 text-white shadow-lg shadow-indigo-600/50 transition-all"
          >
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            Claim Guild with {{ selectedCharacter.name }}
          </button>
        </div>
      </div>

      <div v-else-if="step === 'claiming'" class="rounded-2xl border border-white/10 bg-white/5 backdrop-blur p-12 text-center">
        <div class="inline-flex h-20 w-20 rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-600 items-center justify-center mb-6 animate-pulse">
          <svg class="w-10 h-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
          </svg>
        </div>
        <h2 class="text-2xl font-bold mb-3">Claiming Your Guild...</h2>
        <p class="text-slate-400">Verifying your rank and importing data from Blizzard</p>
      </div>

      <div v-else-if="step === 'success'" class="rounded-2xl border border-emerald-500/20 bg-emerald-500/10 backdrop-blur p-12 text-center">
        <div class="inline-flex h-20 w-20 rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-600 items-center justify-center mb-6 shadow-lg shadow-emerald-600/30">
          <svg class="w-12 h-12 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
          </svg>
        </div>
        <h2 class="text-2xl font-bold mb-3 text-emerald-300">Guild Claimed Successfully!</h2>
        <p class="text-slate-300">Redirecting to your guild dashboard...</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: rgba(255, 255, 255, 0.05);
  border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background: rgba(99, 102, 241, 0.5);
  border-radius: 3px;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background: rgba(99, 102, 241, 0.7);
}
</style>
