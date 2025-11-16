# WoW Class Metadata System

## Vue d'ensemble

Le système de métadonnées de classes WoW fournit des informations riches sur chaque classe (couleurs officielles, rôles, types d'armure, etc.) sans nécessiter de base de données supplémentaire.

## Architecture

### Service: `WowClassMapper`

Localisation: `src/Service/WowClassMapper.php`

Le service centralise toutes les données des classes et races WoW avec leurs métadonnées complètes.

#### Données disponibles par classe

- **name**: Nom de la classe (ex: "Warrior", "Mage")
- **color**: Couleur officielle Blizzard en hexadécimal (ex: "#C79C6E")
- **roles**: Rôles disponibles (Tank, Healer, DPS)
- **armor**: Type d'armure (Plate, Mail, Leather, Cloth)
- **resource**: Type de ressource (Rage, Mana, Energy, etc.)

#### Exemple de données

```php
1 => [
    'name' => 'Warrior',
    'color' => '#C79C6E',
    'roles' => ['Tank', 'DPS'],
    'armor' => 'Plate',
    'resource' => 'Rage',
]
```

## API Endpoints

### 1. Liste de toutes les classes avec métadonnées

```http
GET /api/wow/classes
```

**Réponse:**
```json
[
  {
    "id": 1,
    "name": "Warrior",
    "color": "#C79C6E",
    "roles": ["Tank", "DPS"],
    "armor": "Plate",
    "resource": "Rage"
  },
  {
    "id": 8,
    "name": "Mage",
    "color": "#69CCF0",
    "roles": ["DPS"],
    "armor": "Cloth",
    "resource": "Mana"
  }
  // ...
]
```

### 2. Détails d'une classe spécifique

```http
GET /api/wow/classes/{id}
```

**Exemple:**
```http
GET /api/wow/classes/1
```

**Réponse:**
```json
{
  "id": 1,
  "name": "Warrior",
  "color": "#C79C6E",
  "roles": ["Tank", "DPS"],
  "armor": "Plate",
  "resource": "Rage"
}
```

### 3. Liste de toutes les races

```http
GET /api/wow/races
```

**Réponse:**
```json
[
  {"id": 1, "name": "Human"},
  {"id": 2, "name": "Orc"},
  {"id": 3, "name": "Dwarf"}
  // ...
]
```

## Utilisation dans les DTOs

Le `CharacterDTO` expose automatiquement les métadonnées de classe:

### Avant (sans métadonnées)
```json
{
  "id": "uuid",
  "name": "Thrall",
  "class": "Shaman",
  "spec": "Unknown",
  "role": "Unknown"
}
```

### Après (avec métadonnées)
```json
{
  "id": "uuid",
  "name": "Thrall",
  "class": "Shaman",
  "classColor": "#0070DE",
  "classRoles": ["Healer", "DPS"],
  "spec": "Unknown",
  "role": "Unknown"
}
```

## Utilisation Frontend

### Exemple: Afficher le nom d'un personnage avec la couleur de sa classe

```typescript
// Récupérer les personnages d'une guilde
const response = await fetch('/api/guilds/uuid/characters');
const characters = await response.json();

characters.forEach(char => {
  console.log(`
    <span style="color: ${char.classColor}">
      ${char.name} - ${char.class}
    </span>
  `);
});
```

### Exemple: Filtrer par rôle

```typescript
// Récupérer toutes les classes
const classes = await fetch('/api/wow/classes').then(r => r.json());

// Trouver toutes les classes qui peuvent tank
const tankClasses = classes.filter(c => c.roles.includes('Tank'));
// Résultat: Warrior, Paladin, Death Knight, Monk, Druid, Demon Hunter

// Trouver toutes les classes qui peuvent heal
const healerClasses = classes.filter(c => c.roles.includes('Healer'));
// Résultat: Paladin, Priest, Shaman, Monk, Druid, Evoker
```

## Méthodes du Service

### Méthodes de base

```php
// Obtenir le nom d'une classe par ID
$mapper->getClassName(1); // "Warrior"

// Obtenir la couleur d'une classe
$mapper->getClassColor(8); // "#69CCF0"

// Obtenir les rôles d'une classe
$mapper->getClassRoles(2); // ["Tank", "Healer", "DPS"]

// Obtenir toutes les métadonnées d'une classe
$mapper->getClassData(1);
// [
//   'name' => 'Warrior',
//   'color' => '#C79C6E',
//   'roles' => ['Tank', 'DPS'],
//   'armor' => 'Plate',
//   'resource' => 'Rage'
// ]
```

### Méthodes utilitaires

```php
// Recherche inverse: nom -> ID
$mapper->getClassIdByName('warrior'); // 1 (case-insensitive)

// Vérifier si un ID est valide
$mapper->isValidClassId(99); // false

// Obtenir toutes les classes (simple map)
$mapper->getAllClasses();
// [1 => 'Warrior', 2 => 'Paladin', ...]

// Obtenir toutes les classes avec métadonnées
$mapper->getAllClassesWithMetadata();
// [1 => ['name' => 'Warrior', 'color' => '#C79C6E', ...], ...]
```

## Classes WoW et leurs Couleurs

| ID | Classe | Couleur | Rôles | Armure | Ressource |
|----|--------|---------|-------|--------|-----------|
| 1  | Warrior | ![#C79C6E](https://via.placeholder.com/15/C79C6E/000000?text=+) `#C79C6E` | Tank, DPS | Plate | Rage |
| 2  | Paladin | ![#F58CBA](https://via.placeholder.com/15/F58CBA/000000?text=+) `#F58CBA` | Tank, Healer, DPS | Plate | Mana |
| 3  | Hunter | ![#ABD473](https://via.placeholder.com/15/ABD473/000000?text=+) `#ABD473` | DPS | Mail | Focus |
| 4  | Rogue | ![#FFF569](https://via.placeholder.com/15/FFF569/000000?text=+) `#FFF569` | DPS | Leather | Energy |
| 5  | Priest | ![#FFFFFF](https://via.placeholder.com/15/FFFFFF/000000?text=+) `#FFFFFF` | Healer, DPS | Cloth | Mana |
| 6  | Death Knight | ![#C41F3B](https://via.placeholder.com/15/C41F3B/000000?text=+) `#C41F3B` | Tank, DPS | Plate | Runic Power |
| 7  | Shaman | ![#0070DE](https://via.placeholder.com/15/0070DE/000000?text=+) `#0070DE` | Healer, DPS | Mail | Mana |
| 8  | Mage | ![#69CCF0](https://via.placeholder.com/15/69CCF0/000000?text=+) `#69CCF0` | DPS | Cloth | Mana |
| 9  | Warlock | ![#9482C9](https://via.placeholder.com/15/9482C9/000000?text=+) `#9482C9` | DPS | Cloth | Mana |
| 10 | Monk | ![#00FF96](https://via.placeholder.com/15/00FF96/000000?text=+) `#00FF96` | Tank, Healer, DPS | Leather | Energy |
| 11 | Druid | ![#FF7D0A](https://via.placeholder.com/15/FF7D0A/000000?text=+) `#FF7D0A` | Tank, Healer, DPS | Leather | Mana |
| 12 | Demon Hunter | ![#A330C9](https://via.placeholder.com/15/A330C9/000000?text=+) `#A330C9` | Tank, DPS | Leather | Fury |
| 13 | Evoker | ![#33937F](https://via.placeholder.com/15/33937F/000000?text=+) `#33937F` | Healer, DPS | Mail | Essence |

## Disponibilité par Version de WoW

| Classe | Classic Era | Anniversary | SoD | Hardcore | Retail |
|--------|:-----------:|:-----------:|:---:|:--------:|:------:|
| Warrior | ✅ | ✅ | ✅ | ✅ | ✅ |
| Paladin | ✅ | ✅ | ✅ | ✅ | ✅ |
| Hunter | ✅ | ✅ | ✅ | ✅ | ✅ |
| Rogue | ✅ | ✅ | ✅ | ✅ | ✅ |
| Priest | ✅ | ✅ | ✅ | ✅ | ✅ |
| Death Knight | ❌ | ❌ | ❌ | ❌ | ✅ |
| Shaman | ✅ | ✅ | ✅ | ✅ | ✅ |
| Mage | ✅ | ✅ | ✅ | ✅ | ✅ |
| Warlock | ✅ | ✅ | ✅ | ✅ | ✅ |
| Monk | ❌ | ❌ | ❌ | ❌ | ✅ |
| Druid | ✅ | ✅ | ✅ | ✅ | ✅ |
| Demon Hunter | ❌ | ❌ | ❌ | ❌ | ✅ |
| Evoker | ❌ | ❌ | ❌ | ❌ | ✅ |

## Maintenance

### Ajouter une nouvelle classe (futur)

Si Blizzard ajoute une nouvelle classe:

1. Éditer `src/Service/WowClassMapper.php`
2. Ajouter l'entrée dans `CLASS_DATA` et `CLASS_MAP`:

```php
14 => [
    'name' => 'Nouvelle Classe',
    'color' => '#HEXCODE',
    'roles' => ['Tank', 'Healer', 'DPS'],
    'armor' => 'Plate',
    'resource' => 'Energy',
],
```

3. Mettre à jour la documentation
4. Redéployer (pas de migration nécessaire)

### Mettre à jour les couleurs

Les couleurs sont les couleurs officielles Blizzard. Si elles changent (rare):

1. Éditer le champ `color` dans `CLASS_DATA`
2. Vider le cache: `php bin/console cache:clear`

## Tests

### Tester les endpoints

```bash
# Lister toutes les classes
curl http://localhost:8000/api/wow/classes | jq

# Obtenir une classe spécifique
curl http://localhost:8000/api/wow/classes/1 | jq

# Lister toutes les races
curl http://localhost:8000/api/wow/races | jq
```

### Tester avec un personnage

```bash
# Obtenir les personnages d'une guilde (avec métadonnées)
curl http://localhost:8000/api/guilds/{guild-uuid}/characters | jq
```

Vérifier que la réponse contient `classColor` et `classRoles`.

## Voir Aussi

- [REALM_TYPE_DETECTION.md](./REALM_TYPE_DETECTION.md) - Système de détection des types de serveurs
- [USAGE_EXAMPLES.md](./USAGE_EXAMPLES.md) - Exemples d'utilisation de l'API
- [GETTING_STARTED_REALMS.md](./GETTING_STARTED_REALMS.md) - Guide de démarrage
