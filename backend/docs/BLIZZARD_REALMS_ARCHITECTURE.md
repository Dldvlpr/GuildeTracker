# Architecture Blizzard Realms - WoW Game Types

## Vue d'ensemble

Ce document décrit l'architecture de la base de données pour gérer les différents types de serveurs World of Warcraft (Retail, Classic, Season of Discovery, etc.) de manière pérenne et évolutive.

## Types de jeu WoW supportés

### Enum `WowGameType`

L'enum `WowGameType` définit tous les types de jeu WoW disponibles:

| Type | Valeur DB | Label | Namespace Profile | Namespace Dynamic |
|------|-----------|-------|-------------------|-------------------|
| `RETAIL` | `retail` | Retail | `profile-{region}` | `dynamic-{region}` |
| `CLASSIC_ERA` | `classic-era` | Classic Era | `profile-classic-{region}` | `dynamic-classic-{region}` |
| `CLASSIC_PROGRESSION` | `classic-progression` | Classic Progression | `profile-classic-{region}` | `dynamic-classic-{region}` |
| `CLASSIC_ANNIVERSARY` | `classic-anniversary` | Classic Anniversary | `profile-classic1x-{region}` | `dynamic-classic1x-{region}` |
| `SEASON_OF_DISCOVERY` | `season-of-discovery` | Season of Discovery | `profile-classic1x-{region}` | `dynamic-classic1x-{region}` |
| `HARDCORE` | `hardcore` | Hardcore | `profile-classic1x-{region}` | `dynamic-classic1x-{region}` |

### Namespaces Blizzard API

L'API Blizzard utilise des **namespaces** pour distinguer les différentes versions du jeu:

- **Profile namespace**: Pour les données de profil (personnages, guildes)
- **Dynamic namespace**: Pour les données dynamiques (rosters, enchères)
- **Static namespace**: Pour les données statiques (items, sorts)

#### Exemples pour la région EU:
- Retail: `profile-eu`, `dynamic-eu`, `static-eu`
- Classic Era: `profile-classic-eu`, `dynamic-classic-eu`, `static-classic-eu`
- Classic Anniversary/SoD/Hardcore: `profile-classic1x-eu`, `dynamic-classic1x-eu`, `static-classic1x-eu`

## Structure de base de données

### Entité `BlizzardGameRealm`

Table centrale pour stocker tous les serveurs WoW, quelque soit leur type de jeu.

#### Champs principaux:

```php
class BlizzardGameRealm
{
    private ?int $id;                          // ID auto-incrémenté
    private ?int $blizzardRealmId;            // ID Blizzard du realm
    private ?string $name;                     // Nom du realm (ex: "Auberdine")
    private ?string $slug;                     // Slug pour API (ex: "auberdine")
    private ?WowGameType $gameType;           // Type de jeu (ENUM)
    private ?string $region;                   // Région (us, eu, kr, tw, cn)
    private ?string $locale;                   // Locale (en_US, fr_FR, etc.)
    private ?string $timezone;                 // Fuseau horaire
    private ?int $connectedRealmId;           // ID du connected realm (Retail)
    private bool $isTournament;               // Realm tournoi?
    private ?string $type;                    // Type de serveur (PVP, PVE, RP, etc.)
    private Collection $guilds;               // Guildes sur ce realm
    private ?\DateTimeImmutable $lastSyncAt;  // Dernière sync API
}
```

#### Index unique:
```sql
UNIQUE (slug, game_type, region)
```

Ce qui permet d'avoir le même nom de realm pour différents types de jeu.

**Exemple**: "Sulfuron" peut exister en tant que:
- `Sulfuron` (Retail) - EU
- `Sulfuron` (Classic Anniversary) - EU
- `Sulfuron` (Season of Discovery) - EU

### Entité `GameGuild`

Relation avec `BlizzardGameRealm`:

```php
class GameGuild
{
    // ... autres champs ...

    // DEPRECATED - à migrer progressivement
    private ?string $realm;
    private ?string $blizzardId;

    // NOUVEAU - relation forte avec BlizzardGameRealm
    private ?BlizzardGameRealm $blizzardRealm;
}
```

### Migration progressive

Les champs `realm` et `blizzardId` sont marqués `@deprecated` mais conservés pour compatibilité descendante.

**Plan de migration**:
1. Ajouter `blizzardRealm` (nullable) ✅
2. Créer/populer `BlizzardGameRealm` avec commande sync ✅
3. Migrer progressivement les guildes existantes
4. Rendre `blizzardRealm` non-nullable (optionnel)
5. Supprimer `realm` et `blizzardId` (futur)

## Commandes CLI

### Synchroniser les realms depuis Blizzard

```bash
# Synchroniser tous les types de jeu pour EU
php bin/console app:sync-blizzard-realms

# Synchroniser seulement Retail pour US
php bin/console app:sync-blizzard-realms --region=us --game-type=retail

# Preview sans sauvegarder
php bin/console app:sync-blizzard-realms --dry-run

# Synchroniser Classic Anniversary
php bin/console app:sync-blizzard-realms --game-type=classic-anniversary

# Synchroniser Season of Discovery
php bin/console app:sync-blizzard-realms --game-type=season-of-discovery
```

## Utilisation dans le code

### Obtenir le namespace automatiquement

```php
$realm = $realmRepository->findOneBy([
    'slug' => 'auberdine',
    'gameType' => WowGameType::CLASSIC_ANNIVERSARY,
    'region' => 'eu'
]);

// Les namespaces sont générés automatiquement
$profileNamespace = $realm->getProfileNamespace();  // "profile-classic1x-eu"
$dynamicNamespace = $realm->getDynamicNamespace(); // "dynamic-classic1x-eu"
$staticNamespace = $realm->getStaticNamespace();   // "static-classic1x-eu"
```

### Appeler l'API Blizzard

```php
// Récupérer les personnages d'un utilisateur
$namespace = $realm->getProfileNamespace();
$url = "https://eu.api.blizzard.com/profile/user/wow?namespace={$namespace}&locale=en_US";

// Récupérer une guilde
$namespace = $realm->getDynamicNamespace();
$url = "https://eu.api.blizzard.com/data/wow/guild/{$realmSlug}/{$guildSlug}/roster?namespace={$namespace}";
```

### Créer une nouvelle guilde

```php
$realm = $realmRepository->findOneBy([
    'slug' => 'sulfuron',
    'gameType' => WowGameType::SEASON_OF_DISCOVERY,
    'region' => 'eu'
]);

$guild = new GameGuild();
$guild->setName('My Guild');
$guild->setBlizzardRealm($realm);  // Relation forte
// ... autres champs
$em->persist($guild);
```

## Avantages de cette architecture

1. **Pérennité**: Nouveaux types de jeu = ajouter un cas dans l'enum
2. **Flexibilité**: Même realm name pour différents jeux
3. **Automatisation**: Namespaces calculés automatiquement
4. **Intégrité**: Relations DB fortes avec foreign keys
5. **Migration douce**: Champs legacy conservés temporairement
6. **Sync facile**: Commande CLI pour importer tous les realms

## Évolution future

### Ajout d'un nouveau type de jeu

Si Blizzard lance "WoW Classic: Cataclysm" par exemple:

1. Ajouter dans `WowGameType.php`:
```php
case CLASSIC_CATACLYSM = 'classic-cataclysm';

// Dans getProfileNamespace():
self::CLASSIC_CATACLYSM => "profile-classic-cataclysm-{$region}",
```

2. Relancer la commande de sync:
```bash
php bin/console app:sync-blizzard-realms --game-type=classic-cataclysm
```

Aucune modification de schéma DB nécessaire!

## Documentation Blizzard

- [WoW Namespaces](https://develop.battle.net/documentation/world-of-warcraft/guides/namespaces)
- [WoW Classic Namespaces](https://develop.battle.net/documentation/world-of-warcraft-classic/guides/namespaces)
- [Game Data APIs](https://develop.battle.net/documentation/world-of-warcraft/game-data-apis)

## Références du code

- Enum: `backend/src/Enum/WowGameType.php`
- Entité Realm: `backend/src/Entity/BlizzardGameRealm.php`
- Entité Guild: `backend/src/Entity/GameGuild.php`
- Commande Sync: `backend/src/Command/GetBlizzardRealmCommand.php`
- Migration: `backend/migrations/Version20251104204947.php`
