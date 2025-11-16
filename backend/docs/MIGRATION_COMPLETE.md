# Migration terminée - Système de Realms Blizzard

## ✅ Ce qui a été fait

### 1. Architecture de base de données

**Créé:**
- ✅ Enum `WowGameType` avec 6 types de jeu (Retail, Classic Era, Classic Progression, Classic Anniversary, SoD, Hardcore)
- ✅ Entité `BlizzardGameRealm` avec tous les champs nécessaires
- ✅ Relation ManyToOne entre `GameGuild` et `BlizzardGameRealm`
- ✅ Migration Doctrine (Version20251104204947)

**Résultat:**
```sql
-- Nouvelle table
CREATE TABLE blizzard_game_realm (
    id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    game_type VARCHAR(255) NOT NULL,  -- enum: retail, classic-anniversary, etc.
    region VARCHAR(10) NOT NULL,
    -- ... autres champs
    UNIQUE (slug, game_type, region)
);

-- Nouvelle foreign key
ALTER TABLE game_guild
ADD COLUMN blizzard_realm_id INT REFERENCES blizzard_game_realm(id);
```

### 2. Commandes CLI

**Commande de synchronisation des realms:**
```bash
php bin/console app:sync-blizzard-realms
```
- Récupère tous les serveurs WoW depuis l'API Blizzard
- Supporte tous les types de jeu (Retail, Classic, SoD, etc.)
- Options: `--region`, `--game-type`, `--dry-run`

**Commande de migration des guildes:**
```bash
php bin/console app:migrate-guild-realms --auto-detect --default-game-type=classic-anniversary
```
- Migre automatiquement les guildes existantes
- Détection intelligente du type de jeu
- Mode interactif pour cas ambigus
- Options: `--dry-run`, `--auto-detect`, `--default-game-type`, `--region`

### 3. Migration des données

**Synchronisation des realms:**
```bash
✓ Syncing Retail realms for region EU
  Found 250 realms from API
  [+] Auberdine
  [+] Sulfuron
  ...

✓ Syncing Classic Anniversary realms for region EU
  Found 45 realms from API
  [+] Spineshatter
  ...

[OK] Sync completed: 1245 created, 0 updated
```

**Migration des guildes existantes:**
```bash
Found 2 guilds to migrate
✓ Guild "Tracker" → Spineshatter (Classic Anniversary) [auto-detected]
✓ Guild "Tracker" → Spineshatter (Classic Anniversary) [auto-detected]

Migration Summary:
| Migrated: 2 | Skipped: 0 | Ambiguous: 0 | Not Found: 0 |

[OK] Migration completed: 2 guilds migrated
```

### 4. Documentation créée

- ✅ **BLIZZARD_REALMS_ARCHITECTURE.md** - Architecture complète et design decisions
- ✅ **DATABASE_SCHEMA.md** - Schéma visuel des relations
- ✅ **GETTING_STARTED_REALMS.md** - Guide d'utilisation et migration
- ✅ **USAGE_EXAMPLES.md** - Exemples de code pratiques
- ✅ **MIGRATION_COMPLETE.md** (ce fichier) - Résumé de la migration

## 🎯 Résultat final

### État actuel de la base de données

```
blizzard_game_realm
├── 1245 realms synchronisés
│   ├── Retail: ~250 realms (EU)
│   ├── Classic Anniversary: ~45 realms (EU)
│   ├── Season of Discovery: ~45 realms (EU)
│   ├── Hardcore: ~45 realms (EU)
│   └── ...

game_guild
├── 2 guildes migrées
│   └── Toutes liées à blizzard_game_realm ✓
└── Champs legacy (realm, blizzard_id) conservés pour compatibilité
```

### Verification

```bash
# Vérifier les realms
php bin/console doctrine:query:sql "SELECT COUNT(*), game_type FROM blizzard_game_realm GROUP BY game_type"

# Vérifier les guildes
php bin/console doctrine:query:sql "
SELECT
    g.name,
    r.name AS realm,
    r.game_type
FROM game_guild g
INNER JOIN blizzard_game_realm r ON g.blizzard_realm_id = r.id
"
```

## 📊 Avantages obtenus

### 1. Flexibilité
- ✅ Support de tous les types de jeu WoW
- ✅ Même nom de serveur sur différents types (ex: Sulfuron Retail ET Classic)
- ✅ Ajout de nouveaux types de jeu sans migration DB

### 2. Automatisation
- ✅ Namespaces API calculés automatiquement
- ✅ Synchronisation des realms en une commande
- ✅ Migration des guildes intelligente

### 3. Intégrité des données
- ✅ Foreign keys pour relations fortes
- ✅ Index unique (slug, game_type, region)
- ✅ Enums type-safe (PHP 8.1)

### 4. Maintenance facilitée
- ✅ Documentation complète
- ✅ Exemples de code pratiques
- ✅ Commandes CLI pour opérations courantes

## 🚀 Utilisation au quotidien

### Créer une nouvelle guilde

```php
$realm = $realmRepository->findOneBy([
    'slug' => 'sulfuron',
    'gameType' => WowGameType::SEASON_OF_DISCOVERY,
    'region' => 'eu'
]);

$guild = new GameGuild();
$guild->setName('My Guild');
$guild->setBlizzardRealm($realm);  // ← Nouvelle relation
$em->persist($guild);
$em->flush();
```

### Appeler l'API Blizzard

```php
$realm = $guild->getBlizzardRealm();

// Namespace automatique selon le type de jeu
$namespace = $realm->getDynamicNamespace();  // "dynamic-classic1x-eu" pour SoD

$url = sprintf(
    'https://%s.api.blizzard.com/data/wow/guild/%s/%s/roster?namespace=%s',
    $realm->getRegion(),
    $realm->getSlug(),
    strtolower($guild->getName()),
    $namespace
);
```

### Rechercher des guildes

```php
// Toutes les guildes Season of Discovery EU
$guilds = $guildRepository->findByGameType(
    WowGameType::SEASON_OF_DISCOVERY,
    'eu'
);

// Une guilde spécifique
$guild = $guildRepository->findByNameAndRealm(
    'Tracker',
    'spineshatter',
    WowGameType::CLASSIC_ANNIVERSARY,
    'eu'
);
```

## 🔄 Maintenance future

### Ajouter un nouveau type de jeu

Exemple: Blizzard lance "Classic TBC"

1. Éditer `src/Enum/WowGameType.php`:
```php
case CLASSIC_TBC = 'classic-tbc';

// Dans les méthodes:
self::CLASSIC_TBC => "profile-classic-tbc-{$region}",
```

2. Synchroniser les realms:
```bash
php bin/console app:sync-blizzard-realms --game-type=classic-tbc
```

**Aucune migration DB nécessaire!** ✅

### Mettre à jour les realms

Exécuter régulièrement pour avoir les derniers serveurs:

```bash
# Tous les jours ou semaines
php bin/console app:sync-blizzard-realms --region=eu
```

### Migrer de nouvelles guildes

Si vous ajoutez des guildes avec l'ancien système:

```bash
php bin/console app:migrate-guild-realms --auto-detect --default-game-type=classic-anniversary
```

## 📈 Statistiques actuelles

```sql
-- Realms par type de jeu
SELECT game_type, COUNT(*) as total
FROM blizzard_game_realm
WHERE region = 'eu'
GROUP BY game_type;

-- Guildes par type de jeu
SELECT r.game_type, COUNT(g.id) as total
FROM game_guild g
INNER JOIN blizzard_game_realm r ON g.blizzard_realm_id = r.id
GROUP BY r.game_type;
```

## ⚠️ Points d'attention

### Champs legacy

Les champs `realm` et `blizzard_id` dans `game_guild` sont marqués `@deprecated` mais **conservés** pour:
- Compatibilité avec code existant
- Migration progressive
- Rollback possible si besoin

**Plan future (optionnel):**
1. Vérifier que tout le code utilise `blizzardRealm`
2. Rendre `blizzardRealm` non-nullable
3. Supprimer `realm` et `blizzard_id`

### Guildes non migrées

Si des guildes n'ont pas pu être migrées (realm introuvable):

```bash
# Trouver les guildes non migrées
php bin/console doctrine:query:sql "
SELECT id, name, realm
FROM game_guild
WHERE blizzard_realm_id IS NULL
AND realm IS NOT NULL
"

# Synchroniser les realms manquants
php bin/console app:sync-blizzard-realms --region=eu

# Re-lancer la migration
php bin/console app:migrate-guild-realms --auto-detect
```

## 📚 Ressources

### Documentation
- [Architecture complète](./BLIZZARD_REALMS_ARCHITECTURE.md)
- [Schéma de base de données](./DATABASE_SCHEMA.md)
- [Guide de démarrage](./GETTING_STARTED_REALMS.md)
- [Exemples d'utilisation](./USAGE_EXAMPLES.md)

### API Blizzard
- [WoW Namespaces](https://develop.battle.net/documentation/world-of-warcraft/guides/namespaces)
- [WoW Classic Namespaces](https://develop.battle.net/documentation/world-of-warcraft-classic/guides/namespaces)
- [Game Data APIs](https://develop.battle.net/documentation/world-of-warcraft/game-data-apis)

### Code
- Enum: `src/Enum/WowGameType.php`
- Entité Realm: `src/Entity/BlizzardGameRealm.php`
- Entité Guild: `src/Entity/GameGuild.php`
- Cmd Sync: `src/Command/GetBlizzardRealmCommand.php`
- Cmd Migrate: `src/Command/MigrateGuildRealmsCommand.php`
- Migration: `migrations/Version20251104204947.php`

## ✨ Conclusion

La migration est **terminée avec succès**!

Vous disposez maintenant d'un système:
- ✅ Flexible pour tous les types de jeu WoW
- ✅ Pérenne face aux évolutions de Blizzard
- ✅ Automatisé avec des commandes CLI
- ✅ Bien documenté avec exemples
- ✅ Type-safe avec enums PHP

**Prochaine étape:** Adapter votre code frontend/API pour utiliser les nouvelles relations!

---

*Migration effectuée le: 2025-11-04*
*Version Symfony: 7.x*
*Version PHP: 8.3+*
