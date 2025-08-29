<template>
  <div class="character-card" :aria-label="`Character card for ${character.name}`">
    <div class="character-header">
      <h3 class="character-name">{{ character.name }}</h3>
      <span class="character-level" v-if="character.level">Lv. {{ character.level }}</span>
    </div>

    <div class="character-info">
      <div class="character-class">
        <strong>{{ character.class }}</strong>
        <span v-if="character.spec" class="character-spec"> — {{ character.spec }}</span>
      </div>
      <div v-if="character.role" class="character-role">
        <span class="role-badge" :data-role="character.role">{{ roleLabel }}</span>
      </div>
    </div>

    <div class="character-meta">
      <span class="character-date">Created {{ prettyDate }}</span>
    </div>

    <div class="character-actions" aria-label="Character actions">
      <button class="action-btn edit" title="Edit" @click="$emit('edit', character)">✏️</button>
      <button class="action-btn delete" title="Delete" @click="$emit('delete', character.id)">
        🗑️
      </button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import type { Character, Role } from '@/interfaces/game.interface'
import { ROLE_ICONS } from '@/interfaces/game.interface'

const props = defineProps<{ character: Character }>()

const prettyDate = computed(() =>
  new Date(props.character.createdAt).toLocaleDateString('en-GB', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  }),
)

const roleLabel = computed(() => {
  const role = props.character.role as Role | undefined
  return role ? `${ROLE_ICONS[role]} ${role}` : ''
})
</script>

<style scoped>
.character-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 1.25rem;
  transition: all 0.2s;
  position: relative;
}
.character-card:hover {
  box-shadow: 0 6px 20px rgba(2, 6, 23, 0.06);
  transform: translateY(-2px);
}
.character-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 0.85rem;
}
.character-name {
  font-size: 1.2rem;
  font-weight: 700;
  color: #0f172a;
  margin: 0;
}
.character-level {
  background: #f1f5f9;
  color: #334155;
  padding: 0.2rem 0.55rem;
  border-radius: 9999px;
  font-size: 0.825rem;
  font-weight: 700;
}
.character-info {
  margin-bottom: 0.75rem;
}
.character-class {
  font-size: 1rem;
  margin-bottom: 0.4rem;
}
.character-spec {
  color: #64748b;
}
.character-role {
  margin-bottom: 0.4rem;
}
.role-badge {
  padding: 0.25rem 0.65rem;
  border-radius: 9999px;
  font-size: 0.85rem;
  font-weight: 700;
}
.role-badge[data-role='Tanks'] {
  background: #dbeafe;
  color: #1e40af;
}
.role-badge[data-role='Healers'] {
  background: #dcfce7;
  color: #166534;
}
.role-badge[data-role='Melee'] {
  background: #fee2e2;
  color: #991b1b;
}
.role-badge[data-role='Ranged'] {
  background: #fed7aa;
  color: #9a3412;
}
.character-meta {
  font-size: 0.85rem;
  color: #64748b;
  margin-bottom: 0.75rem;
}
.character-actions {
  position: absolute;
  top: 0.75rem;
  right: 0.75rem;
  display: flex;
  gap: 0.5rem;
}
.action-btn {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 0.45rem;
  cursor: pointer;
  transition: all 0.2s;
  font-size: 1rem;
}
.action-btn:hover {
  background: #f8fafc;
  transform: scale(1.06);
}
.action-btn.delete:hover {
  background: #fef2f2;
  border-color: #fecaca;
}
</style>
