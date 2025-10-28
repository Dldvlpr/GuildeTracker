<template>
  <div>
    <header class="border-b border-white/10">
      <div class="mx-auto max-w-4xl px-4 py-6 flex items-center justify-between">
        <h1 class="text-xl font-semibold">Create a Guild</h1>
        <RouterLink
          to="/"
          class="text-sm rounded-lg px-3 py-1.5 ring-1 ring-inset ring-white/10 hover:ring-white/20"
        >
          ← Back
        </RouterLink>
      </div>
    </header>

    <main class="mx-auto max-w-4xl px-4 py-8">
      <form @submit.prevent="onSubmit" class="space-y-6">
        <div>
          <label for="guild-name" class="block text-sm font-medium text-slate-200">Guild name</label>
          <input
            id="guild-name"
            v-model.trim="name"
            type="text"
            required
            minlength="2"
            maxlength="50"
            class="mt-1 block w-full rounded-lg bg-slate-800 text-slate-100 placeholder-slate-400 ring-1 ring-inset ring-white/10 focus:ring-2 focus:ring-indigo-500 px-3 py-2"
            placeholder="e.g. Tracker"
          />
          <p v-if="fieldError" class="mt-1 text-sm text-red-400">{{ fieldError }}</p>
        </div>

        <fieldset>
          <legend class="text-sm font-medium text-slate-200 mb-2">Faction</legend>
          <div class="flex items-center gap-6">
            <label class="inline-flex items-center gap-2 cursor-pointer">
              <input
                class="accent-indigo-500"
                type="radio"
                value="HORDE"
                v-model="faction"
                required
              />
              <span class="text-slate-300">Horde</span>
            </label>
            <label class="inline-flex items-center gap-2 cursor-pointer">
              <input
                class="accent-indigo-500"
                type="radio"
                value="ALLIANCE"
                v-model="faction"
                required
              />
              <span class="text-slate-300">Alliance</span>
            </label>
          </div>
        </fieldset>

        <div class="flex items-center gap-3">
          <button
            type="submit"
            :disabled="loading"
            class="rounded-xl px-4 py-2 text-sm ring-1 ring-inset ring-white/10 bg-white/5 hover:bg-white/10 disabled:opacity-60 disabled:cursor-not-allowed"
          >
            <span v-if="!loading">Create guild</span>
            <span v-else>Creating…</span>
          </button>

          <p v-if="submitError" class="text-sm text-red-400">{{ submitError }}</p>
          <p v-if="submitOk" class="text-sm text-emerald-400">Guild created successfully.</p>
        </div>
      </form>
    </main>
    <Toaster :items="notifications" />
  </div>
</template>

<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { createGuild } from '@/services/gameGuild.service.ts'
import Toaster from '@/components/Toaster.vue'

const router = useRouter()

const name = ref('')
const faction = ref<'HORDE' | 'ALLIANCE' | ''>('')

const loading = ref(false)
const fieldError = ref('')
const submitError = ref('')
const submitOk = ref(false)

type ToastType = 'success' | 'error' | 'warning' | 'info'
interface Notification { id: string; message: string; type: ToastType }
const notifications = ref<Notification[]>([])
const genId = () => Date.now().toString(36) + Math.random().toString(36).slice(2)
const toast = (m: string, t: ToastType = 'info') => {
  const id = genId()
  notifications.value.push({ id, message: m, type: t })
  setTimeout(() => (notifications.value = notifications.value.filter((n) => n.id !== id)), 3000)
}

function validate() {
  fieldError.value = ''
  if (name.value.length < 2) {
    fieldError.value = 'Name must be at least 2 characters.'
    return false
  }
  if (!faction.value) {
    fieldError.value = 'Please choose a faction.'
    return false
  }
  return true
}

async function onSubmit() {
  submitError.value = ''
  submitOk.value = false

  if (!validate()) return

  loading.value = true
  try {
    const result = await createGuild(name.value, faction.value as 'HORDE' | 'ALLIANCE')
    submitOk.value = true
    if (result?.id) {
      await router.push({ name: 'guildDetails', params: { id: result.id } })
    } else {
      await router.push({ name: 'home' })
    }
  } catch (e: any) {
    submitError.value = e?.message ?? 'Network error'
    toast('Error: guild could not be created', 'error')
  } finally {
    loading.value = false
  }
}
</script>
