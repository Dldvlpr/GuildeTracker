<template>
  <div class="app">
    <main class="app-main">
      <CharacterForm
        form-title="Add character"
        :enable-auto-validation="true"
        @submit="handleCharacterSubmit"
        @class-change="handleClassChange"
        @spec-change="handleSpecChange"
        @error="handleFormError"
      />
    </main>

    <div class="notifications">
      <div
        v-for="notification in notifications"
        :key="notification.id"
        class="notification"
        :class="notification.type"
      >
        {{ notification.message }}
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
  ClassChangeEvent,
  SpecChangeEvent,
  FormErrors,
  CharacterStatus,
} from '../interfaces/game.interface'

interface Notification {
  id: string
  message: string
  type: 'success' | 'error' | 'warning' | 'info'
}

const characters = ref<Character[]>([])
const notifications = ref<Notification[]>([])

const generateId = (): string => {
  return Date.now().toString(36) + Math.random().toString(36).substr(2)
}

const showNotification = (message: string, type: Notification['type'] = 'info'): void => {
  const notification: Notification = {
    id: generateId(),
    message,
    type,
  }

  notifications.value.push(notification)

  setTimeout(() => {
    const index = notifications.value.findIndex((n) => n.id === notification.id)
    if (index >= 0) notifications.value.splice(index, 1)
  }, 3000)
}

const handleCharacterSubmit = (event: FormSubmitEvent): void => {
  try {
    const existingNames = characters.value.map((char) => char.name.toLowerCase())
    if (existingNames.includes(event.character.name.toLowerCase())) {
      showNotification('A character with this name already exists.', 'error')
      return
    }

    const newCharacter: Character = {
      id: generateId(),
      createdAt: new Date().toISOString(),
      status: 'active' as CharacterStatus,
      ...event.character,
    }

    characters.value.push(newCharacter)
    saveToLocalStorage()

    showNotification(`Character "${newCharacter.name}" created successfully!`, 'success')
  } catch (error) {
    console.error('Error while creating character:', error)
    showNotification('An error occurred while creating the character.', 'error')
  }
}

const handleClassChange = (event: ClassChangeEvent): void => {
  console.log('Class changed:', event.className)
}

const handleSpecChange = (event: SpecChangeEvent): void => {
  console.log('Spec changed:', event.specName, 'Role:', event.role)
}

const handleFormError = (errors: FormErrors): void => {
  console.error('Form errors:', errors)
  if (errors.general) {
    showNotification(errors.general, 'error')
  } else {
    showNotification('Please fix the form errors.', 'error')
  }
}


const saveToLocalStorage = (): void => {
  try {
    localStorage.setItem('wow-characters', JSON.stringify(characters.value))
  } catch (error) {
    console.error('Save error:', error)
    showNotification('An error occurred while saving.', 'error')
  }
}

const loadFromLocalStorage = (): void => {
  try {
    const saved = localStorage.getItem('wow-characters')
    if (saved) {
      const parsed = JSON.parse(saved)
      if (Array.isArray(parsed)) {
        characters.value = parsed
        showNotification(`${parsed.length} character(s) loaded.`, 'info')
      }
    }
  } catch (error) {
    console.error('Load error:', error)
    showNotification('An error occurred while loading saved data.', 'warning')
  }
}

onMounted(() => {
  loadFromLocalStorage()
})
</script>

<style scoped>
.app {
  max-height: 100vh;
  font-family: 'Inter', 'Segoe UI', sans-serif;
}

.app-main {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem 2rem 2rem;
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
  padding: 1rem 1.5rem;
  border-radius: 6px;
  color: white;
  font-weight: 500;
  min-width: 300px;
  animation: slideIn 0.3s ease;
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

@media (max-width: 768px) {
  .app-main {
    padding: 0 1rem 1rem 1rem;
  }

  .notifications {
    left: 1rem;
    right: 1rem;
  }

  .notification {
    min-width: auto;
  }
}
</style>
