<template>
  <div class="app">
    <main class="app-main">
      <CharacterForm
        :enable-auto-validation="true"
        :existing-names="characters.map(c => c.name)"
      @submit="handleCharacterSubmit"
      @bulkImport="handleBulkImport"
      @notify="({type, message}) => toast(message, type)"
      />
    </main>

    <div class="notifications">
      <div v-for="n in notifications" :key="n.id" class="notification" :class="n.type">
        {{ n.message }}
      </div>
    </div>

  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import CharacterForm from '@/components/CharacterForm.vue'
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
ref(false)
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

const handleBulkImport = (items: Omit<Character,'id'|'createdAt'>[]) => {
  const existing = new Set(characters.value.map(c => c.name.toLowerCase()))
  const now = new Date().toISOString()
  const toAdd: Character[] = []
  for (const it of items) {
    const key = it.name.toLowerCase()
    if (existing.has(key)) continue
    existing.add(key)
    toAdd.push({ id: genId(), createdAt: now, ...it })
  }
  if (!toAdd.length) { toast('Nothing to import (duplicates).', 'warning'); return }
  characters.value.push(...toAdd)
  save()
  toast(`Imported ${toAdd.length} character(s).`, 'success')
}

onMounted(load)
</script>

<style scoped>
.app-main {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem 2rem;
}
.toolbar {
  display: flex;
  justify-content: flex-end;
  padding: 1rem 0;
}
.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.6rem 1.1rem;
  border-radius: 10px;
  font-weight: 700;
  cursor: pointer;
  border: 1px solid #cbd5e1;
  background: transparent;
  color: #334155;
}
.btn:hover {
  background: #f8fafc;
}

.notifications {
  position: fixed;
  top: 1rem;
  right: 1rem;
  z-index: 1000;
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}
.notification {
  padding: 1rem 1.25rem;
  border-radius: 10px;
  color: white;
  font-weight: 600;
  min-width: 300px;
  animation: slideIn 0.25s ease;
}
.notification.success {
  background: #10b981;
}
.notification.error {
  background: #ef4444;
}
.notification.warning {
  background: #f59e0b;
}
.notification.info {
  background: #3b82f6;
}
@keyframes slideIn {
  from {
    transform: translateX(100%);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}
</style>
