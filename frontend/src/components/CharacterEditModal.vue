<template>
  <BaseModal
    :modelValue="show"
    @update:modelValue="(val) => !val && $emit('close')"
    :title="`Edit ${character?.name}`"
    size="md"
    @close="$emit('close')"
  >
    <form @submit.prevent="handleSubmit" class="space-y-4">
      <div>
        <label class="block text-sm font-medium text-slate-200 mb-2">
          Rôle (calculé)
        </label>
        <div
          class="w-full rounded-lg border border-white/10 bg-white/5 px-4 py-2.5 text-white flex items-center justify-between"
        >
          <span class="text-sm text-slate-200">
            {{ derivedFrontendRoleLabel || 'Unknown' }}
          </span>
          <span class="text-xs text-slate-400">d'après la spécialisation</span>
        </div>
        <p class="mt-1 text-xs text-slate-400">
          Le rôle est déduit de la spécialisation sélectionnée. Choisissez "Unknown" pour laisser la synchronisation auto-détecter.
        </p>
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-200 mb-2">
          Specialization
        </label>
        <select
          v-model="formData.spec"
          class="w-full rounded-lg border border-white/10 bg-white/5 px-4 py-2.5 text-white placeholder-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none transition"
          :disabled="!availableSpecs.length"
        >
          <option value="">Select a spec...</option>
          <option value="Unknown">Unknown (Auto-detect)</option>
          <option
            v-for="spec in availableSpecs"
            :key="spec.value"
            :value="spec.value"
          >
            {{ spec.label }}
          </option>
        </select>
        <p class="mt-1 text-xs text-slate-400">
          Available specs for {{ character?.class || 'this class' }}
        </p>
      </div>

      <div>
        <label class="block text-sm font-medium text-slate-200 mb-2">
          Secondary specialization (optional)
        </label>
        <select
          v-model="formData.specSecondary"
          class="w-full rounded-lg border border-white/10 bg-white/5 px-4 py-2.5 text-white placeholder-slate-400 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20 focus:outline-none transition"
          :disabled="!availableSpecs.length"
        >
          <option value="">— None —</option>
          <option
            v-for="spec in availableSpecs"
            :key="spec.value + ':secondary'"
            :value="spec.value"
          >
            {{ spec.label }}
          </option>
        </select>
        <p class="mt-1 text-xs text-slate-400">
          Used to suggest preferred spec in assignments.
        </p>
      </div>

      <div v-if="error" class="rounded-lg bg-rose-500/10 border border-rose-500/20 px-4 py-3 text-sm text-rose-200">
        {{ error }}
      </div>
    </form>

    <template #footer>
      <div class="flex justify-end gap-3">
        <button
          type="button"
          @click="$emit('close')"
          class="rounded-lg px-4 py-2 text-sm font-medium text-slate-300 hover:text-white transition"
        >
          Cancel
        </button>
        <button
          type="submit"
          @click="handleSubmit"
          :disabled="saving"
          class="rounded-lg bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-800 disabled:cursor-not-allowed px-4 py-2 text-sm font-medium text-white transition"
        >
          {{ saving ? 'Saving...' : 'Save Changes' }}
        </button>
      </div>
    </template>
  </BaseModal>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import BaseModal from './ui/BaseModal.vue'
import { getSpecOptions } from '@/data/gameData'
import type { Character, Role } from '@/interfaces/game.interface'

const props = defineProps<{
  character: Character | null
  show: boolean
}>()

const emit = defineEmits<{
  (e: 'close'): void
  (e: 'save', updates: { role?: string; spec?: string }): void
}>()

const formData = ref({
  role: '',
  spec: '',
  specSecondary: ''
})

const saving = ref(false)
const error = ref('')

const availableSpecs = computed(() => {
  if (!props.character?.class) return []
  return getSpecOptions(props.character.class)
})

const selectedSpecRole = computed<Role | undefined>(() => {
  if (!formData.value.spec) return undefined
  const spec = availableSpecs.value.find(s => s.value === formData.value.spec)
  return spec?.role
})

const derivedFrontendRoleLabel = computed(() => selectedSpecRole.value ?? '')

const toBackendRole = (role: string | undefined): string => {
  if (!role) return 'Unknown'

  const roleMap: Record<string, string> = {
    'Tanks': 'Tank',
    'Healers': 'Healer',
    'Melee': 'DPS',
    'Ranged': 'DPS'
  }

  return roleMap[role] || role
}

const toFrontendRole = (role: string | undefined): string => {
  if (!role) return 'Unknown'

  const roleMap: Record<string, string> = {
    'Tank': 'Tank',
    'Healer': 'Healer',
    'DPS': 'DPS',
    'Unknown': 'Unknown'
  }

  return roleMap[role] || role
}

watch(() => props.character, (newChar) => {
  if (newChar) {
    formData.value = {
      role: toFrontendRole(newChar.role),
      spec: newChar.spec || '',
      specSecondary: newChar.specSecondary || ''
    }
  }
  error.value = ''
}, { immediate: true })

const handleSubmit = async () => {
  if (!props.character) return

  error.value = ''

  const updates: { role?: string; spec?: string; specSecondary?: string } = {}

  const newSpec = formData.value.spec || 'Unknown'
  if (newSpec !== (props.character.spec || '')) {
    updates.spec = newSpec
  }

  const newSpec2 = formData.value.specSecondary || ''
  if (newSpec2 !== (props.character.specSecondary || '')) {
    updates.specSecondary = newSpec2 || undefined
  }

  const derivedFrontendRole = selectedSpecRole.value as unknown as string | undefined
  const derivedBackendRole = toBackendRole(derivedFrontendRole)
  const currentBackendRole = toBackendRole(props.character.role)
  if (derivedBackendRole !== currentBackendRole) {
    updates.role = derivedBackendRole
  }

  if (Object.keys(updates).length === 0) {
    error.value = 'No changes detected'
    return
  }

  emit('save', updates)
}
</script>
