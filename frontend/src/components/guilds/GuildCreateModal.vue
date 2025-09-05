<template>
  <BaseModal v-model="model" title="Create a guild" @open="reset">
    <form @submit.prevent="submit" class="space-y-4" novalidate>
      <div>
        <label for="guild-name" class="block text-sm font-medium text-slate-200">Guild name</label>
        <input
          id="guild-name"
          v-model.trim="name"
          type="text"
          required
          minlength="2"
          maxlength="50"
          autocomplete="off"
          autocapitalize="none"
          spellcheck="false"
          class="mt-1 block w-full rounded-lg bg-slate-800 text-slate-100 placeholder-slate-400 ring-1 ring-inset ring-white/10 focus:ring-2 focus:ring-indigo-500 px-3 py-2"
          placeholder="e.g. Tracker"
          :aria-invalid="!!fieldError"
          aria-describedby="guild-name-error"
        />
        <p v-if="fieldError" id="guild-name-error" class="mt-1 text-sm text-red-400">{{ fieldError }}</p>
      </div>

      <fieldset class="space-y-2">
        <legend class="text-sm font-medium text-slate-200">Faction</legend>
        <div class="flex items-center gap-6">
          <label for="faction-horde" class="inline-flex items-center gap-2 text-sm text-slate-200">
            <input
              class="ml-0"
              type="radio"
              id="faction-horde"
              name="faction"
              value="HORDE"
              v-model="faction"
              required
            />
            Horde
          </label>

          <label for="faction-alliance" class="inline-flex items-center gap-2 text-sm text-slate-200">
            <input
              class="ml-0"
              type="radio"
              id="faction-alliance"
              name="faction"
              value="ALLIANCE"
              v-model="faction"
              required
            />
            Alliance
          </label>
        </div>
        <p v-if="factionError" class="mt-1 text-sm text-red-400">{{ factionError }}</p>
      </fieldset>

      <div class="flex items-center justify-end gap-2">
        <button
          type="button"
          class="rounded-lg px-3 py-2 text-sm text-slate-300 hover:text-white hover:bg-white/5 focus:outline-none focus:ring-2 focus:ring-indigo-500"
          @click="model = false"
          :disabled="loading"
        >
          Cancel
        </button>
        <button
          type="submit"
          class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50"
          :disabled="!isFormValid || loading"
        >
          <svg v-if="loading" class="mr-2 h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="12" cy="12" r="10" stroke="currentColor" opacity=".25" stroke-width="4"/>
            <path d="M22 12a10 10 0 0 1-10 10" stroke="currentColor" stroke-width="4"/>
          </svg>
          Create
        </button>
      </div>

      <p v-if="serverError" class="text-sm text-red-400">{{ serverError }}</p>
      <p v-if="success" class="text-sm text-green-400">Guild created successfully.</p>
    </form>
  </BaseModal>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, ref } from 'vue'
import BaseModal from '@/components/ui/BaseModal.vue'

type GuildPayload = { name: string; faction: 'HORDE' | 'ALLIANCE' }
type GuildResponse = { id: string; name: string; faction: 'HORDE' | 'ALLIANCE' }

const props = defineProps<{ modelValue: boolean }>()
const emit = defineEmits<{
  (e: 'update:modelValue', v: boolean): void
  (e: 'created', payload: GuildResponse): void
}>()

const model = computed({
  get: () => props.modelValue,
  set: (v: boolean) => emit('update:modelValue', v),
})

const name = ref('')
const faction = ref<GuildPayload['faction'] | ''>('')
const loading = ref(false)
const success = ref(false)
const fieldError = ref<string | null>(null)
const factionError = ref<string | null>(null)
const serverError = ref<string | null>(null)

let currentAbort: AbortController | null = null

const isFormValid = computed(() => name.value.trim().length >= 2 && (faction.value === 'HORDE' || faction.value === 'ALLIANCE'))

function reset() {
  name.value = ''
  faction.value = ''
  loading.value = false
  success.value = false
  fieldError.value = null
  factionError.value = null
  serverError.value = null
  if (currentAbort) {
    currentAbort.abort()
    currentAbort = null
  }
}

onBeforeUnmount(() => {
  if (currentAbort) currentAbort.abort()
})

async function submit() {
  fieldError.value = null
  factionError.value = null
  serverError.value = null
  success.value = false

  if (name.value.trim().length < 2) {
    fieldError.value = 'Name must be at least 2 characters.'
    return
  }
  if (faction.value !== 'HORDE' && faction.value !== 'ALLIANCE') {
    factionError.value = 'Please choose a faction.'
    return
  }

  loading.value = true
  currentAbort?.abort()
  currentAbort = new AbortController()

  try {
    const base = (import.meta.env.VITE_API_URL ?? '').replace(/\/$/, '')
    const url = `${base}/api/gameguild/create`
    const payload: GuildPayload = { name: name.value.trim(), faction: faction.value }

    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(payload),
      credentials: 'include',
      signal: currentAbort.signal,
    })

    if (!res.ok) {
      let message = `Error ${res.status}`
      try {
        const body = await res.json()
        if (Array.isArray(body?.violations) && body.violations.length) {
          message = body.violations.map((v: any) => v.message).join(' · ')
        } else if (typeof body?.detail === 'string') {
          message = body.detail
        } else if (typeof body?.message === 'string') {
          message = body.message
        }
      } catch {
      }
      throw new Error(message)
    }

    const data = (await res.json()) as GuildResponse
    success.value = true
    emit('created', data)
    setTimeout(() => (model.value = false), 250)
  } catch (e: any) {
    if (e?.name === 'AbortError') return
    serverError.value = e?.message ?? 'Unknown error'
  } finally {
    loading.value = false
  }
}
</script>
