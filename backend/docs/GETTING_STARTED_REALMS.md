# Guide de démarrage - Gestion des Realms WoW

## Mise en place initiale

### 1. Exécuter la migration

Appliquer la nouvelle structure de base de données:

```bash
php bin/console doctrine:migrations:migrate
```

Cette migration va créer la table `blizzard_game_realm` et ajouter la relation `blizzard_realm_id` à `game_guild`.

### 2. Synchroniser les realms depuis Blizzard

#### Synchroniser tous les types de jeu pour EU

```bash
php bin/console app:sync-blizzard-realms --region=eu
```

#### Synchroniser seulement un type de jeu spécifique

```bash
# Classic Anniversary
php bin/console app:sync-blizzard-realms --region=eu --game-type=classic-anniversary

# Season of Discovery
php bin/console app:sync-blizzard-realms --region=eu --game-type=season-of-discovery

# Retail
php bin/console app:sync-blizzard-realms --region=eu --game-type=retail

# Hardcore
php bin/console app:sync-blizzard-realms --region=eu --game-type=hardcore
```

#### Preview avant import (dry-run)

```bash
php bin/console app:sync-blizzard-realms --dry-run
```

Ceci affichera ce qui sera créé/mis à jour sans modifier la base de données.

#### Synchroniser plusieurs régions

```bash
php bin/console app:sync-blizzard-realms --region=eu
php bin/console app:sync-blizzard-realms --region=us
php bin/console app:sync-blizzard-realms --region=kr
```

### 3. Vérifier les realms importés

```bash
php bin/console doctrine:query:sql "SELECT name, game_type, region FROM blizzard_game_realm ORDER BY game_type, name"
```

## Utilisation dans le code

### Trouver un realm

```php
use App\Entity\BlizzardGameRealm;
use App\Enum\WowGameType;

// Par slug, type de jeu et région
$realm = $realmRepository->findOneBy([
    'slug' => 'auberdine',
    'gameType' => WowGameType::CLASSIC_ANNIVERSARY,
    'region' => 'eu'
]);

// Tous les realms d'un type de jeu
$classicRealms = $realmRepository->findBy([
    'gameType' => WowGameType::CLASSIC_ANNIVERSARY,
    'region' => 'eu'
]);
```

### Créer une guilde avec le nouveau système

```php
use App\Entity\GameGuild;
use App\Entity\BlizzardGameRealm;
use App\Enum\WowGameType;

// 1. Trouver ou créer le realm
$realm = $realmRepository->findOneBy([
    'slug' => 'sulfuron',
    'gameType' => WowGameType::SEASON_OF_DISCOVERY,
    'region' => 'eu'
]);

// Si le realm n'existe pas, le synchroniser d'abord
// ou créer manuellement si besoin

// 2. Créer la guilde
$guild = new GameGuild();
$guild->setName('My Guild Name');
$guild->setFaction('Alliance');
$guild->setBlizzardRealm($realm);  // Nouvelle relation
$guild->setIsPublic(true);
// ... autres propriétés

$entityManager->persist($guild);
$entityManager->flush();
```

### Migrer une guilde existante

```php
// Guilde existante avec ancienne structure
$guild = $guildRepository->find($guildId);

// Le realm est stocké comme string
$oldRealmName = $guild->getRealm(); // ex: "Sulfuron"

// Trouver le realm correspondant
// Note: Il faut connaître le type de jeu!
$realm = $realmRepository->findOneBy([
    'slug' => strtolower(str_replace(' ', '-', $oldRealmName)),
    'gameType' => WowGameType::CLASSIC_ANNIVERSARY, // À déterminer
    'region' => 'eu'
]);

if ($realm) {
    $guild->setBlizzardRealm($realm);
    $entityManager->flush();
}
```

### Obtenir les namespaces automatiquement

```php
// Récupérer une guilde avec son realm
$guild = $guildRepository->find($guildId);
$realm = $guild->getBlizzardRealm();

if ($realm) {
    // Les namespaces sont calculés automatiquement
    $profileNs = $realm->getProfileNamespace();   // ex: "profile-classic1x-eu"
    $dynamicNs = $realm->getDynamicNamespace();  // ex: "dynamic-classic1x-eu"
    $staticNs = $realm->getStaticNamespace();    // ex: "static-classic1x-eu"

    // Utiliser dans les appels API
    $url = sprintf(
        'https://eu.api.blizzard.com/data/wow/guild/%s/%s/roster?namespace=%s',
        $realm->getSlug(),
        strtolower($guild->getName()),
        $dynamicNs
    );
}
```

### Afficher les informations du realm

```php
// Dans un template Twig
{{ guild.blizzardRealm.name }}
{{ guild.blizzardRealm.gameType.label }}
{{ guild.blizzardRealm.region|upper }}

// Exemple: "Sulfuron Season of Discovery EU"
```

## Cas d'usage courants

### 1. Lister toutes les guildes Classic Anniversary EU

```php
$realms = $realmRepository->findBy([
    'gameType' => WowGameType::CLASSIC_ANNIVERSARY,
    'region' => 'eu'
]);

$guilds = [];
foreach ($realms as $realm) {
    $guilds = array_merge($guilds, $realm->getGuilds()->toArray());
}
```

### 2. Rechercher une guilde sur tous les types de jeu

```php
// Par nom et realm slug (tous types de jeu)
$guildName = 'My Guild';
$realmSlug = 'sulfuron';

$realms = $realmRepository->findBy(['slug' => $realmSlug]);

foreach ($realms as $realm) {
    foreach ($realm->getGuilds() as $guild) {
        if (strtolower($guild->getName()) === strtolower($guildName)) {
            echo sprintf(
                "Found: %s on %s (%s)\n",
                $guild->getName(),
                $realm->getName(),
                $realm->getGameType()->getLabel()
            );
        }
    }
}
```

### 3. Statistiques par type de jeu

```php
$stats = [];
$gameTypes = WowGameType::cases();

foreach ($gameTypes as $gameType) {
    $realms = $realmRepository->findBy([
        'gameType' => $gameType,
        'region' => 'eu'
    ]);

    $guildCount = 0;
    foreach ($realms as $realm) {
        $guildCount += $realm->getGuilds()->count();
    }

    $stats[$gameType->getLabel()] = [
        'realms' => count($realms),
        'guilds' => $guildCount
    ];
}

print_r($stats);
// [
//   'Retail' => ['realms' => 250, 'guilds' => 1234],
//   'Classic Anniversary' => ['realms' => 45, 'guilds' => 567],
//   ...
// ]
```

## Maintenance

### Mettre à jour les realms

Exécuter régulièrement pour synchroniser les nouveaux realms ou changements:

```bash
# Mettre à jour tous les realms
php bin/console app:sync-blizzard-realms --region=eu

# Preview des changements
php bin/console app:sync-blizzard-realms --region=eu --dry-run
```

### Ajouter un nouveau type de jeu

Quand Blizzard lance un nouveau type de jeu (ex: Classic TBC):

1. Ajouter dans `src/Enum/WowGameType.php`:
```php
case CLASSIC_TBC = 'classic-tbc';
```

2. Ajouter les namespaces correspondants dans les méthodes de l'enum

3. Synchroniser:
```bash
php bin/console app:sync-blizzard-realms --game-type=classic-tbc
```

## Migration des guildes existantes

Si vous avez des guildes existantes avec l'ancien système (champ `realm` en string), utilisez la commande de migration:

### Migration automatique (recommandée)

```bash
# Preview en dry-run
php bin/console app:migrate-guild-realms --dry-run --auto-detect --default-game-type=classic-anniversary

# Exécuter la migration
php bin/console app:migrate-guild-realms --auto-detect --default-game-type=classic-anniversary
```

Options disponibles:
- `--dry-run`: Preview sans sauvegarder
- `--auto-detect`: Détection automatique du type de jeu
- `--default-game-type`: Type de jeu par défaut (classic-anniversary, season-of-discovery, retail, etc.)
- `--region`: Région par défaut (eu, us, kr, tw)

### Migration manuelle (pour cas ambigus)

Si une guilde existe sur plusieurs types de jeu (ex: Spineshatter sur Classic, SoD et Hardcore), la commande vous demandera de choisir:

```bash
php bin/console app:migrate-guild-realms

# Output:
# Guild "My Guild" has multiple realm matches for "spineshatter":
#   [0] Spineshatter (Classic Anniversary - EU)
#   [1] Spineshatter (Hardcore - EU)
#   [2] Spineshatter (Season of Discovery - EU)
#   [skip] Skip this guild
# Select the correct realm:
```

### Exemples de migration

```bash
# Migrer avec Season of Discovery par défaut
php bin/console app:migrate-guild-realms --auto-detect --default-game-type=season-of-discovery

# Migrer avec Retail par défaut
php bin/console app:migrate-guild-realms --auto-detect --default-game-type=retail

# Migrer avec Hardcore par défaut
php bin/console app:migrate-guild-realms --auto-detect --default-game-type=hardcore

# Migrer pour la région US
php bin/console app:migrate-guild-realms --auto-detect --region=us --default-game-type=retail
```

### Vérifier la migration

```bash
# Lister toutes les guildes avec leur realm
php bin/console doctrine:query:sql "SELECT g.name, g.realm as old_realm, r.name as new_realm, r.game_type FROM game_guild g LEFT JOIN blizzard_game_realm r ON g.blizzard_realm_id = r.id"

# Compter les guildes non migrées
php bin/console doctrine:query:sql "SELECT COUNT(*) FROM game_guild WHERE blizzard_realm_id IS NULL AND realm IS NOT NULL"
```

### Résultats de migration

La commande affiche un résumé:

```
Migration Summary
-----------------
| Status             | Count |
| Migrated           | 45    |  ← Guildes migrées avec succès
| Skipped            | 2     |  ← Guildes sans realm
| Ambiguous (manual) | 3     |  ← Guildes nécessitant sélection manuelle
| Not Found          | 1     |  ← Realms introuvables (vérifier sync)
```

## Troubleshooting

### La commande de sync échoue

```bash
# Vérifier les credentials Blizzard
php bin/console debug:container --parameters | grep BLIZZARD

# Tester manuellement l'API
curl -u "CLIENT_ID:CLIENT_SECRET" -d grant_type=client_credentials https://eu.battle.net/oauth/token
```

### Realm introuvable

```bash
# Lister tous les realms d'un type
php bin/console doctrine:query:sql "SELECT name, slug, game_type FROM blizzard_game_realm WHERE game_type = 'classic-anniversary' ORDER BY name"

# Rechercher par slug
php bin/console doctrine:query:sql "SELECT * FROM blizzard_game_realm WHERE slug LIKE '%sulfuron%'"
```

### Migration de données legacy

Pour migrer des guildes existantes avec `realm` (string) vers `blizzard_realm_id`:

```bash
# 1. Créer un script de migration
php bin/console make:command app:migrate-guild-realms

# 2. Dans le script, pour chaque guilde:
#    - Extraire le realm (string)
#    - Déterminer le game_type (par défaut ou via heuristique)
#    - Trouver le BlizzardGameRealm correspondant
#    - Mettre à jour la relation

# 3. Exécuter
php bin/console app:migrate-guild-realms
```

## Ressources

- [Architecture complète](./BLIZZARD_REALMS_ARCHITECTURE.md)
- [Schéma de base de données](./DATABASE_SCHEMA.md)
- [Documentation Blizzard API](https://develop.battle.net/documentation/world-of-warcraft)
