<template>
  <section class="page">
    <CharacterForm
      form-title="Add character"
      :enable-auto-validation="true"
      @submit="handleCharacterSubmit"
      @error="handleFormError"
    />

    <Toaster :items="notifications" />
  </section>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import CharacterForm from '@/components/CharacterForm.vue'
import Toaster from '@/components/Toaster.vue'
import type {
  Character,
  FormSubmitEvent,
  FormErrors,
  CharacterStatus,
} from '@/interfaces/game.interface'

type ToastType = 'success' | 'error' | 'warning' | 'info'
interface Notification {
  id: string
  message: string
  type: ToastType
}

const characters = ref<Character[]>([])
const notifications = ref<Notification[]>([])

const genId = () => Date.now().toString(36) + Math.random().toString(36).slice(2)
const toast = (m: string, t: ToastType = 'info') => {
  const id = genId()
  notifications.value.push({ id, message: m, type: t })
  setTimeout(() => (notifications.value = notifications.value.filter((n) => n.id !== id)), 3000)
}
const save = () => {
  try {
    localStorage.setItem('wow-characters', JSON.stringify(characters.value))
  } catch {
    toast('An error occurred while saving.', 'error')
  }
}
const load = () => {
  try {
    const s = localStorage.getItem('wow-characters')
    if (s) {
      const arr = JSON.parse(s)
      if (Array.isArray(arr)) {
        characters.value = arr
        toast(`${arr.length} character(s) loaded.`, 'info')
      }
    }
  } catch {
    toast('An error occurred while loading saved data.', 'warning')
  }
}


const handleCharacterSubmit = (event: FormSubmitEvent) => {
  try {
    const exists = new Set(characters.value.map((c) => c.name.toLowerCase()))
    if (exists.has(event.character.name.toLowerCase())) {
      toast('A character with this name already exists.', 'error')
      return
    }
    const newChar: Character = {
      id: genId(),
      createdAt: new Date().toISOString(),
      status: 'active' as CharacterStatus,
      ...event.character,
    }
    characters.value.push(newChar)
    save()
    toast(`Character "${newChar.name}" created successfully!`, 'success')
  } catch (e) {
    console.error(e)
    toast('An error occurred while creating the character.', 'error')
  }
}

const handleFormError = (errors: FormErrors) => {
  if (errors.general) toast(errors.general, 'error')
  else toast('Please fix the form errors.', 'error')
}

onMounted(load)
</script>

<style scoped>
.page {
  max-width: 1200px;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
</style>
