# 🗺️ Guild Tracker - Roadmap de développement

> Feuille de route complète pour le développement de l'application de gestion de guilde World of Warcraft

---

## 📋 Table des matières

- [Vue d'ensemble](#vue-densemble)
- [État actuel](#état-actuel)
- [Priorité 1 : Fonctionnalités Core](#priorité-1--fonctionnalités-core)
- [Priorité 2 : Fonctionnalités Gameplay](#priorité-2--fonctionnalités-gameplay)
- [Priorité 3 : Analytics & Rapports](#priorité-3--analytics--rapports)
- [Dette technique](#dette-technique)
- [Timeline recommandée](#timeline-recommandée)
- [Schéma de base de données](#schéma-de-base-de-données)

---

## 🎯 Vue d'ensemble

Guild Tracker est une application de gestion de guilde WoW construite avec :
- **Backend :** Symfony PHP
- **Frontend :** Vue.js 3 + TypeScript
- **Auth :** Discord OAuth
- **Base de données :** PostgreSQL/MySQL

### Objectif
Fournir aux guildes WoW un outil complet pour gérer leurs membres, organiser des raids, suivre le DKP/loot, et analyser les performances.

---

## ✅ État actuel

### Fonctionnalités complètes

#### Authentification
- [x] Discord OAuth intégration
- [x] Gestion des sessions utilisateur
- [x] JWT/session-based auth

#### Gestion de guilde
- [x] Création de guilde (nom, faction)
- [x] Liste des guildes de l'utilisateur
- [x] Vue détaillée d'une guilde
- [x] Sélection de guilde active

#### Gestion des personnages
- [x] Liste des personnages par guilde
- [x] Affichage des données (nom, classe, spec, rôle)
- [x] Filtres par classe et rôle
- [x] Recherche de personnages

#### Gestion des membres
- [x] Ajout/suppression de membres
- [x] Attribution de rôles (GM, Officier, Membre)
- [x] Liste avec recherche et pagination
- [x] Mise à jour des rôles via dropdown
- [x] Nettoyage automatique des personnages lors du retrait

### Fonctionnalités partielles

#### Personnages - CRUD incomplet
- [x] Création de personnages
- [x] Lecture des personnages
- [ ] **Édition de personnages** ⚠️
- [ ] **Suppression de personnages** ⚠️

#### Dashboard
- [x] Page existante
- [ ] **Contenu réel** (actuellement placeholder)

#### Assignations
- [x] Route créée
- [ ] **Implémentation complète** (page vide)

---

## 🔴 PRIORITÉ 1 : Fonctionnalités Core

### 1.1 Compléter la gestion des personnages ⚡

**Status :** 🟡 Partiellement implémenté
**Complexité :** 🟢 Faible (1-2 jours)
**Dépendances :** Aucune

#### Tâches

**Backend**
- [ ] Créer endpoint `PUT /api/guildcharacter/{id}` pour édition
- [ ] Créer endpoint `DELETE /api/guildcharacter/{id}` pour suppression
- [ ] Ajouter validation des données (form type)
- [ ] Implémenter checks d'autorisation

**Frontend**
- [ ] Créer composant `CharacterEditModal.vue`
- [ ] Ajouter fonctions `updateCharacter()` et `deleteCharacter()` au service
- [ ] Intégrer modal d'édition dans `ListPlayerView.vue`
- [ ] Ajouter dialog de confirmation pour suppression
- [ ] Gérer les messages d'erreur/succès

#### Fichiers concernés
```
backend/src/Controller/GuildCharacterController.php
backend/src/Form/GameCharacterType.php (à créer)
frontend/src/views/ListPlayerView.vue (lignes 130, 134)
frontend/src/components/CharacterEditModal.vue (à créer)
frontend/src/services/character.service.ts
```

---

### 1.2 Dashboard de guilde 📊

**Status :** 🔴 Non démarré
**Complexité :** 🟡 Moyenne (3-5 jours)
**Dépendances :** 1.1 recommandé

#### Fonctionnalités

**Statistiques générales**
- [ ] Nombre total de membres
- [ ] Nombre de personnages actifs
- [ ] Distribution tanks/healers/DPS
- [ ] Membres par rôle (GM/Officier/Membre)

**Visualisations**
- [ ] Graphique en donut pour composition de raid
- [ ] Graphique de distribution des classes
- [ ] Timeline d'activité récente

**Aperçu du roster**
- [ ] Liste des 5-10 derniers membres ajoutés
- [ ] Personnages récemment mis à jour
- [ ] Liens rapides vers fonctionnalités

#### Tâches

**Backend**
- [ ] Créer `GuildAnalyticsController.php`
- [ ] Créer endpoint `GET /api/guild/{id}/stats`
- [ ] Créer DTO `GuildStatsDTO.php`
- [ ] Implémenter requêtes d'agrégation
  - Compter personnages par rôle
  - Compter membres par permission
  - Récupérer activité récente

**Frontend**
- [ ] Compléter `GuildDashboardView.vue`
- [ ] Créer composants de statistiques
  - `StatCard.vue` (cartes avec chiffres)
  - `RoleDistributionChart.vue` (graphique composition)
  - `RecentActivityFeed.vue` (fil d'activité)
- [ ] Intégrer bibliothèque de charts (Chart.js ou ApexCharts)

#### Fichiers concernés
```
backend/src/Controller/GuildAnalyticsController.php (à créer)
backend/src/DTO/GuildStatsDTO.php (à créer)
frontend/src/views/GuildDashboardView.vue
frontend/src/components/dashboard/ (nouveaux composants)
frontend/src/services/analytics.service.ts (à créer)
```

---

### 1.3 Système d'assignation de roster 👥

**Status :** 🔴 Non démarré
**Complexité :** 🔴 Élevée (5-7 jours)
**Dépendances :** 1.1

#### Fonctionnalités

**Création de roster**
- [ ] Créer un nouveau roster (nom, type de raid)
- [ ] Définir le nombre de slots (10, 20, 25, 40 joueurs)
- [ ] Sauvegarder templates réutilisables

**Assignation de personnages**
- [ ] Interface drag & drop pour assigner personnages
- [ ] Validation de composition (min tanks/healers)
- [ ] Visualisation par rôle (tanks | healers | dps)
- [ ] Marquer personnages comme main/alt
- [ ] Statut de personnage (actif/bench/absence)

**Gestion**
- [ ] Dupliquer un roster
- [ ] Archiver/supprimer un roster
- [ ] Export vers format texte (pour Discord)
- [ ] Historique des compositions

#### Tâches

**Base de données**
- [ ] Créer migration pour table `raid_roster`
  - `id`, `guild_id`, `name`, `size`, `notes`, `created_at`
- [ ] Créer migration pour table `roster_assignment`
  - `id`, `roster_id`, `character_id`, `slot_number`, `role`
- [ ] Ajouter champ `is_main` à table `game_character`
- [ ] Ajouter enum `status` à table `game_character`

**Backend**
- [ ] Créer entité `RaidRoster.php`
- [ ] Créer entité `RosterAssignment.php`
- [ ] Créer `RaidRosterController.php`
  - Endpoint `POST /api/guild/{id}/rosters` (créer)
  - Endpoint `GET /api/guild/{id}/rosters` (lister)
  - Endpoint `GET /api/roster/{id}` (détails)
  - Endpoint `PUT /api/roster/{id}` (mettre à jour)
  - Endpoint `DELETE /api/roster/{id}` (supprimer)
  - Endpoint `POST /api/roster/{id}/assignments` (assigner)
- [ ] Créer DTO `RaidRosterDTO.php`
- [ ] Validation de composition (min/max tanks, healers)

**Frontend**
- [ ] Compléter `AssignementView.vue`
- [ ] Créer composants
  - `RosterBuilder.vue` (interface principale)
  - `RosterSlot.vue` (slot individuel drag-drop)
  - `CharacterPool.vue` (liste personnages disponibles)
  - `RosterTemplateSelector.vue` (sélection template)
- [ ] Implémenter drag & drop (utiliser VueDraggable)
- [ ] Créer service `roster.service.ts`
- [ ] Ajouter export texte pour Discord

#### Fichiers concernés
```
backend/migrations/VersionXXX_CreateRosterTables.php (à créer)
backend/src/Entity/RaidRoster.php (à créer)
backend/src/Entity/RosterAssignment.php (à créer)
backend/src/Controller/RaidRosterController.php (à créer)
backend/src/DTO/RaidRosterDTO.php (à créer)
frontend/src/views/AssignementView.vue
frontend/src/components/roster/ (nouveaux composants)
frontend/src/services/roster.service.ts (à créer)
```

---

## 🟡 PRIORITÉ 2 : Fonctionnalités Gameplay

### 2.1 Calendrier de raids & événements 📅

**Status :** 🔴 Non démarré
**Complexité :** 🔴 Élevée (5-7 jours)
**Dépendances :** Aucune (standalone)

#### Fonctionnalités

**Gestion d'événements**
- [ ] Créer événement de raid
  - Nom, description
  - Date et heure
  - Difficulté (Normal, Héroïque, Mythique)
  - Nombre de participants attendus
- [ ] Événements récurrents (hebdomadaires)
- [ ] Modifier/supprimer événements
- [ ] Filtrer par mois/semaine

**Système d'inscription (RSVP)**
- [ ] Inscription par personnage
- [ ] Statuts : Confirmé / Tentative / Décliné
- [ ] Voir qui est inscrit en temps réel
- [ ] Composition actuelle (tanks/healers/dps)
- [ ] Notifications pour inscrits

**Vue calendrier**
- [ ] Affichage mensuel
- [ ] Affichage hebdomadaire
- [ ] Vue liste des prochains événements
- [ ] Indicateurs visuels (full, places restantes)

#### Tâches

**Base de données**
- [ ] Créer migration pour table `raid_event`
  - `id`, `guild_id`, `name`, `description`
  - `event_date`, `duration`, `difficulty`
  - `max_participants`, `recurring_pattern`
  - `created_by`, `created_at`, `updated_at`
- [ ] Créer migration pour table `event_signup`
  - `id`, `event_id`, `character_id`
  - `status` (enum: confirmed/tentative/declined)
  - `signup_date`, `notes`

**Backend**
- [ ] Créer entité `RaidEvent.php`
- [ ] Créer entité `EventSignup.php`
- [ ] Créer enum `EventSignupStatus.php`
- [ ] Créer enum `RaidDifficulty.php`
- [ ] Créer `RaidEventController.php`
  - Endpoint `POST /api/guild/{id}/events` (créer)
  - Endpoint `GET /api/guild/{id}/events` (lister avec filtres)
  - Endpoint `GET /api/event/{id}` (détails + inscrits)
  - Endpoint `PUT /api/event/{id}` (modifier)
  - Endpoint `DELETE /api/event/{id}` (supprimer)
  - Endpoint `POST /api/event/{id}/signup` (s'inscrire)
  - Endpoint `PUT /api/signup/{id}` (changer statut)
- [ ] Créer DTO `RaidEventDTO.php`
- [ ] Créer DTO `EventSignupDTO.php`
- [ ] Logique récurrence d'événements

**Frontend**
- [ ] Compléter `RaidCalendarView.vue`
- [ ] Créer composants
  - `CalendarMonth.vue` (vue mensuelle)
  - `CalendarWeek.vue` (vue hebdomadaire)
  - `EventCard.vue` (carte événement)
  - `EventDetailModal.vue` (détails + inscription)
  - `EventCreateModal.vue` (création/édition)
  - `SignupList.vue` (liste inscrits)
- [ ] Intégrer bibliothèque calendrier (FullCalendar ou V-Calendar)
- [ ] Créer service `event.service.ts`

#### Fichiers concernés
```
backend/migrations/VersionXXX_CreateEventTables.php (à créer)
backend/src/Entity/RaidEvent.php (à créer)
backend/src/Entity/EventSignup.php (à créer)
backend/src/Enum/EventSignupStatus.php (à créer)
backend/src/Enum/RaidDifficulty.php (à créer)
backend/src/Controller/RaidEventController.php (à créer)
backend/src/DTO/RaidEventDTO.php (à créer)
frontend/src/views/RaidCalendarView.vue
frontend/src/components/calendar/ (nouveaux composants)
frontend/src/services/event.service.ts (à créer)
```

---

### 2.2 Import d'événements JSON 📤

**Status :** 🔴 Non démarré
**Complexité :** 🟡 Moyenne (2-3 jours)
**Dépendances :** 2.1 (Calendrier)

#### Fonctionnalités

**Import de fichiers**
- [ ] Upload fichier JSON
- [ ] Parser format custom ou exports d'addons WoW
- [ ] Validation structure JSON
- [ ] Prévisualisation données avant import
- [ ] Import en masse avec gestion d'erreurs
- [ ] Log des imports (succès/échecs)

**Formats supportés**
- [ ] Format custom Guild Tracker
- [ ] Export RCLootCouncil (optionnel)
- [ ] Export GroupCalendar (optionnel)

#### Tâches

**Backend**
- [ ] Créer `EventImportController.php`
  - Endpoint `POST /api/guild/{id}/events/import` (upload + preview)
  - Endpoint `POST /api/guild/{id}/events/import/confirm` (import final)
- [ ] Créer service `EventImportService.php`
  - Parser JSON
  - Validator JSON schema
  - Mapper vers entités `RaidEvent`
- [ ] Définir schéma JSON attendu
- [ ] Gestion des doublons (skip ou update)

**Frontend**
- [ ] Compléter `ImportEventsView.vue`
- [ ] Utiliser composant existant `EventJsonImportModal.vue`
- [ ] Créer composant `ImportPreview.vue`
- [ ] Drag & drop pour upload fichier
- [ ] Affichage des erreurs de parsing
- [ ] Créer service `import.service.ts`

#### Fichiers concernés
```
backend/src/Controller/EventImportController.php (à créer)
backend/src/Service/EventImportService.php (à créer)
frontend/src/views/ImportEventsView.vue
frontend/src/components/EventJsonImportModal.vue (existe déjà)
frontend/src/components/ImportPreview.vue (à créer)
frontend/src/services/import.service.ts (à créer)
```

#### Exemple de format JSON
```json
{
  "events": [
    {
      "name": "Raid Naxxramas",
      "date": "2025-11-03T20:00:00Z",
      "duration": 180,
      "difficulty": "Heroic",
      "maxParticipants": 25,
      "description": "Clear Naxx 25H"
    }
  ]
}
```

---

### 2.3 Système DKP/Points 💎

**Status :** 🔴 Non démarré
**Complexité :** 🔴 Très élevée (7-10 jours)
**Dépendances :** 2.1 (pour tracking présence)

#### Fonctionnalités

**Gestion de comptes DKP**
- [ ] Balance DKP par personnage
- [ ] Pools DKP multiples (par tier/raid)
- [ ] Historique complet des transactions
- [ ] Import/export données DKP (CSV)

**Attribution de DKP**
- [ ] Attribution manuelle (ajustements)
- [ ] Attribution automatique pour présence raid
- [ ] Attribution par boss kill
- [ ] Pénalités (absences, retards)
- [ ] Bonus (officicer, performance)

**Dépense de DKP**
- [ ] Enregistrer loot reçu
- [ ] Déduction automatique de DKP
- [ ] Prix personnalisés par item
- [ ] Prix par slot (tête, arme, etc.)
- [ ] Free loot (upgrade, disenchant)

**Règles avancées**
- [ ] Déclin DKP dans le temps (optionnel)
- [ ] Plafond de DKP maximum
- [ ] Plancher de DKP minimum
- [ ] Système de priorité de loot
  - Main spec > Off spec
  - BiS > upgrade mineur
- [ ] Reset DKP (nouveau tier)

**Rapports**
- [ ] Classement DKP de la guilde
- [ ] Historique par personnage
- [ ] Historique de loot
- [ ] Export pour Discord

#### Tâches

**Base de données**
- [ ] Créer migration pour table `dkp_pool`
  - `id`, `guild_id`, `name`, `description`
  - `decay_rate`, `max_balance`, `min_balance`
- [ ] Créer migration pour table `dkp_account`
  - `id`, `character_id`, `pool_id`, `balance`
- [ ] Créer migration pour table `dkp_transaction`
  - `id`, `account_id`, `amount`, `reason`
  - `transaction_type` (earn/spend/adjustment)
  - `event_id` (nullable), `created_by`, `created_at`
- [ ] Créer migration pour table `loot_record`
  - `id`, `character_id`, `item_name`, `item_id`
  - `dkp_spent`, `event_id`, `received_at`

**Backend**
- [ ] Créer entité `DkpPool.php`
- [ ] Créer entité `DkpAccount.php`
- [ ] Créer entité `DkpTransaction.php`
- [ ] Créer entité `LootRecord.php`
- [ ] Créer enum `TransactionType.php`
- [ ] Créer `DkpController.php`
  - CRUD pools
  - CRUD accounts
  - Endpoint `POST /api/dkp/transaction` (créer transaction)
  - Endpoint `GET /api/guild/{id}/dkp/standings` (classement)
  - Endpoint `GET /api/character/{id}/dkp/history` (historique)
  - Endpoint `POST /api/dkp/loot` (enregistrer loot)
  - Endpoint `GET /api/guild/{id}/loot/history` (historique loot)
  - Endpoint `POST /api/dkp/import` (import CSV)
  - Endpoint `GET /api/dkp/export` (export CSV)
- [ ] Créer service `DkpCalculationService.php`
  - Calculer DKP pour présence
  - Appliquer déclin
  - Valider transactions
- [ ] Créer DTOs (`DkpStandingsDTO.php`, etc.)

**Frontend**
- [ ] Compléter `DkpSystemView.vue`
- [ ] Créer composants
  - `DkpStandings.vue` (classement)
  - `DkpTransactionForm.vue` (créer transaction)
  - `LootRecordForm.vue` (enregistrer loot)
  - `DkpHistory.vue` (historique)
  - `LootHistory.vue` (historique loot)
  - `DkpPoolManager.vue` (gérer pools)
  - `DkpImportExport.vue` (import/export)
- [ ] Créer service `dkp.service.ts`

#### Fichiers concernés
```
backend/migrations/VersionXXX_CreateDkpTables.php (à créer)
backend/src/Entity/DkpPool.php (à créer)
backend/src/Entity/DkpAccount.php (à créer)
backend/src/Entity/DkpTransaction.php (à créer)
backend/src/Entity/LootRecord.php (à créer)
backend/src/Controller/DkpController.php (à créer)
backend/src/Service/DkpCalculationService.php (à créer)
frontend/src/views/DkpSystemView.vue
frontend/src/components/dkp/ (nouveaux composants)
frontend/src/services/dkp.service.ts (à créer)
```

---

## 🟢 PRIORITÉ 3 : Analytics & Rapports

### 3.1 Statistiques de raid 📈

**Status :** 🔴 Non démarré
**Complexité :** 🔴 Élevée (5-7 jours)
**Dépendances :** 2.1 (Événements), 2.3 (DKP optionnel)

#### Fonctionnalités

**Statistiques de présence**
- [ ] Taux de présence par personnage
- [ ] Taux de présence par joueur (tous persos)
- [ ] Présence sur période (mois, tier, saison)
- [ ] Classement membres les plus/moins actifs
- [ ] Présence par rôle

**Statistiques de progression**
- [ ] Boss kills par raid
- [ ] Temps moyen par boss
- [ ] Nombre de wipes avant kill
- [ ] Progression par difficulté

**Statistiques de loot**
- [ ] Distribution de loot par personnage
- [ ] Distribution de loot par classe/rôle
- [ ] Items les plus convoités
- [ ] Comparaison DKP dépensé vs loot reçu

**Visualisations**
- [ ] Graphiques de présence dans le temps
- [ ] Graphique de distribution de loot
- [ ] Heatmap d'activité
- [ ] Comparaisons visuelles

**Intégrations (optionnel)**
- [ ] Import logs WarcraftLogs
- [ ] Affichage DPS/HPS par joueur
- [ ] Parses de performance

#### Tâches

**Backend**
- [ ] Créer `RaidStatsController.php`
  - Endpoint `GET /api/guild/{id}/stats/attendance` (présence)
  - Endpoint `GET /api/character/{id}/stats/attendance` (présence perso)
  - Endpoint `GET /api/guild/{id}/stats/loot` (stats loot)
  - Endpoint `GET /api/guild/{id}/stats/progression` (progression)
- [ ] Créer service `RaidStatsService.php`
  - Calculer taux de présence
  - Agréger données loot
  - Parser logs WarcraftLogs (optionnel)
- [ ] Créer DTOs pour stats

**Frontend**
- [ ] Compléter `RaidStatsView.vue`
- [ ] Créer composants
  - `AttendanceChart.vue` (graphique présence)
  - `LootDistributionChart.vue` (répartition loot)
  - `ProgressionTimeline.vue` (progression)
  - `PlayerStatsCard.vue` (stats individuelles)
  - `AttendanceTable.vue` (tableau détaillé)
- [ ] Intégrer Chart.js ou ApexCharts
- [ ] Créer service `stats.service.ts`

#### Fichiers concernés
```
backend/src/Controller/RaidStatsController.php (à créer)
backend/src/Service/RaidStatsService.php (à créer)
backend/src/DTO/AttendanceStatsDTO.php (à créer)
frontend/src/views/RaidStatsView.vue
frontend/src/components/stats/ (nouveaux composants)
frontend/src/services/stats.service.ts (à créer)
```

---

### 3.2 Rapports de guilde 📋

**Status :** 🔴 Non démarré
**Complexité :** 🟡 Moyenne (3-5 jours)
**Dépendances :** 1.2 (Dashboard), 3.1 (Stats)

#### Fonctionnalités

**Rapports d'activité**
- [ ] Rapport hebdomadaire automatique
- [ ] Rapport mensuel automatique
- [ ] Activité par membre (connexions, raids)
- [ ] Évolution du roster dans le temps

**Tracking des changements**
- [ ] Log arrivées de membres
- [ ] Log départs de membres
- [ ] Log changements de rôle
- [ ] Log modifications importantes

**Métriques de guilde**
- [ ] Croissance de la guilde (graphique)
- [ ] Taux de rétention des membres
- [ ] Taux de rotation du roster
- [ ] Évolution de la composition (tanks/heal/dps)

**Export & partage**
- [ ] Export PDF
- [ ] Export CSV
- [ ] Partage via lien (optionnel)
- [ ] Envoi automatique Discord (optionnel)

#### Tâches

**Base de données**
- [ ] Créer migration pour table `guild_activity_log`
  - `id`, `guild_id`, `activity_type`
  - `description`, `metadata` (JSON)
  - `user_id`, `character_id` (nullable)
  - `created_at`
- [ ] Créer enum `ActivityType.php`
  - MEMBER_JOIN, MEMBER_LEAVE, ROLE_CHANGE
  - CHARACTER_ADD, CHARACTER_REMOVE
  - RAID_EVENT_CREATE, etc.

**Backend**
- [ ] Créer entité `GuildActivityLog.php`
- [ ] Créer `GuildReportsController.php`
  - Endpoint `GET /api/guild/{id}/reports/activity` (activité)
  - Endpoint `GET /api/guild/{id}/reports/growth` (croissance)
  - Endpoint `GET /api/guild/{id}/reports/weekly` (rapport hebdo)
  - Endpoint `GET /api/guild/{id}/reports/export` (export PDF/CSV)
- [ ] Créer service `ActivityLoggerService.php`
  - Méthodes pour logger chaque type d'activité
  - Injecter dans controllers existants
- [ ] Créer service `ReportGeneratorService.php`
  - Générer rapports PDF/CSV
- [ ] Créer DTOs pour rapports

**Frontend**
- [ ] Compléter `GuildReportsView.vue`
- [ ] Créer composants
  - `ActivityFeed.vue` (fil d'activité)
  - `GrowthChart.vue` (graphique croissance)
  - `WeeklyReport.vue` (rapport hebdo)
  - `ReportExporter.vue` (export)
- [ ] Créer service `reports.service.ts`

#### Fichiers concernés
```
backend/migrations/VersionXXX_CreateActivityLog.php (à créer)
backend/src/Entity/GuildActivityLog.php (à créer)
backend/src/Enum/ActivityType.php (à créer)
backend/src/Controller/GuildReportsController.php (à créer)
backend/src/Service/ActivityLoggerService.php (à créer)
backend/src/Service/ReportGeneratorService.php (à créer)
frontend/src/views/GuildReportsView.vue
frontend/src/components/reports/ (nouveaux composants)
frontend/src/services/reports.service.ts (à créer)
```

---

### 3.3 Notifications Discord 🔔

**Status :** 🔴 Non démarré
**Complexité :** 🟡 Moyenne (2-4 jours)
**Dépendances :** 2.1 (Événements recommandé)

#### Fonctionnalités

**Configuration**
- [ ] Configurer webhook Discord par guilde
- [ ] Tester webhook (bouton test)
- [ ] Activer/désactiver notifications globalement
- [ ] Activer/désactiver par type de notification

**Types de notifications**
- [ ] Nouveau membre rejoint la guilde
- [ ] Membre quitte/retiré de la guilde
- [ ] Changement de rôle (GM/Officier/Membre)
- [ ] Nouveau raid créé
- [ ] Rappel raid (X heures avant)
- [ ] Nouveau loot enregistré (optionnel)
- [ ] Transaction DKP importante (optionnel)

**Templates de messages**
- [ ] Templates personnalisables
- [ ] Variables disponibles : {guild}, {member}, {role}, etc.
- [ ] Prévisualisation du message
- [ ] Embeds Discord formatés

#### Tâches

**Base de données**
- [ ] Créer migration pour table `discord_webhook_config`
  - `id`, `guild_id`, `webhook_url`
  - `enabled`, `enabled_notifications` (JSON array)
  - `created_at`, `updated_at`

**Backend**
- [ ] Créer entité `DiscordWebhookConfig.php`
- [ ] Créer `DiscordNotificationController.php`
  - Endpoint `GET /api/guild/{id}/discord/config` (get config)
  - Endpoint `PUT /api/guild/{id}/discord/config` (save config)
  - Endpoint `POST /api/guild/{id}/discord/test` (test webhook)
- [ ] Créer service `DiscordNotificationService.php`
  - Méthode `sendNotification(type, data)`
  - Formater embeds Discord
  - Gestion des erreurs d'envoi
- [ ] Intégrer appels dans controllers existants
  - `GuildMembershipController` → notify on join/leave
  - `RaidEventController` → notify on event creation
  - etc.
- [ ] Créer templates de messages

**Frontend**
- [ ] Compléter `DiscordNotificationsView.vue`
- [ ] Créer composants
  - `WebhookConfigForm.vue` (configuration)
  - `NotificationTypeToggle.vue` (activer types)
  - `MessageTemplateEditor.vue` (éditer templates)
  - `NotificationPreview.vue` (prévisualisation)
- [ ] Créer service `discord.service.ts`

#### Fichiers concernés
```
backend/migrations/VersionXXX_CreateWebhookConfig.php (à créer)
backend/src/Entity/DiscordWebhookConfig.php (à créer)
backend/src/Controller/DiscordNotificationController.php (à créer)
backend/src/Service/DiscordNotificationService.php (à créer)
backend/src/Controller/GuildMembershipController.php (modifier)
backend/src/Controller/RaidEventController.php (modifier)
frontend/src/views/DiscordNotificationsView.vue
frontend/src/components/discord/ (nouveaux composants)
frontend/src/services/discord.service.ts (à créer)
```

#### Exemple de notification Discord
```json
{
  "embeds": [{
    "title": "Nouveau membre",
    "description": "**PlayerName** a rejoint la guilde !",
    "color": 3066993,
    "timestamp": "2025-11-03T12:00:00Z",
    "footer": {
      "text": "Guild Tracker"
    }
  }]
}
```

---

## 🔧 Dette technique

### Sécurité ⚠️

**Problème :** Checks d'autorisation commentés dans plusieurs controllers

#### Tâches
- [ ] Décommenter `denyAccessUnlessGranted()` dans tous les controllers
- [ ] Implémenter les Voters manquants
  - [ ] `GuildVoter.php` (GUILD_VIEW, GUILD_MANAGE)
  - [ ] `CharacterVoter.php` (CHARACTER_VIEW, CHARACTER_EDIT, CHARACTER_DELETE)
  - [ ] `RosterVoter.php` (ROSTER_VIEW, ROSTER_MANAGE)
  - [ ] `EventVoter.php` (EVENT_VIEW, EVENT_MANAGE)
  - [ ] `DkpVoter.php` (DKP_VIEW, DKP_MANAGE)
- [ ] Tester permissions pour tous les rôles (GM, Officier, Membre)

#### Fichiers concernés
```
backend/src/Controller/GuildCharacterController.php (ligne 43)
backend/src/Controller/GuildMembershipController.php (lignes 36, 53, 82)
backend/src/Security/Voter/ (à créer)
```

---

### Qualité de code

#### Tests
- [ ] Configurer PHPUnit pour backend
- [ ] Écrire tests unitaires pour services
- [ ] Écrire tests fonctionnels pour controllers
- [ ] Configurer Vitest pour frontend
- [ ] Écrire tests unitaires pour composants Vue
- [ ] Écrire tests E2E (Playwright/Cypress)

#### Documentation
- [ ] Ajouter documentation OpenAPI/Swagger
- [ ] Documenter schéma de base de données
- [ ] Ajouter PHPDoc dans le code
- [ ] Créer guide de contribution
- [ ] Documenter installation/déploiement

#### Standards
- [ ] Configurer PHP CS Fixer
- [ ] Configurer ESLint + Prettier
- [ ] Standardiser format des réponses API
- [ ] Standardiser gestion des erreurs
- [ ] Créer guide de style de code

---

### Gestion d'erreurs

#### Tâches
- [ ] Créer `ApiExceptionSubscriber.php`
- [ ] Standardiser format d'erreur JSON
```json
{
  "error": "Message d'erreur",
  "code": "ERROR_CODE",
  "status": 400,
  "violations": []
}
```
- [ ] Créer exceptions custom
  - [ ] `GuildNotFoundException`
  - [ ] `CharacterNotFoundException`
  - [ ] `UnauthorizedException`
  - [ ] `ValidationException`
- [ ] Améliorer messages d'erreur frontend
- [ ] Logger les erreurs serveur

---

## 📊 Schéma de base de données

### Tables existantes

#### `user`
- `id` (PK)
- `discord_id` (unique)
- `username`
- `avatar`
- `email`
- `roles` (JSON)

#### `game_guild`
- `id` (UUID, PK)
- `name`
- `faction` (Horde/Alliance)
- `nbr_guild_members`
- `nbr_characters`

#### `game_character`
- `id` (UUID, PK)
- `name`
- `class`
- `spec`
- `role` (Tank/Healer/DPS)
- `guild_id` (FK)

#### `guild_membership`
- `id` (PK)
- `user_id` (FK)
- `guild_id` (FK)
- `role` (enum: GM/Officer/Member)

---

### Tables à créer

#### Phase 1 (Priorité 1)

**`raid_roster`**
- `id` (PK)
- `guild_id` (FK)
- `name` (varchar 100)
- `size` (int) - 10, 20, 25, 40
- `notes` (text, nullable)
- `created_by` (FK user)
- `created_at` (timestamp)
- `updated_at` (timestamp)

**`roster_assignment`**
- `id` (PK)
- `roster_id` (FK)
- `character_id` (FK)
- `slot_number` (int) - position dans le roster
- `role` (varchar) - Tank, Healer, DPS

---

#### Phase 2 (Priorité 2)

**`raid_event`**
- `id` (PK)
- `guild_id` (FK)
- `name` (varchar 100)
- `description` (text, nullable)
- `event_date` (datetime)
- `duration` (int) - en minutes
- `difficulty` (enum: Normal/Heroic/Mythic)
- `max_participants` (int)
- `recurring_pattern` (varchar, nullable) - cron expression
- `created_by` (FK user)
- `created_at` (timestamp)
- `updated_at` (timestamp)

**`event_signup`**
- `id` (PK)
- `event_id` (FK)
- `character_id` (FK)
- `status` (enum: confirmed/tentative/declined)
- `notes` (text, nullable)
- `signup_date` (timestamp)

**`dkp_pool`**
- `id` (PK)
- `guild_id` (FK)
- `name` (varchar 100) - ex: "Naxxramas", "Ulduar"
- `description` (text, nullable)
- `decay_rate` (decimal, nullable) - % de déclin par semaine
- `max_balance` (int, nullable)
- `min_balance` (int, nullable)
- `created_at` (timestamp)

**`dkp_account`**
- `id` (PK)
- `character_id` (FK)
- `pool_id` (FK)
- `balance` (int, default 0)
- `updated_at` (timestamp)

**`dkp_transaction`**
- `id` (PK)
- `account_id` (FK)
- `amount` (int) - positif = gain, négatif = dépense
- `reason` (text)
- `transaction_type` (enum: earn/spend/adjustment)
- `event_id` (FK, nullable)
- `created_by` (FK user)
- `created_at` (timestamp)

**`loot_record`**
- `id` (PK)
- `character_id` (FK)
- `item_name` (varchar 255)
- `item_id` (int, nullable) - ID WoW item
- `dkp_spent` (int)
- `event_id` (FK, nullable)
- `received_at` (timestamp)

---

#### Phase 3 (Priorité 3)

**`guild_activity_log`**
- `id` (PK)
- `guild_id` (FK)
- `activity_type` (enum) - voir `ActivityType.php`
- `description` (text)
- `metadata` (JSON, nullable) - données supplémentaires
- `user_id` (FK, nullable)
- `character_id` (FK, nullable)
- `created_at` (timestamp)

**`discord_webhook_config`**
- `id` (PK)
- `guild_id` (FK, unique)
- `webhook_url` (text)
- `enabled` (boolean, default true)
- `enabled_notifications` (JSON) - array de types activés
- `created_at` (timestamp)
- `updated_at` (timestamp)

---

### Modifications aux tables existantes

#### `game_character` - ajouter
- `level` (int, nullable)
- `status` (enum: active/inactive/bench/absence, default active)
- `is_main` (boolean, default true)
- `item_level` (int, nullable)
- `created_at` (timestamp)
- `updated_at` (timestamp)

#### `game_guild` - ajouter
- `description` (text, nullable)
- `realm` (varchar 100, nullable) - nom du serveur WoW
- `region` (varchar 10, nullable) - US, EU, etc.
- `created_at` (timestamp)
- `updated_at` (timestamp)

#### `guild_membership` - ajouter
- `created_at` (timestamp)
- `updated_at` (timestamp)

---

## 📅 Timeline recommandée

### Phase 1 : Core Completion (2-3 semaines)
**Objectif :** Fonctionnalités de base 100% complètes

- **Semaine 1-2**
  - [ ] 1.1 Compléter CRUD personnages (2 jours)
  - [ ] 1.2 Dashboard de guilde (5 jours)
  - [ ] Dette technique : Activer sécurité (2 jours)

- **Semaine 3**
  - [ ] 1.3 Système de roster (7 jours)

**Livrable :** Application utilisable au quotidien pour gestion basique

---

### Phase 2 : Roster & Events (3-4 semaines)
**Objectif :** Planification et organisation de raids

- **Semaine 4-5**
  - [ ] 2.1 Calendrier de raids (7 jours)

- **Semaine 6**
  - [ ] 2.2 Import événements JSON (3 jours)
  - [ ] Tests et bugs Phase 2 (2 jours)

**Livrable :** Planification complète de raids avec inscriptions

---

### Phase 3 : DKP System (4-5 semaines)
**Objectif :** Gestion complète du loot

- **Semaine 7-9**
  - [ ] 2.3 Système DKP complet (10 jours)
  - [ ] Tests approfondis (5 jours)

**Livrable :** Système de loot fonctionnel et équitable

---

### Phase 4 : Analytics (2-3 semaines)
**Objectif :** Insights et rapports

- **Semaine 10-11**
  - [ ] 3.1 Statistiques de raid (7 jours)
  - [ ] 3.2 Rapports de guilde (5 jours)

- **Semaine 12**
  - [ ] 3.3 Notifications Discord (3 jours)
  - [ ] Polish et améliorations (2 jours)

**Livrable :** Application complète avec analytics

---

### Phase 5 : Polish & Production (2 semaines)
**Objectif :** Qualité production

- **Semaine 13**
  - [ ] Tests complets (end-to-end)
  - [ ] Documentation API
  - [ ] Guide utilisateur
  - [ ] Corrections de bugs

- **Semaine 14**
  - [ ] Optimisations performance
  - [ ] Audit sécurité
  - [ ] Préparation déploiement
  - [ ] Migration données (si nécessaire)

**Livrable :** Application production-ready

---

## 📌 Recommandations de développement

### Ordre de développement suggéré

1. **Commencer par P1.1** (CRUD personnages) - Quick win, débloque le reste
2. **Puis P1.2** (Dashboard) - Valeur immédiate pour les utilisateurs
3. **Puis P1.3** (Roster) - Feature complexe mais essentielle
4. **En parallèle** - Activer la sécurité (dette technique)
5. **Continuer P2** (Événements) - Standalone, peut se faire en parallèle
6. **Puis P2.3** (DKP) - Feature la plus complexe, nécessite les événements
7. **Finir P3** (Analytics) - Se base sur les données accumulées

### Bonnes pratiques

#### Pour chaque feature
1. **Migration BDD** d'abord
2. **Entités backend** ensuite
3. **Controllers & DTOs** après
4. **Tests backend** pour valider
5. **Services frontend** pour communication API
6. **Composants Vue** pour UI
7. **Tests frontend** pour composants
8. **Tests E2E** pour workflow complet

#### Git workflow
- Créer une branche par feature : `feature/1.1-character-crud`
- Commits réguliers et atomiques
- Pull requests avec review
- Merge dans `main` seulement si tests passent

#### Documentation
- Documenter chaque endpoint API (annotations Symfony)
- Commenter le code complexe
- Mettre à jour ce ROADMAP au fur et à mesure

---

## 🎯 KPIs de succès

### Métriques techniques
- [ ] 80%+ couverture de tests
- [ ] < 200ms temps de réponse API (p95)
- [ ] 0 vulnérabilités critiques
- [ ] 100% endpoints documentés

### Métriques produit
- [ ] 100% features P1 complètes
- [ ] 80%+ features P2 complètes
- [ ] Documentation utilisateur complète
- [ ] < 5 bugs critiques en production

---

## 📝 Notes

### Technologies à considérer

**Frontend**
- **Charts :** Chart.js ou ApexCharts
- **Calendrier :** FullCalendar ou V-Calendar
- **Drag & Drop :** VueDraggable
- **Date handling :** Day.js
- **Forms :** VeeValidate

**Backend**
- **PDF Generation :** TCPDF ou mPDF
- **CSV Export :** League CSV
- **Caching :** Redis (recommandé)
- **Queue :** Symfony Messenger (pour notifications)

### Intégrations futures
- [ ] WarcraftLogs API (performance raid)
- [ ] Blizzard API (données personnages)
- [ ] Raider.IO API (M+ scores)
- [ ] Discord Bot (au lieu de webhooks)

---

## 📞 Support & Contribution

Pour contribuer à ce projet :
1. Fork le repository
2. Créer une branche feature
3. Suivre les guidelines de ce ROADMAP
4. Soumettre une pull request

---

**Dernière mise à jour :** 2025-10-27
**Version :** 1.0.0
**Statut :** 🟡 En développement actif
