# Refactorisation de BlizzardController

## Changements effectués

Le `BlizzardController` a été refactorisé pour utiliser la nouvelle structure `BlizzardGameRealm` au lieu des champs legacy `realm` (string) et `blizzardId` (string).

## Modifications principales

### 1. Ajout des imports

```php
use App\Entity\BlizzardGameRealm;
use App\Enum\WowGameType;
use App\Repository\BlizzardGameRealmRepository;
```

### 2. Injection du nouveau repository

```php
public function __construct(
    // ... autres dépendances
    private readonly BlizzardGameRealmRepository $blizzardGameRealmRepository,
) {}
```

### 3. Nouvelle méthode helper: `findOrCreateBlizzardRealm()`

Cette méthode:
- Normalise le slug du realm (lowercase, tirets)
- Détecte le type de jeu via `WowGameType::fromString()`
- Recherche le `BlizzardGameRealm` correspondant
- Log une erreur si le realm n'est pas trouvé (→ besoin de sync)

```php
private function findOrCreateBlizzardRealm(string $realmSlug, string $wowType): ?BlizzardGameRealm
{
    $region = (string) $this->params->get('blizzard.region');
    $slug = strtolower(str_replace(' ', '-', $realmSlug));
    $gameType = WowGameType::fromString($wowType);

    $realm = $this->blizzardGameRealmRepository->findOneBy([
        'slug' => $slug,
        'gameType' => $gameType,
        'region' => $region,
    ]);

    if (!$realm) {
        error_log(sprintf(
            'BlizzardGameRealm not found for slug=%s, gameType=%s, region=%s. Run app:sync-blizzard-realms',
            $slug,
            $gameType->value,
            $region
        ));
    }

    return $realm;
}
```

### 4. Nouvelle méthode helper: `formatGuildResponse()`

Format standardisé pour les réponses JSON incluant les informations de realm:

```php
private function formatGuildResponse(GameGuild $guild): array
{
    $realm = $guild->getBlizzardRealm();

    return [
        'id' => $guild->getUuidToString(),
        'name' => $guild->getName(),
        'faction' => $guild->getFaction(),
        'realm' => $realm ? [
            'id' => $realm->getId(),
            'name' => $realm->getName(),
            'slug' => $realm->getSlug(),
            'game_type' => $realm->getGameType()->value,
            'game_type_label' => $realm->getGameType()->getLabel(),
            'region' => $realm->getRegion(),
        ] : null,
        // Legacy fields for backward compatibility
        'realm_name' => $realm?->getName(),
        'game_type' => $realm?->getGameType()->getLabel(),
    ];
}
```

### 5. Refactorisation de `claimGuild()`

**Avant:**
```php
// Recherche legacy
$existingGuild = $this->gameGuildRepository->findOneBy([
    'blizzardId' => (string) $guildId,
    'realm' => $realm
]);

// Création legacy
$gameGuild->setRealm($realm);
$gameGuild->setBlizzardId((string) $guildId);
```

**Après:**
```php
// 1. Trouve le BlizzardGameRealm
$blizzardRealm = $this->findOrCreateBlizzardRealm($realm, $wowType);

if (!$blizzardRealm) {
    return $this->json(['error' => 'realm_not_found'], 404);
}

// 2. Recherche par BlizzardGameRealm
$existingGuild = $this->gameGuildRepository->findOneBy([
    'name' => $guildName,
    'blizzardRealm' => $blizzardRealm,
]);

// 3. Fallback: recherche legacy + migration auto
if (!$existingGuild) {
    $existingGuild = $this->gameGuildRepository->findOneBy([
        'blizzardId' => (string) $guildId,
        'realm' => $realm
    ]);

    if ($existingGuild) {
        // Migration automatique!
        $existingGuild->setBlizzardRealm($blizzardRealm);
        $this->em->persist($existingGuild);
    }
}

// 4. Création avec nouvelle structure
$gameGuild->setBlizzardRealm($blizzardRealm);
// Keep legacy fields for backward compatibility
$gameGuild->setRealm($realm);
$gameGuild->setBlizzardId((string) $guildId);

// 5. Réponse formatée
return $this->json([
    'guild' => $this->formatGuildResponse($gameGuild)
]);
```

### 6. Refactorisation de `joinGuild()`

**Avant:**
```php
// Vérification par blizzard_id
if ($guild->getBlizzardId() !== $charGuildId || ...) {
    return $this->json(['error' => 'guild_mismatch']);
}
```

**Après:**
```php
// Vérification par nom uniquement (plus flexible)
$charGuildName = $characterProfile['guild']['name'] ?? '';

if (strcasecmp($guild->getName() ?? '', $charGuildName) !== 0) {
    return $this->json(['error' => 'guild_mismatch']);
}
```

## Avantages de la refactorisation

### ✅ Migration automatique
Les guildes avec ancienne structure sont automatiquement migrées lors du claim:
```php
if ($existingGuild) {
    $existingGuild->setBlizzardRealm($blizzardRealm);
    $this->em->persist($existingGuild);
}
```

### ✅ Compatibilité descendante
Les champs legacy sont conservés pour ne pas casser le frontend:
```php
$gameGuild->setRealm($realm);          // Legacy
$gameGuild->setBlizzardId((string) $guildId);  // Legacy
```

### ✅ Réponses enrichies
Les réponses JSON incluent maintenant toutes les infos du realm:
```json
{
  "guild": {
    "id": "...",
    "name": "Tracker",
    "realm": {
      "id": 431,
      "name": "Spineshatter",
      "slug": "spineshatter",
      "game_type": "classic-anniversary",
      "game_type_label": "Classic Anniversary",
      "region": "eu"
    },
    "realm_name": "Spineshatter",
    "game_type": "Classic Anniversary"
  }
}
```

### ✅ Détection automatique du type de jeu
Plus besoin de gérer manuellement les namespaces:
```php
$gameType = WowGameType::fromString($wowType);  // "Classic Anniversary" → CLASSIC_ANNIVERSARY
$realm = $blizzardGameRealmRepository->findOneBy(['gameType' => $gameType]);
```

### ✅ Erreurs explicites
Si un realm n'est pas trouvé:
```json
{
  "error": "realm_not_found",
  "message": "Realm 'Spineshatter' not found for Classic Anniversary. Please contact support or try syncing realms."
}
```

## Impact sur le frontend

### ⚠️ Réponses API modifiées

**Ancien format:**
```json
{
  "guild": {
    "id": "...",
    "name": "Tracker",
    "realm": "spineshatter",
    "blizzard_id": "3554045"
  }
}
```

**Nouveau format:**
```json
{
  "guild": {
    "id": "...",
    "name": "Tracker",
    "realm": {
      "id": 431,
      "name": "Spineshatter",
      "slug": "spineshatter",
      "game_type": "classic-anniversary",
      "game_type_label": "Classic Anniversary",
      "region": "eu"
    },
    "realm_name": "Spineshatter",
    "game_type": "Classic Anniversary"
  }
}
```

### Champs de compatibilité

Pour éviter de casser le frontend actuel, les champs suivants sont toujours présents:
- `realm_name`: Nom du realm (string)
- `game_type`: Label du type de jeu (string)

**Le frontend peut donc continuer à utiliser:**
```typescript
const guildName = guild.name;
const realmName = guild.realm_name;  // ← Compat
const gameType = guild.game_type;     // ← Compat
```

**Ou migrer vers la nouvelle structure:**
```typescript
const guildName = guild.name;
const realmName = guild.realm?.name;
const gameType = guild.realm?.game_type_label;
const realmSlug = guild.realm?.slug;
const region = guild.realm?.region;
```

## Étapes suivantes

### 1. Tester le claim de guilde
```bash
# En tant qu'utilisateur, essayer de claim une guilde
# Vérifier que:
# - Le realm est trouvé
# - La guilde est créée avec blizzardRealm
# - Les champs legacy sont remplis
# - La réponse JSON est correcte
```

### 2. Vérifier les guildes migrées automatiquement
```sql
SELECT
    g.name,
    g.realm AS legacy_realm,
    r.name AS new_realm,
    r.game_type
FROM game_guild g
LEFT JOIN blizzard_game_realm r ON g.blizzard_realm_id = r.id
WHERE g.realm IS NOT NULL;
```

### 3. Adapter le frontend (optionnel)
Utiliser la nouvelle structure `guild.realm` pour afficher plus d'infos.

### 4. Une fois tout testé: supprimer les champs deprecated
Voir [REMOVING_DEPRECATED_FIELDS.md](./REMOVING_DEPRECATED_FIELDS.md) (à créer)

## Dépannage

### Erreur "realm_not_found"

**Cause:** Le realm n'est pas dans la table `blizzard_game_realm`.

**Solution:**
```bash
php bin/console app:sync-blizzard-realms --region=eu
```

### Guildes non migrées

**Symptôme:** `blizzard_realm_id` est NULL pour certaines guildes.

**Solution:**
```bash
php bin/console app:migrate-guild-realms --auto-detect --default-game-type=classic-anniversary
```

### Frontend affiche "undefined"

**Cause:** Le frontend utilise `guild.realm` (objet) au lieu de `guild.realm_name` (string).

**Solution temporaire:** Utiliser les champs de compatibilité:
```typescript
const realmName = guild.realm_name || guild.realm?.name;
```

## Résumé

- ✅ BlizzardController refactorisé
- ✅ Migration automatique des guildes lors du claim
- ✅ Compatibilité descendante maintenue
- ✅ Réponses API enrichies
- ✅ Code compile sans erreur
- ⏳ Tests manuels à effectuer
- ⏳ Frontend à adapter (optionnel)
- ⏳ Suppression des champs deprecated (future)

---

*Refactorisation effectuée le: 2025-11-04*
*Version Symfony: 7.x*
