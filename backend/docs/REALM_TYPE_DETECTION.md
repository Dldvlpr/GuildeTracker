# Système de Détection des Types de Serveurs WoW

## Vue d'ensemble

Le système hybride de détection des types de serveurs permet d'identifier précisément à quel type de jeu appartient chaque serveur WoW (Retail, Classic Era, Anniversary, Season of Discovery, Hardcore) en combinant plusieurs sources de données.

## Architecture

### Ordre de Priorité

Le système utilise une **cascade de priorités** pour déterminer le type d'un serveur :

1. **Override manuel** (`realm_type_override`) → Vérité absolue ⭐⭐⭐⭐⭐
2. **API Blizzard category** → Source officielle ⭐⭐⭐⭐
3. **Known metadata** (`known_realm_metadata`) → Données confirmées ⭐⭐⭐
4. **Fallback patterns** → Détection par nom/slug ⭐⭐

### Tables de Base de Données

#### `realm_type_override`
Corrections manuelles pour les serveurs mal classés par l'API Blizzard.

**Colonnes :**
- `slug` : Identifiant du serveur (ex: "soulseeker")
- `region` : Région (us, eu, kr, tw)
- `game_type` : Type forcé (retail, classic-era, classic-anniversary, season-of-discovery, hardcore)
- `reason` : Raison de l'override (pour traçabilité)
- `updated_at` : Date de mise à jour

#### `known_realm_metadata`
Métadonnées des serveurs confirmés via annonces officielles ou sources communautaires.

**Colonnes :**
- `slug` : Identifiant du serveur
- `region` : Région
- `expected_game_type` : Type attendu
- `launch_date` : Date de lancement
- `source` : Source de l'information (blizzard_announcement, community, wowhead)
- `confidence_score` : Score de confiance (1-10)
- `notes` : Notes additionnelles
- `created_at` : Date de création

---

## Guide d'Utilisation

### 🔄 Synchroniser les serveurs

La synchronisation récupère automatiquement tous les serveurs depuis l'API Blizzard :

```bash
# Synchroniser tous les types de jeux pour une région
php bin/console app:sync-blizzard-realms --region=eu
php bin/console app:sync-blizzard-realms --region=us

# Synchroniser un type spécifique
php bin/console app:sync-blizzard-realms --region=eu --game-type=hardcore
php bin/console app:sync-blizzard-realms --region=us --game-type=classic-anniversary

# Mode test (sans sauvegarder)
php bin/console app:sync-blizzard-realms --region=eu --dry-run
```

**Types disponibles :** `retail`, `classic-era`, `classic-progression`, `classic-anniversary`, `season-of-discovery`, `hardcore`, `all`

---

## 📝 Ajouter un Nouveau Serveur

### Cas 1 : Nouveau serveur automatiquement détecté ✅

**Quand ?** Blizzard lance un nouveau serveur et l'API le classe correctement.

**Action :** Aucune ! La synchronisation le détectera automatiquement.

```bash
php bin/console app:sync-blizzard-realms --region=us
```

---

### Cas 2 : API Blizzard se trompe de type ⚠️

**Quand ?** L'API marque un serveur Hardcore comme "Anniversary" par exemple.

**Solution : Override manuel**

```bash
php bin/console dbal:run-sql "
INSERT INTO realm_type_override (slug, region, game_type, reason, updated_at)
VALUES ('nom-du-serveur', 'eu', 'hardcore', 'Raison de la correction', NOW())
"
```

**Exemple réel :**
```bash
php bin/console dbal:run-sql "
INSERT INTO realm_type_override (slug, region, game_type, reason, updated_at)
VALUES ('soulseeker', 'eu', 'hardcore', 'API marks as Anniversary but launched as Hardcore on 2024-11-21', NOW())
"
```

Puis re-synchroniser :
```bash
php bin/console app:sync-blizzard-realms --region=eu
```

---

### Cas 3 : Ajouter métadonnées pour serveur annoncé 📚

**Quand ?** Blizzard annonce officiellement un nouveau serveur avant son lancement.

**Solution : Ajouter aux métadonnées**

```bash
php bin/console dbal:run-sql "
INSERT INTO known_realm_metadata
(slug, region, expected_game_type, launch_date, source, confidence_score, notes, created_at)
VALUES
('nom-serveur', 'us', 'hardcore', '2024-12-15', 'blizzard_announcement', 10, 'Description', NOW())
"
```

**Exemple réel :**
```bash
php bin/console dbal:run-sql "
INSERT INTO known_realm_metadata
(slug, region, expected_game_type, launch_date, source, confidence_score, notes, created_at)
VALUES
('deathbringer', 'eu', 'hardcore', '2024-12-15', 'blizzard_announcement', 10, 'New Hardcore server announced Dec 2024', NOW())
"
```

**Scores de confiance :**
- `10` : Annonce officielle Blizzard
- `8-9` : Sources communautaires fiables (Wowhead, MMO-Champion)
- `5-7` : Information probable mais non confirmée
- `1-4` : Spéculation

---

### Cas 4 : Ajouter plusieurs serveurs via code 🔨

**Quand ?** Batch de serveurs à ajouter régulièrement (mise à jour majeure).

**Solution : Modifier le code de population**

1. **Éditez** `src/Command/PopulateRealmMetadataCommand.php`

2. **Ajoutez** dans la méthode `getKnownRealmData()` :

```php
[
    'slug' => 'nouveau-serveur',
    'region' => 'eu',
    'gameType' => WowGameType::HARDCORE,
    'launchDate' => new \DateTimeImmutable('2024-12-15'),
    'source' => 'blizzard_announcement',
    'confidenceScore' => 10,
    'notes' => 'New Hardcore server December 2024'
],
```

3. **Relancez** la commande :

```bash
php bin/console app:populate-realm-metadata
```

---

## 🔍 Vérifier et Déboguer

### Vérifier un serveur spécifique

```bash
# Vérifier dans les overrides
php bin/console dbal:run-sql "
SELECT * FROM realm_type_override WHERE slug = 'soulseeker'
"

# Vérifier dans les métadonnées
php bin/console dbal:run-sql "
SELECT * FROM known_realm_metadata WHERE slug = 'soulseeker'
"

# Vérifier dans les serveurs synchronisés
php bin/console dbal:run-sql "
SELECT name, slug, game_type, region
FROM blizzard_game_realm
WHERE slug = 'soulseeker'
"
```

### Lister tous les serveurs par type

```bash
php bin/console dbal:run-sql "
SELECT game_type, region, COUNT(*) as count
FROM blizzard_game_realm
GROUP BY game_type, region
ORDER BY region, game_type
"
```

### Trouver les serveurs avec override

```bash
php bin/console dbal:run-sql "
SELECT o.slug, o.region, o.game_type, o.reason, r.name
FROM realm_type_override o
LEFT JOIN blizzard_game_realm r ON r.slug = o.slug AND r.region = o.region
ORDER BY o.updated_at DESC
"
```

### Debug : Voir les logs de détection

Les logs du système se trouvent dans `var/log/dev.log` (ou `prod.log` en production) :

```bash
tail -f var/log/dev.log | grep RealmGameTypeDetector
```

---

## 📊 Exemples de Cas d'Usage

### Exemple 1 : Blizzard lance 3 nouveaux serveurs Anniversary

**Situation :** Blizzard annonce 3 serveurs Fresh le 20 janvier 2025.

**Action :**

```bash
# Ajouter aux métadonnées avant le lancement
php bin/console dbal:run-sql "
INSERT INTO known_realm_metadata
(slug, region, expected_game_type, launch_date, source, confidence_score, notes, created_at)
VALUES
('frostbite', 'us', 'classic-anniversary', '2025-01-20', 'blizzard_announcement', 10, 'New PvP Anniversary Fresh server', NOW()),
('serenity', 'us', 'classic-anniversary', '2025-01-20', 'blizzard_announcement', 10, 'New PvE Anniversary Fresh server', NOW()),
('eternal', 'eu', 'classic-anniversary', '2025-01-20', 'blizzard_announcement', 10, 'New EU Anniversary Fresh server', NOW())
"

# Le jour du lancement, synchroniser
php bin/console app:sync-blizzard-realms --region=us
php bin/console app:sync-blizzard-realms --region=eu
```

---

### Exemple 2 : Corriger un serveur mal classé

**Situation :** L'API marque "Lone Wolf US" comme Season of Discovery au lieu de Hardcore.

**Action :**

```bash
# Override immédiat
php bin/console dbal:run-sql "
INSERT INTO realm_type_override (slug, region, game_type, reason, updated_at)
VALUES ('lone-wolf', 'us', 'hardcore', 'API incorrectly marks as Seasonal, official Hardcore server since Aug 2023', NOW())
"

# Nettoyer l'ancien mauvais enregistrement et re-synchroniser
php bin/console dbal:run-sql "DELETE FROM blizzard_game_realm WHERE slug = 'lone-wolf' AND region = 'us'"
php bin/console app:sync-blizzard-realms --region=us
```

---

### Exemple 3 : Préparer une mise à jour de contenu

**Situation :** Blizzard annonce Season of Discovery Phase 2 avec de nouveaux serveurs.

**Action :**

1. **Éditer** `src/Command/PopulateRealmMetadataCommand.php`

2. **Ajouter** les nouveaux serveurs :

```php
// Season of Discovery Phase 2 (January 2025)
[
    'slug' => 'new-discovery-1',
    'region' => 'us',
    'gameType' => WowGameType::SEASON_OF_DISCOVERY,
    'launchDate' => new \DateTimeImmutable('2025-01-15'),
    'source' => 'blizzard_announcement',
    'confidenceScore' => 10,
    'notes' => 'Phase 2 SoD server'
],
```

3. **Repeupler** :

```bash
php bin/console app:populate-realm-metadata
php bin/console app:sync-blizzard-realms --region=us
```

---

## 🛠️ Maintenance

### Nettoyer les serveurs fermés

```bash
# Lister les serveurs potentiellement fermés (pas de sync récent)
php bin/console dbal:run-sql "
SELECT name, slug, region, game_type, last_sync_at
FROM blizzard_game_realm
WHERE last_sync_at < NOW() - INTERVAL 30 DAY
ORDER BY last_sync_at
"

# Supprimer un serveur spécifique
php bin/console dbal:run-sql "
DELETE FROM blizzard_game_realm
WHERE slug = 'serveur-ferme' AND region = 'eu'
"
```

### Mettre à jour un override

```bash
php bin/console dbal:run-sql "
UPDATE realm_type_override
SET game_type = 'classic-era', reason = 'Serveur transféré en Classic Era', updated_at = NOW()
WHERE slug = 'nom-serveur' AND region = 'eu'
"
```

### Supprimer un override devenu inutile

```bash
php bin/console dbal:run-sql "
DELETE FROM realm_type_override
WHERE slug = 'nom-serveur' AND region = 'eu'
"
```

---

## 🚨 Troubleshooting

### Problème : Un serveur n'est pas détecté

**Causes possibles :**
1. Le serveur n'est pas encore dans l'API Blizzard
2. Le namespace est incorrect
3. L'OAuth token a expiré

**Solutions :**
```bash
# Vérifier avec debug
php bin/console app:sync-blizzard-realms --region=eu --game-type=hardcore --debug-first=10

# Forcer une nouvelle authentification en redémarrant le processus
php bin/console app:sync-blizzard-realms --region=eu
```

---

### Problème : Mauvais type détecté malgré override

**Cause :** Cache ou ordre de synchronisation

**Solution :**
```bash
# Supprimer le serveur mal typé
php bin/console dbal:run-sql "DELETE FROM blizzard_game_realm WHERE slug = 'nom-serveur'"

# Vider le cache Doctrine si nécessaire
php bin/console cache:clear

# Re-synchroniser
php bin/console app:sync-blizzard-realms --region=eu
```

---

### Problème : Logs indiquent "No game type detected"

**Cause :** Serveur nouveau et inconnu du système

**Solution :**
```bash
# Vérifier quel est le serveur dans les logs
tail -f var/log/dev.log | grep "No game type detected"

# Ajouter aux métadonnées ou override selon le cas
php bin/console dbal:run-sql "
INSERT INTO known_realm_metadata (slug, region, expected_game_type, launch_date, source, confidence_score, notes, created_at)
VALUES ('nouveau-serveur', 'eu', 'hardcore', NOW(), 'manual_investigation', 7, 'Added after detection failure', NOW())
"
```

---

## 📚 Référence Rapide

### Commandes Principales

| Commande | Description |
|----------|-------------|
| `app:sync-blizzard-realms --region=eu` | Synchronise tous les serveurs EU |
| `app:sync-blizzard-realms --region=us --game-type=hardcore` | Synchronise uniquement Hardcore US |
| `app:populate-realm-metadata` | Peuple les métadonnées depuis le code |
| `app:populate-realm-metadata --clear` | Efface et repeuple les métadonnées |

### Types de Jeu

| Valeur | Description | Namespace |
|--------|-------------|-----------|
| `retail` | WoW Retail actuel | `dynamic-{region}` |
| `classic-era` | Classic vanilla (60 max) | `dynamic-classic-{region}` |
| `classic-progression` | Classic vanilla (progression) | `dynamic-classic-{region}` |
| `classic-anniversary` | Fresh servers 2024 | `dynamic-classic1x-{region}` |
| `season-of-discovery` | Season of Discovery | `dynamic-classic1x-{region}` |
| `hardcore` | Hardcore (mort permanente) | `dynamic-classic1x-{region}` |

### Régions

| Code | Nom |
|------|-----|
| `us` | North America + Oceania |
| `eu` | Europe |
| `kr` | Korea |
| `tw` | Taiwan |

---

## 📖 Voir Aussi

- [GETTING_STARTED_REALMS.md](./GETTING_STARTED_REALMS.md) - Guide de démarrage général
- [BLIZZARD_REALMS_ARCHITECTURE.md](./BLIZZARD_REALMS_ARCHITECTURE.md) - Architecture technique
- [DATABASE_SCHEMA.md](./DATABASE_SCHEMA.md) - Schéma de base de données
- [USAGE_EXAMPLES.md](./USAGE_EXAMPLES.md) - Exemples d'utilisation de l'API
