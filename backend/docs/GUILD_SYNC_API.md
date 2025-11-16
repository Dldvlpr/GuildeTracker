# Guild Roster Synchronization API

## Vue d'ensemble

API pour synchroniser les personnages d'une guilde avec les données Blizzard tout en préservant les rôles définis manuellement.

---

## 🔄 Synchroniser le Roster

### Endpoint

```http
POST /api/guilds/{guildId}/sync
```

### Description

Synchronise le roster de la guilde avec l'API Blizzard:
- ✅ Ajoute les nouveaux personnages
- ✅ Met à jour les classes et specs des personnages existants
- ✅ **Préserve les rôles définis manuellement**
- ✅ Ne supprime pas les anciens personnages (pour garder l'historique)

### Authentification

Requiert:
- Utilisateur authentifié
- Permission `GUILD_MANAGE` sur la guilde
- Token Blizzard valide

### Réponse Success

```json
{
  "success": true,
  "message": "Guild roster synchronized",
  "created": 5,
  "updated": 107,
  "removed": 0
}
```

### Erreurs Possibles

#### Token Blizzard expiré
```json
{
  "error": "blizzard_token_expired",
  "message": "Your Battle.net session has expired. Please reconnect.",
  "reconnect_url": "/api/oauth/blizzard/connect"
}
```

#### Permission refusée
```json
{
  "error": "Access Denied"
}
```

#### Sync échoué
```json
{
  "error": "sync_failed",
  "message": "Failed to sync guild roster: <error details>"
}
```

---

## 🎯 Mettre à Jour le Rôle d'un Personnage

### Endpoint

```http
PATCH /api/guilds/{guildId}/characters/{characterId}/role
```

### Description

Permet de définir manuellement le rôle d'un personnage. Ce rôle sera **préservé** lors des synchronisations futures.

### Body

```json
{
  "role": "Tank"
}
```

**Valeurs acceptées**: `Tank`, `Healer`, `DPS`, `Unknown`

### Réponse Success

```json
{
  "success": true,
  "message": "Character role updated",
  "character": {
    "id": "uuid",
    "name": "Murd",
    "class": "Warrior",
    "spec": "Armes",
    "role": "Tank"
  }
}
```

### Erreurs Possibles

#### Rôle invalide
```json
{
  "error": "Invalid role. Must be: Tank, Healer, DPS, or Unknown"
}
```

#### Personnage non trouvé
```json
{
  "error": "Character not found in this guild"
}
```

---

## 💡 Cas d'Usage

### Cas 1: Synchronisation Automatique Régulière

Un bouton "Sync" dans l'interface qui met à jour le roster:

```typescript
async function syncGuild(guildId: string) {
  const response = await fetch(`/api/guilds/${guildId}/sync`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${token}`
    }
  });

  const result = await response.json();

  if (result.success) {
    console.log(`Sync réussi: ${result.created} ajoutés, ${result.updated} mis à jour`);
  }
}
```

### Cas 2: Correction Manuelle des Rôles

Pour les personnages multi-spec où la détection automatique se trompe:

```typescript
async function setCharacterRole(guildId: string, characterId: string, role: string) {
  const response = await fetch(`/api/guilds/${guildId}/characters/${characterId}/role`, {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`
    },
    body: JSON.stringify({ role })
  });

  return response.json();
}

// Exemple: Un Druid Restauration qui tank parfois
await setCharacterRole(guildId, druidId, 'Tank');
```

### Cas 3: Workflow Complet

1. **Claim initial** de la guilde → Tous les personnages importés avec specs/rôles auto
2. **Corrections manuelles** pour les cas spéciaux (hybrid builds, off-spec, etc.)
3. **Syncs réguliers** → Nouveaux membres ajoutés, specs mises à jour, rôles manuels préservés

---

## 🔒 Comportement de Préservation des Rôles

### Logique de Mise à Jour

Lors d'une synchronisation:

| Rôle Actuel | Rôle API | Résultat | Raison |
|-------------|----------|----------|--------|
| `Unknown` | `Tank` | → `Tank` | Mise à jour auto |
| `Tank` | `DPS` | → `Tank` | **Préservé** (manuel) |
| `Healer` | `Healer` | → `Healer` | Pas de changement |
| `DPS` (manuel) | `Tank` | → `DPS` | **Préservé** (manuel) |

**Règle**: Un rôle non-`Unknown` est considéré comme **défini manuellement** et ne sera jamais écrasé.

### Réinitialiser un Rôle

Pour permettre la détection auto à nouveau:

```typescript
// Remettre à Unknown pour réactiver la détection auto
await setCharacterRole(guildId, characterId, 'Unknown');

// Puis sync
await syncGuild(guildId);
```

---

## 📊 Données Mises à Jour lors du Sync

### Toujours mis à jour
- ✅ Classe (`class`)
- ✅ Spécialisation (`classSpec`)

### Conditionnellement mis à jour
- ⚠️ Rôle (`role`) → Seulement si actuellement `Unknown`

### Jamais touché
- 🔒 Lien utilisateur (`userPlayer`)
- 🔒 Guilde (`guild`)
- 🔒 ID du personnage

---

## 🚀 Exemple Complet - Interface de Gestion

```typescript
interface Character {
  id: string;
  name: string;
  class: string;
  spec: string;
  role: string;
  classColor: string;
  classRoles: string[];
}

function GuildRosterManager({ guildId }: { guildId: string }) {
  const [syncing, setSyncing] = useState(false);
  const [characters, setCharacters] = useState<Character[]>([]);

  const handleSync = async () => {
    setSyncing(true);
    try {
      const result = await fetch(`/api/guilds/${guildId}/sync`, {
        method: 'POST',
        headers: { 'Authorization': `Bearer ${token}` }
      }).then(r => r.json());

      if (result.success) {
        // Recharger les personnages
        loadCharacters();
        toast.success(`${result.created} ajoutés, ${result.updated} mis à jour`);
      }
    } finally {
      setSyncing(false);
    }
  };

  const handleRoleChange = async (characterId: string, newRole: string) => {
    await fetch(`/api/guilds/${guildId}/characters/${characterId}/role`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        'Authorization': `Bearer ${token}`
      },
      body: JSON.stringify({ role: newRole })
    });

    loadCharacters();
  };

  return (
    <div>
      <button onClick={handleSync} disabled={syncing}>
        {syncing ? 'Synchronisation...' : 'Sync Roster'}
      </button>

      <table>
        <thead>
          <tr>
            <th>Nom</th>
            <th>Classe</th>
            <th>Spec</th>
            <th>Rôle</th>
          </tr>
        </thead>
        <tbody>
          {characters.map(char => (
            <tr key={char.id}>
              <td>{char.name}</td>
              <td style={{ color: char.classColor }}>{char.class}</td>
              <td>{char.spec}</td>
              <td>
                <select
                  value={char.role}
                  onChange={(e) => handleRoleChange(char.id, e.target.value)}
                >
                  <option value="Tank">Tank</option>
                  <option value="Healer">Healer</option>
                  <option value="DPS">DPS</option>
                  <option value="Unknown">Auto</option>
                </select>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
```

---

## ⚙️ Configuration

Pour désactiver la préservation des rôles (tout écraser):

Modifier `GameGuildController.php` ligne 194:

```php
// Avant (préserve les rôles manuels)
preserveManualRoles: true

// Après (écrase tout)
preserveManualRoles: false
```

---

## 📝 Notes Techniques

### Performance
- **Avec specs** (`fetchSpecs: true`): ~1-2 minutes pour 100 personnages
- **Sans specs** (`fetchSpecs: false`): ~5-10 secondes pour 100 personnages

### Rate Limiting Blizzard
- Limite: 100 requêtes/seconde
- Le sync respecte automatiquement cette limite

### Logs
Tous les syncs sont loggés dans `var/debug.log`:
```
[2025-11-14 20:18:37] syncRoster: got guild data with 112 members
[2025-11-14 20:18:37] syncRoster: created 5, updated 107
```

---

## 🔗 Voir Aussi

- [WOW_CLASS_METADATA.md](./WOW_CLASS_METADATA.md) - Métadonnées de classes
- [USAGE_EXAMPLES.md](./USAGE_EXAMPLES.md) - Exemples d'utilisation de l'API
- [REALM_TYPE_DETECTION.md](./REALM_TYPE_DETECTION.md) - Système de détection des serveurs
