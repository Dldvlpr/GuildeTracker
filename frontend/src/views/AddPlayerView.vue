<template>
  <div class="app">
    <header class="app-header">
      <h1>Character manager</h1>
    </header>

    <nav class="app-nav">
      <button
        @click="currentView = 'form'"
        class="nav-button"
        :class="{ active: currentView === 'form' }"
      >
        ➕ Add player
      </button>
      <button
        @click="currentView = 'list'"
        class="nav-button"
        :class="{ active: currentView === 'list' }"
      >
        📋 List ({{ characters.length }})
      </button>
    </nav>

    <main class="app-main">
      <section v-if="currentView === 'form'" class="view-section">
        <CharacterForm
          form-title="Add character"
          :enable-auto-validation="true"
          @submit="handleCharacterSubmit"
          @class-change="handleClassChange"
          @spec-change="handleSpecChange"
          @error="handleFormError"
        />
      </section>

      <section v-else-if="currentView === 'list'" class="view-section">
        <div class="list-header">
          <h2>Mes personnages</h2>
          <div class="list-stats">
            <span class="stat-item">
              Total: {{ characters.length }}
            </span>
            <span class="stat-item">
              Tanks: {{ getCharactersByRole('Tanks').length }}
            </span>
            <span class="stat-item">
              Healers: {{ getCharactersByRole('Healers').length }}
            </span>
            <span class="stat-item">
              DPS: {{ getDpsCount() }}
            </span>
          </div>
        </div>

        <div class="filters">
          <div class="filter-group">
            <label for="filter-class">Classe :</label>
            <select id="filter-class" v-model="filterClass" class="filter-select">
              <option value="">Toutes</option>
              <option v-for="cls in availableClasses" :key="cls" :value="cls">
                {{ cls }}
              </option>
            </select>
          </div>

          <div class="filter-group">
            <label for="filter-role">Rôle :</label>
            <select id="filter-role" v-model="filterRole" class="filter-select">
              <option value="">Tous</option>
              <option value="Tanks">🛡️ Tanks</option>
              <option value="Healers">💚 Healers</option>
              <option value="Melee">⚔️ Melee</option>
              <option value="Ranged">🏹 Ranged</option>
            </select>
          </div>

          <button @click="clearFilters" class="btn btn-outline btn-sm">
            Effacer filtres
          </button>
        </div>

        <div v-if="filteredCharacters.length === 0" class="empty-state">
          <div class="empty-content">
            <span class="empty-icon">👤</span>
            <h3>Aucun personnage</h3>
            <p v-if="characters.length === 0">
              Vous n'avez pas encore créé de personnage.
            </p>
            <p v-else>
              Aucun personnage ne correspond aux filtres sélectionnés.
            </p>
            <button
              @click="currentView = 'form'"
              class="btn btn-primary"
            >
              Créer mon premier personnage
            </button>
          </div>
        </div>

        <div v-else class="characters-grid">
          <div
            v-for="character in filteredCharacters"
            :key="character.id"
            class="character-card"
          >
            <div class="character-header">
              <h3 class="character-name">{{ character.name }}</h3>
              <span class="character-level" v-if="character.level">
                Niv. {{ character.level }}
              </span>
            </div>

            <div class="character-info">
              <div class="character-class">
                <strong>{{ character.class }}</strong>
                <span v-if="character.spec" class="character-spec">
                  - {{ character.spec }}
                </span>
              </div>

              <div v-if="character.role" class="character-role">
                <span
                  class="role-badge"
                  :data-role="character.role"
                >
                  {{ getRoleDisplay(character.role) }}
                </span>
              </div>
            </div>

            <div class="character-meta">
              <span class="character-date">
                Créé le {{ formatDate(character.createdAt) }}
              </span>
            </div>

            <div class="character-actions">
              <button
                @click="editCharacter(character)"
                class="action-btn edit"
                title="Modifier"
              >
                ✏️
              </button>
              <button
                @click="deleteCharacter(character.id)"
                class="action-btn delete"
                title="Supprimer"
              >
                🗑️
              </button>
            </div>
          </div>
        </div>
      </section>
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
import { ref, computed, onMounted } from 'vue';
import CharacterForm from '@/components/CharacterForm.vue';
import type {
  Character,
  Role,
  FormSubmitEvent,
  ClassChangeEvent,
  SpecChangeEvent,
  FormErrors,
  CharacterStatus
} from '../interfaces/game.interface';
import { ROLE_ICONS } from '../interfaces/game.interface';

interface Notification {
  id: string;
  message: string;
  type: 'success' | 'error' | 'warning' | 'info';
}

const currentView = ref<'form' | 'list'>('form');
const characters = ref<Character[]>([]);
const notifications = ref<Notification[]>([]);

const filterClass = ref<string>('');
const filterRole = ref<string>('');

const availableClasses = computed((): string[] => {
  const classes = new Set(characters.value.map(char => char.class));
  return Array.from(classes).sort();
});

const filteredCharacters = computed((): Character[] => {
  let filtered = [...characters.value];

  if (filterClass.value) {
    filtered = filtered.filter(char => char.class === filterClass.value);
  }

  if (filterRole.value) {
    filtered = filtered.filter(char => char.role === filterRole.value);
  }

  return filtered.sort((a, b) => a.name.localeCompare(b.name));
});

const getCharactersByRole = (role: string): Character[] => {
  return characters.value.filter(char => char.role === role);
};

const getDpsCount = (): number => {
  return getCharactersByRole('Melee').length + getCharactersByRole('Ranged').length;
};

const getRoleDisplay = (role: Role): string => {
  return `${ROLE_ICONS[role]} ${role}`;
};

const formatDate = (dateString: string): string => {
  const date = new Date(dateString);
  return date.toLocaleDateString('fr-FR', {
    year: 'numeric',
    month: 'long',
    day: 'numeric'
  });
};

const generateId = (): string => {
  return Date.now().toString(36) + Math.random().toString(36).substr(2);
};

const showNotification = (message: string, type: Notification['type'] = 'info'): void => {
  const notification: Notification = {
    id: generateId(),
    message,
    type
  };

  notifications.value.push(notification);

  setTimeout(() => {
    const index = notifications.value.findIndex(n => n.id === notification.id);
    if (index >= 0) {
      notifications.value.splice(index, 1);
    }
  }, 3000);
};

const handleCharacterSubmit = (event: FormSubmitEvent): void => {
  try {
    const existingNames = characters.value.map(char => char.name.toLowerCase());
    if (existingNames.includes(event.character.name.toLowerCase())) {
      showNotification('Un personnage avec ce nom existe déjà', 'error');
      return;
    }

    const newCharacter: Character = {
      id: generateId(),
      createdAt: new Date().toISOString(),
      status: 'active' as CharacterStatus,
      ...event.character
    };

    characters.value.push(newCharacter);

    saveToLocalStorage();

    showNotification(`Personnage "${newCharacter.name}" créé avec succès!`, 'success');

    currentView.value = 'list';

  } catch (error) {
    console.error('Erreur lors de la création du personnage:', error);
    showNotification('Erreur lors de la création du personnage', 'error');
  }
};

const editCharacter = (character: Character): void => {
  // TODO: Implémenter l'édition
  showNotification('Fonction d\'édition à implémenter', 'info');
};

const deleteCharacter = (id: string): void => {
  const character = characters.value.find(char => char.id === id);
  if (!character) return;

  if (confirm(`Êtes-vous sûr de vouloir supprimer "${character.name}" ?`)) {
    const index = characters.value.findIndex(char => char.id === id);
    if (index >= 0) {
      characters.value.splice(index, 1);
      saveToLocalStorage();
      showNotification(`Personnage "${character.name}" supprimé`, 'success');
    }
  }
};

const clearFilters = (): void => {
  filterClass.value = '';
  filterRole.value = '';
};

const handleClassChange = (event: ClassChangeEvent): void => {
  console.log('Classe changée:', event.className);
};

const handleSpecChange = (event: SpecChangeEvent): void => {
  console.log('Spécialisation changée:', event.specName, 'Rôle:', event.role);
};

const handleFormError = (errors: FormErrors): void => {
  console.error('Erreurs du formulaire:', errors);

  if (errors.general) {
    showNotification(errors.general, 'error');
  } else {
    showNotification('Veuillez corriger les erreurs du formulaire', 'error');
  }
};

const saveToLocalStorage = (): void => {
  try {
    localStorage.setItem('wow-characters', JSON.stringify(characters.value));
  } catch (error) {
    console.error('Erreur lors de la sauvegarde:', error);
    showNotification('Erreur lors de la sauvegarde', 'error');
  }
};

const loadFromLocalStorage = (): void => {
  try {
    const saved = localStorage.getItem('wow-characters');
    if (saved) {
      const parsed = JSON.parse(saved);
      if (Array.isArray(parsed)) {
        characters.value = parsed;
        showNotification(`${parsed.length} personnage(s) chargé(s)`, 'info');
      }
    }
  } catch (error) {
    console.error('Erreur lors du chargement:', error);
    showNotification('Erreur lors du chargement des données', 'warning');
  }
};

/**
 * CYCLE DE VIE
 */
onMounted(() => {
  loadFromLocalStorage();
});
</script>

<style scoped>
.app {
  min-height: 100vh;
  font-family: 'Inter', 'Segoe UI', sans-serif;
}

.app-header {
  text-align: center;
  padding: 2rem;
  color: white;
}

.app-header h1 {
  font-size: 2.5rem;
  font-weight: 700;
  margin: 0 0 0.5rem 0;
  text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
}

.app-header p {
  font-size: 1.25rem;
  opacity: 0.9;
  margin: 0;
}

.app-nav {
  display: flex;
  justify-content: center;
  gap: 1rem;
  padding: 0 2rem 2rem 2rem;
}

.nav-button {
  padding: 0.75rem 1.5rem;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-radius: 25px;
  background: rgba(255, 255, 255, 0.1);
  color: white;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.3s ease;
  backdrop-filter: blur(10px);
}

.nav-button:hover {
  background: rgba(255, 255, 255, 0.2);
  transform: translateY(-2px);
}

.nav-button.active {
  background: rgba(255, 255, 255, 0.9);
  color: #667eea;
  border-color: white;
}

.app-main {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem 2rem 2rem;
}

.view-section {
  background: white;
  border-radius: 12px;
  min-height: 400px;
}

.list-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 2rem 2rem 1rem 2rem;
  border-bottom: 1px solid #e2e8f0;
}

.list-header h2 {
  font-size: 1.75rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
}

.list-stats {
  display: flex;
  gap: 1rem;
}

.stat-item {
  padding: 0.5rem 1rem;
  background: #f1f5f9;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 600;
  color: #475569;
}

.filters {
  display: flex;
  gap: 1rem;
  align-items: end;
  padding: 1rem 2rem;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.filter-group {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.filter-group label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
}

.filter-select {
  padding: 0.5rem;
  border: 1px solid #d1d5db;
  border-radius: 4px;
  background: white;
  min-width: 120px;
}

.empty-state {
  display: flex;
  justify-content: center;
  align-items: center;
  min-height: 300px;
  padding: 2rem;
}

.empty-content {
  text-align: center;
  max-width: 400px;
}

.empty-icon {
  font-size: 4rem;
  display: block;
  margin-bottom: 1rem;
}

.empty-content h3 {
  font-size: 1.5rem;
  font-weight: 600;
  color: #374151;
  margin: 0 0 0.5rem 0;
}

.empty-content p {
  color: #6b7280;
  margin: 0 0 1.5rem 0;
}

.characters-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 1.5rem;
  padding: 2rem;
}

.character-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 1.5rem;
  transition: all 0.2s ease;
  position: relative;
}

.character-card:hover {
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
  transform: translateY(-2px);
}

.character-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-bottom: 1rem;
}

.character-name {
  font-size: 1.25rem;
  font-weight: 600;
  color: #1e293b;
  margin: 0;
}

.character-level {
  background: #f1f5f9;
  color: #475569;
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  font-size: 0.875rem;
  font-weight: 600;
}

.character-info {
  margin-bottom: 1rem;
}

.character-class {
  font-size: 1rem;
  margin-bottom: 0.5rem;
}

.character-spec {
  color: #6b7280;
  font-weight: normal;
}

.character-role {
  margin-bottom: 0.5rem;
}

.role-badge {
  padding: 0.25rem 0.75rem;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 600;
}

.role-badge[data-role="Tanks"] {
  background: #dbeafe;
  color: #1e40af;
}

.role-badge[data-role="Healers"] {
  background: #dcfce7;
  color: #166534;
}

.role-badge[data-role="Melee"] {
  background: #fee2e2;
  color: #991b1b;
}

.role-badge[data-role="Ranged"] {
  background: #fed7aa;
  color: #9a3412;
}

.character-meta {
  font-size: 0.875rem;
  color: #6b7280;
  margin-bottom: 1rem;
}

.character-actions {
  position: absolute;
  top: 1rem;
  right: 1rem;
  display: flex;
  gap: 0.5rem;
  opacity: 0;
  transition: opacity 0.2s ease;
}

.character-card:hover .character-actions {
  opacity: 1;
}

.action-btn {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 4px;
  padding: 0.5rem;
  cursor: pointer;
  transition: all 0.2s ease;
  font-size: 1rem;
}

.action-btn:hover {
  background: #f8fafc;
  transform: scale(1.1);
}

.action-btn.delete:hover {
  background: #fef2f2;
  border-color: #fecaca;
}

.btn {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: 6px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-primary {
  background: #3b82f6;
  color: white;
}

.btn-primary:hover {
  background: #2563eb;
  transform: translateY(-1px);
}

.btn-outline {
  background: transparent;
  color: #6b7280;
  border: 1px solid #d1d5db;
}

.btn-outline:hover {
  background: #f9fafb;
}

.btn-sm {
  padding: 0.5rem 1rem;
  font-size: 0.875rem;
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
  .app-header {
    padding: 1rem;
  }

  .app-header h1 {
    font-size: 2rem;
  }

  .app-nav {
    flex-direction: column;
    align-items: center;
  }

  .list-header {
    flex-direction: column;
    align-items: flex-start;
    gap: 1rem;
  }

  .list-stats {
    flex-wrap: wrap;
  }

  .filters {
    flex-direction: column;
    align-items: stretch;
  }

  .characters-grid {
    grid-template-columns: 1fr;
    padding: 1rem;
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
