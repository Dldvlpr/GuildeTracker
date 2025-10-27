# 🗺️ Guild Tracker - Roadmap Complète de Développement

> **Feuille de route repensée et optimisée pour le développement de l'application de gestion de guilde World of Warcraft**  
> Basée sur une analyse approfondie du code existant, des besoins réels des guildes, et des principes de transparence

---

## 📋 Table des matières

1. [Vision & Principes](#-vision--principes)
2. [Personas & Permissions](#-personas--permissions)
3. [État Actuel](#-état-actuel)
4. [Phase 0 : Fondations](#-phase-0--fondations-critiques)
5. [Phase 1 : Core Guild Management](#-phase-1--core-guild-management)
6. [Phase 2 : Planification de Raids](#-phase-2--planification-de-raids)
7. [Phase 3 : Système DKP & Loot](#-phase-3--système-dkp--loot)
8. [Phase 4 : Analytics & Insights](#-phase-4--analytics--insights)
9. [Phase 5 : Communication](#-phase-5--communication--intégrations)
10. [Schéma Base de Données](#-schéma-base-de-données-complet)
11. [API Endpoints](#-api-endpoints-complets)
12. [Flows Utilisateurs](#-flows-utilisateurs-détaillés)
13. [Timeline & Estimation](#-timeline--estimation)

---

## 🎯 Vision & Principes

### Vision Statement

**Guild Tracker est une plateforme de gestion de guilde WoW qui place la TRANSPARENCE au coeur de son fonctionnement. Tous les membres d'une guilde peuvent voir les personnages, les points DKP, et l'historique de loot - créant ainsi un environnement de confiance et d'équité.**

### Principes Fondamentaux

#### 1. **Transparence par Défaut** 🌐
- Les standings DKP sont **PUBLICS** (ou visibles par tous les membres)
- L'historique de loot est **VISIBLE** par tous
- Les rosters et compositions sont **PARTAGÉS**
- Les statistiques de participation sont **ACCESSIBLES**

**Pourquoi ?** La transparence élimine les soupçons de favoritisme et crée de la confiance.

#### 2. **Permissions Basées sur les Rôles** 🔐
- **GM (Guild Master)** : Contrôle total de la guilde
- **Officer** : Gère le gameplay (événements, DKP, loot) mais pas les membres
- **Member** : Voir toutes les données, gérer ses propres personnages
- **Public** : Accès limité aux informations de recrutement

#### 3. **Centré sur le Workflow Réel** 🎮
Les features sont organisées autour de workflows réels :
1. **Planifier un raid** : Créer événement → Assigner roster → Membres s'inscrivent
2. **Distribuer le loot** : Raid terminé → Officer enregistre loot → DKP déduits automatiquement
3. **Analyser la guilde** : Dashboard → Stats de participation → Rapports

#### 4. **Architecture Multi-Guilde** 👥
- Un utilisateur peut appartenir à plusieurs guildes
- Changement rapide entre guildes
- Personnages liés à une seule guilde (comme dans WoW)

---

## 👤 Personas & Permissions

### Personas Définis

#### 🌐 Public Guest (Non Connecté)
**Qui :** Visiteur externe, recrue potentielle, curieux

**Peut voir :**
- ✅ Page publique de guilde (si activée par GM)
- ✅ Roster de personnages (noms, classes, rôles)
- ✅ Standings DKP (si show_dkp_public = true)
- ✅ Événements à venir
- ✅ Statut de recrutement

**Ne peut PAS :**
- ❌ Voir les propriétaires de personnages
- ❌ Voir l'historique détaillé
- ❌ Interagir avec la guilde

**Use Case :** "Je cherche une guilde Horde qui raid Mardi/Jeudi et qui a besoin d'un Paladin Heal. Je veux voir leur progression et leur roster."

---

#### 🔓 Authenticated User (Non-Membre)
**Qui :** Utilisateur connecté via Discord mais pas dans la guilde

**Peut faire :**
- ✅ Créer sa propre guilde (devient GM)
- ✅ Voir les guildes publiques
- ✅ (Futur) Postuler à une guilde

**Use Case :** "Je viens de me connecter et je veux créer une guilde pour mon équipe de raid."

---

#### 👤 Guild Member (Rôle: Member)
**Qui :** Raider standard de la guilde

**Peut voir (TOUT) :**
- ✅ Roster complet avec tous les personnages
- ✅ **Standings DKP complets** (tous les membres)
- ✅ Historique DKP de tous
- ✅ Historique de loot de tous
- ✅ Tous les événements de raid
- ✅ Dashboard et statistiques
- ✅ Rapports de guilde

**Peut gérer :**
- ✅ Ses propres personnages (CRUD)
- ✅ S'inscrire aux raids avec ses persos
- ✅ Voir son historique DKP

**Ne peut PAS :**
- ❌ Ajouter/retirer des membres
- ❌ Créer des événements
- ❌ Attribuer du DKP
- ❌ Enregistrer du loot

**Use Case :** "Je veux ajouter mon alt, m'inscrire au raid de ce soir, et vérifier combien de DKP j'ai."

---

#### ⭐ Guild Officer (Rôle: Officer)
**Qui :** Raid leader, class leader, officier de confiance

**Peut faire (+ tout ce que Member fait) :**
- ✅ Créer/éditer/supprimer des événements de raid
- ✅ Créer et gérer des rosters
- ✅ Attribuer du DKP (présence, performance)
- ✅ Enregistrer du loot reçu
- ✅ Voir analytics détaillés
- ✅ Envoyer notifications Discord

**Ne peut PAS :**
- ❌ Ajouter/retirer des membres de la guilde
- ❌ Changer les rôles des membres
- ❌ Configurer les paramètres de la guilde
- ❌ Supprimer la guilde

**Use Case :** "Je dois créer le raid de ce soir, assigner le roster, puis après le raid distribuer les DKP et enregistrer le loot."

---

#### 👑 Guild Master (Rôle: GM)
**Qui :** Leader de la guilde, contrôle total

**Peut faire (+ tout ce qu'Officer fait) :**
- ✅ Ajouter/retirer des membres
- ✅ Promouvoir/rétrograder (Member ↔ Officer)
- ✅ Transférer le rôle de GM
- ✅ Configurer les settings de guilde
  - Pools DKP (decay rate, max/min)
  - Visibilité publique
  - Webhooks Discord
- ✅ Supprimer la guilde

**Use Case :** "Je veux promouvoir notre nouveau raid leader en Officer, configurer le decay DKP à 5% par semaine, et setup les notifications Discord."

---

### 🔒 Matrice de Permissions CRUD Complète

| Ressource | CREATE | READ | UPDATE | DELETE | Règles Spéciales |
|-----------|--------|------|--------|--------|------------------|
| **Guild** | Any auth user | **PUBLIC**: Basic info<br>Member: Full | GM only | GM only | - |
| **Membership** | GM only | Public: Count<br>Member: Full list | GM only (roles) | GM only | Cannot remove last GM |
| **Characters** | Member (own) | **PUBLIC**: Basic<br>Member: Full roster | Owner or GM | Owner or GM | Must belong to guild |
| **Events** | Officer+ | **PUBLIC**: Upcoming<br>Member: All | Officer+ (creator) | Officer+ (creator) | - |
| **Signups** | Member (own chars) | Member: View all | Member (own)<br>Officer+ (any) | Member (own)<br>Officer+ (any) | - |
| **Rosters** | Officer+ | Member: View | Officer+ | Officer+ | - |
| **DKP Standings** | Auto-calc | **PUBLIC** or Member | - | - | **KEY: TRANSPARENT** |
| **DKP Transactions** | Officer+ | **PUBLIC** or Member | GM only (adjustments) | GM only (corrections) | Immutable audit trail |
| **Loot Records** | Officer+ | **PUBLIC** or Member | Officer+ (24h window) | GM only | Shows fairness |
| **Discord Config** | GM | GM only | GM only | GM only | Contains webhook URL |

---

## ✅ État Actuel

### Ce qui Existe et Fonctionne

#### ✅ Authentification (100%)
- Discord OAuth complet
- Session management
- User entity avec Discord ID

#### ✅ Guildes - Base (80%)
- Création de guilde ✅
- Liste des guildes ✅
- Détails guilde ✅
- **MANQUE** : Settings guilde (is_public, show_dkp_public)

#### ✅ Personnages - Partiel (60%)
- CREATE ✅
- READ ✅ (liste, filtres, recherche)
- UPDATE ❌ **MANQUANT**
- DELETE ❌ **MANQUANT**

#### ✅ Membres - Complet (90%)
- Ajout membre ✅
- Suppression membre ✅
- Changement rôle ✅
- Liste avec search/pagination ✅
- **MANQUE** : Système d'invitation

#### ❌ Features Manquantes (0%)
- Dashboard (page vide)
- Rosters/Assignations (page vide)
- Calendrier raids (page vide)
- DKP System (page vide)
- Stats & Reports (page vide)
- Discord notifications (page vide)

### Problèmes Critiques à Régler

#### 🔴 CRITIQUE: Pas de Modèle de Permissions Public
**Problème :** Tout requiert une authentification. Impossible de :
- Voir une page de guilde publique
- Consulter les standings DKP sans login
- Partager le roster avec des recrues

**Solution :** Phase 0 - Créer routes publiques + voter system

#### 🔴 CRITIQUE: Permissions Commentées
**Problème :** Les checks `denyAccessUnlessGranted()` sont commentés partout

**Fichiers :**
```
GuildCharacterController.php:43  // $this->denyAccessUnlessGranted('CHARACTER_VIEW', $character);
GuildMembershipController.php:36 // $this->denyAccessUnlessGranted('GUILD_MANAGE', $guild);
```

**Solution :** Phase 0 - Implémenter tous les Voters

#### 🟡 IMPORTANT: Ownership de Personnages Flou
**Problème :** `game_character.user_player_id` est nullable. Qui possède les personnages?

**Solution :** Phase 0 - Clarifier le modèle (permettre nullable pour imports, mais require verification)

---

## 🔨 Phase 0 : Fondations Critiques

**Objectif :** Réparer l'architecture avant d'ajouter des features  
**Durée :** 1 semaine  
**Priorité :** 🔴 CRITIQUE

### F0.1 - Système de Permissions Complet

#### Backend Tasks
- ✅ **Créer tous les Voters** (backend/src/Security/Voter/)
  - ✅ `GuildVoter.php`
    - `GUILD_VIEW` : Member OR guild.is_public
    - `GUILD_MANAGE` : GM only
    - `GUILD_DELETE` : GM only
  - ✅ `CharacterVoter.php`
    - `CHARACTER_VIEW` : Public (basic) OR Member (full)
    - `CHARACTER_CREATE` : Must be member
    - `CHARACTER_EDIT` : Owner OR GM
    - `CHARACTER_DELETE` : Owner OR GM
  - ✅ `MembershipVoter.php`
    - `MEMBERSHIP_VIEW` : Member
    - `MEMBERSHIP_MANAGE` : GM only
  
- [ ] **Décommenter tous les checks d'autorisation**
  - [ ] GuildCharacterController (ligne 43)
  - [ ] GuildMembershipController (lignes 36, 53, 82)
  - [ ] Tous les autres controllers

- [ ] **Tester permissions pour chaque rôle**
  - [ ] Créer fixtures avec User GM, Officer, Member
  - [ ] Tests fonctionnels pour chaque endpoint

#### Fichiers à Modifier
```
backend/src/Security/Voter/GuildVoter.php (à créer)
backend/src/Security/Voter/CharacterVoter.php (à créer)
backend/src/Security/Voter/MembershipVoter.php (à créer)
backend/src/Controller/*.php (décommenter checks)
backend/tests/Functional/SecurityTest.php (à créer)
```

**Tests de Validation :**
- [ ] GM peut tout faire
- [ ] Officer ne peut pas gérer les membres
- [ ] Member ne peut éditer que ses propres chars
- [ ] Non-membre ne peut pas voir guilde privée

---

### F0.2 - Routes Publiques & Visibilité

#### Backend Tasks
- ✅ **Ajouter champs à game_guild**
  - [ ] `is_public` (boolean, default true)
  - [ ] `show_dkp_public` (boolean, default true)
  - [ ] `recruiting_status` (enum: open/closed/trial)
  - [ ] Migration

- [ ] **Créer PublicGuildController.php**
  - [ ] `GET /api/public/guilds` (liste guildes publiques)
  - [ ] `GET /api/public/guild/{id}` (détails, **NO AUTH**)
  - [ ] `GET /api/public/guild/{id}/roster` (persos, **NO AUTH**)
  - [ ] `GET /api/public/guild/{id}/dkp` (standings si show_dkp_public)

- [ ] **Créer PublicGuildDTO.php**
  - Exclure infos sensibles (discord IDs, emails)
  - Inclure : name, faction, realm, roster count, recruiting_status

#### Frontend Tasks
- [ ] **Créer route publique**
  - [ ] `/public/guild/:id` (accessible sans login)
  
- [ ] **Créer PublicGuildView.vue**
  - [ ] Header avec nom, faction, stats
  - [ ] Roster public (tableau characters)
  - [ ] DKP standings (si activé)
  - [ ] Bouton "Apply to Join" (futur)

- [ ] **Créer service public**
  - [ ] `publicGuild.service.ts` (pas de auth header)

#### Fichiers
```
backend/migrations/VersionXXX_AddGuildVisibility.php
backend/src/Controller/PublicGuildController.php
backend/src/DTO/PublicGuildDTO.php
frontend/src/views/PublicGuildView.vue
frontend/src/services/publicGuild.service.ts
frontend/src/router/index.ts (add public route)
```

**Tests de Validation :**
- [ ] Visiteur non-loggué peut voir guilde publique
- [ ] Visiteur ne peut pas voir guilde privée (is_public=false)
- [ ] DKP visible seulement si show_dkp_public=true

---

### F0.3 - Compléter CRUD Personnages

#### Backend Tasks
- [ ] **Créer GameCharacterType.php** (form)
  - Validation : name (required, max 255)
  - Validation : class (enum WoW classes)
  - Validation : spec, role

- [ ] **Ajouter endpoints manquants**
  - [ ] `PUT /api/character/{id}` (update)
    - Check CharacterVoter: EDIT
    - Validation via form
  - [ ] `DELETE /api/character/{id}` (delete)
    - Check CharacterVoter: DELETE
    - Cascade: remove signups, DKP accounts, loot records

#### Frontend Tasks
- [ ] **Créer CharacterEditModal.vue**
  - Reprendre CharacterForm existant
  - Pré-remplir avec données actuelles
  - Submit → PUT endpoint

- [ ] **Modifier ListPlayerView.vue**
  - [ ] Dé-commenter bouton Edit (ligne 130)
  - [ ] Remplacer toast par openEditModal()
  - [ ] Bouton Delete → confirmation dialog
  - [ ] Gérer erreurs (non-owner, etc.)

- [ ] **Mettre à jour character.service.ts**
  - [ ] `updateCharacter(id, data)`
  - [ ] `deleteCharacter(id)`

#### Fichiers
```
backend/src/Form/GameCharacterType.php
backend/src/Controller/GuildCharacterController.php (add PUT, DELETE)
frontend/src/components/CharacterEditModal.vue
frontend/src/views/ListPlayerView.vue
frontend/src/services/character.service.ts
```

**Tests de Validation :**
- [ ] Member peut éditer son propre personnage
- [ ] Member ne peut PAS éditer perso d'un autre
- [ ] GM peut éditer n'importe quel personnage
- [ ] Suppression cascade les relations

---

### F0.4 - Améliorer Modèle de Données

#### Backend Tasks
- [ ] **Ajouter champs à game_character**
  - [ ] `level` (int, nullable)
  - [ ] `item_level` (int, nullable)
  - [ ] `is_main` (boolean, default true)
  - [ ] `status` (enum: active/inactive/bench/absence, default active)
  - [ ] `created_at`, `updated_at`
  - [ ] Migration

- [ ] **Créer enum CharacterStatus.php**
  ```php
  enum CharacterStatus: string {
      case ACTIVE = 'active';
      case INACTIVE = 'inactive';
      case BENCH = 'bench';
      case ABSENCE = 'absence';
  }
  ```

- [ ] **Ajouter champs à game_guild**
  - [ ] `description` (text)
  - [ ] `realm` (string)
  - [ ] `region` (string: US/EU/KR/TW)
  - [ ] `created_at`, `updated_at`
  - [ ] Migration

- [ ] **Ajouter timestamps à guild_membership**
  - [ ] `joined_at`, `left_at` (nullable)
  - [ ] Migration

#### Fichiers
```
backend/migrations/VersionXXX_EnhanceEntities.php
backend/src/Entity/GameCharacter.php
backend/src/Entity/GameGuild.php
backend/src/Entity/GuildMembership.php
backend/src/Enum/CharacterStatus.php
```

**Tests de Validation :**
- [ ] Nouveaux champs sont bien enregistrés
- [ ] Defaults appliqués correctement
- [ ] Enum CharacterStatus fonctionne

---

### Livrable Phase 0
✅ Permissions fonctionnelles pour tous les rôles  
✅ Routes publiques pour visibilité guilde  
✅ CRUD personnages complet  
✅ Modèle de données robuste  

**→ Base solide pour Phase 1**

---

## 🏰 Phase 1 : Core Guild Management

**Objectif :** Gestion quotidienne de la guilde  
**Durée :** 2-3 semaines  
**Priorité :** 🔴 HAUTE

### F1.1 - Dashboard Guilde avec Vraies Données

**User Story :** "En tant que GM, je veux voir en un coup d'oeil la santé de ma guilde : combien de membres, quelle composition, qui est actif."

#### Fonctionnalités
- 📊 Cartes statistiques
  - Nombre total membres
  - Nombre personnages actifs
  - Nombre total de raids ce mois
  - Taux de présence moyen
  
- 📈 Graphiques
  - Distribution Tanks/Healers/DPS (pie chart)
  - Distribution par classe (bar chart)
  - Activité récente (timeline)

- 📋 Aperçus
  - 5 derniers membres ajoutés
  - 5 prochains raids
  - Activité récente (feed)

#### Backend Tasks
- [ ] **Créer GuildAnalyticsController.php**
  - [ ] `GET /api/guild/{id}/analytics/dashboard`
    - Vérifier : Member
    - Retourne : DashboardStatsDTO
  
- [ ] **Créer DashboardStatsDTO.php**
  ```php
  {
    memberCount: int,
    characterCount: int,
    activeCharacterCount: int,
    roleDistribution: {tank: int, healer: int, melee: int, ranged: int},
    classDistribution: {Warrior: int, Paladin: int, ...},
    recentMembers: Member[],
    upcomingEvents: Event[] (future),
    recentActivity: Activity[] (future)
  }
  ```

- [ ] **Créer GuildAnalyticsService.php**
  - Méthode `calculateDashboardStats(Guild $guild): array`
  - Queries d'agrégation optimisées

#### Frontend Tasks
- [ ] **Compléter GuildDashboardView.vue**
  - Remplacer placeholder par vraies données
  - Layout en grid (2-3 colonnes)

- [ ] **Créer composants dashboard/** 
  - [ ] `StatCard.vue` (carte avec icône + nombre)
  - [ ] `RoleDistributionChart.vue` (pie chart avec Chart.js)
  - [ ] `ClassDistributionChart.vue` (bar chart)
  - [ ] `RecentMembersList.vue` (liste avec avatars)
  - [ ] `ActivityFeedItem.vue` (item de timeline)

- [ ] **Créer analytics.service.ts**
  - `getDashboardStats(guildId)`

- [ ] **Installer Chart.js**
  ```bash
  npm install chart.js vue-chartjs
  ```

#### Fichiers
```
backend/src/Controller/GuildAnalyticsController.php
backend/src/Service/GuildAnalyticsService.php
backend/src/DTO/DashboardStatsDTO.php
frontend/src/views/GuildDashboardView.vue
frontend/src/components/dashboard/StatCard.vue
frontend/src/components/dashboard/RoleDistributionChart.vue
frontend/src/components/dashboard/ClassDistributionChart.vue
frontend/src/components/dashboard/RecentMembersList.vue
frontend/src/services/analytics.service.ts
package.json (add chart.js)
```

**Tests de Validation :**
- [ ] Dashboard affiche les bonnes stats
- [ ] Graphiques se render correctement
- [ ] Membres peuvent voir le dashboard
- [ ] Non-membres ne peuvent pas (sauf si public)

---

### F1.2 - Système de Roster Builder

**User Story :** "En tant qu'Officer, je veux créer des compositions de raid en glissant des personnages dans des slots, pour planifier mes encounters."

#### Fonctionnalités
- 🎯 Créer un roster
  - Nom (ex: "Naxx 25 - Week 1")
  - Taille (10, 20, 25, 40)
  - Notes

- 🎨 Interface Drag & Drop
  - Slots organisés par rôle : [Tanks] [Healers] [DPS]
  - Pool de personnages disponibles
  - Validation : min 2 tanks, 3-5 healers pour 25man

- 💾 Sauvegarder & Réutiliser
  - Sauvegarder comme template
  - Dupliquer un roster existant
  - Exporter en texte (Discord-ready)

#### Backend Tasks
- [ ] **Créer migrations tables**
  ```sql
  CREATE TABLE raid_roster (
    id UUID PRIMARY KEY,
    guild_id UUID NOT NULL REFERENCES game_guild(id),
    name VARCHAR(255) NOT NULL,
    size INT NOT NULL,
    notes TEXT,
    created_by UUID REFERENCES user(id),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
  );

  CREATE TABLE roster_assignment (
    id UUID PRIMARY KEY,
    roster_id UUID REFERENCES raid_roster(id) ON DELETE CASCADE,
    character_id UUID REFERENCES game_character(id) ON DELETE CASCADE,
    slot_number INT NOT NULL,
    assigned_role VARCHAR(50)
  );
  ```

- [ ] **Créer entités**
  - [ ] `RaidRoster.php`
    - Relation oneToMany avec RosterAssignment
    - Relation manyToOne avec Guild
  - [ ] `RosterAssignment.php`
    - Relation manyToOne avec RaidRoster
    - Relation manyToOne avec GameCharacter

- [ ] **Créer RaidRosterController.php**
  - [ ] `POST /api/guild/{id}/rosters`
    - Check : Officer+
    - Crée roster vide
  - [ ] `GET /api/guild/{id}/rosters`
    - Check : Member
    - Liste tous les rosters
  - [ ] `GET /api/roster/{id}`
    - Check : Member
    - Détails + assignments
  - [ ] `PUT /api/roster/{id}/assignments`
    - Check : Officer+
    - Body: `{assignments: [{characterId, slotNumber, role}]}`
    - Update en batch
  - [ ] `DELETE /api/roster/{id}`
    - Check : Officer+
    - Cascade delete assignments

- [ ] **Créer RosterValidationService.php**
  - Méthode `validateComposition(size, assignments): ValidationResult`
  - Règles :
    - 10man : min 1 tank, 2 healers
    - 25man : min 2 tanks, 5 healers

- [ ] **Créer RaidRosterDTO.php**
  ```php
  {
    id: string,
    name: string,
    size: int,
    notes: string,
    assignments: [{
      characterId: string,
      characterName: string,
      class: string,
      role: string,
      slotNumber: int
    }],
    validation: {
      valid: bool,
      tankCount: int,
      healerCount: int,
      dpsCount: int,
      warnings: string[]
    }
  }
  ```

#### Frontend Tasks
- [ ] **Compléter AssignementView.vue**
  - Layout : Toolbar + RosterBuilder

- [ ] **Créer composants roster/**
  - [ ] `RosterBuilder.vue` (conteneur principal)
    - Sections : Tanks | Healers | DPS
    - Character pool en bas
  - [ ] `RosterSlot.vue` (slot drop zone)
    - Affiche character si assigné
    - Drag handle pour re-order
  - [ ] `CharacterPoolItem.vue` (item draggable)
    - Avatar + nom + classe + rôle
  - [ ] `RosterSidebar.vue` (liste rosters)
    - Créer nouveau
    - Sélectionner existant
  - [ ] `RosterValidationBanner.vue` (warnings)
    - "Besoin d'au moins 1 tank de plus"

- [ ] **Installer VueDraggable**
  ```bash
  npm install vuedraggable@next
  ```

- [ ] **Créer roster.service.ts**
  - `createRoster(guildId, data)`
  - `getRosters(guildId)`
  - `getRoster(id)`
  - `updateAssignments(id, assignments)`
  - `deleteRoster(id)`
  - `exportToText(roster)` → format Discord

#### Fichiers
```
backend/migrations/VersionXXX_CreateRosterTables.php
backend/src/Entity/RaidRoster.php
backend/src/Entity/RosterAssignment.php
backend/src/Controller/RaidRosterController.php
backend/src/Service/RosterValidationService.php
backend/src/DTO/RaidRosterDTO.php
frontend/src/views/AssignementView.vue
frontend/src/components/roster/RosterBuilder.vue
frontend/src/components/roster/RosterSlot.vue
frontend/src/components/roster/CharacterPoolItem.vue
frontend/src/components/roster/RosterSidebar.vue
frontend/src/components/roster/RosterValidationBanner.vue
frontend/src/services/roster.service.ts
package.json (add vuedraggable)
```

**Tests de Validation :**
- [ ] Officer peut créer roster
- [ ] Drag & drop fonctionne
- [ ] Validation composition OK
- [ ] Export texte format Discord
- [ ] Member peut voir mais pas éditer

---

### F1.3 - Système d'Invitation de Membres

**User Story :** "En tant que GM, je veux inviter des utilisateurs à rejoindre ma guilde via leur Discord ID."

#### Fonctionnalités
- 📨 Inviter par Discord ID ou username
- ✅ Accepter/Refuser invitation (futur)
- 📋 Liste des invitations pendantes

#### Backend Tasks (Simplifié pour MVP)
- [ ] **Modifier GuildMembershipController**
  - [ ] `POST /api/guild/{id}/members/invite`
    - Check : GM only
    - Body : `{discordId: string, role: 'Member'}`
    - Chercher User par discordId
    - Créer GuildMembership directement
    - (Futur : créer Invitation pending)

#### Frontend Tasks
- [ ] **Créer InviteMemberModal.vue**
  - Input : Discord ID
  - Select : Role (default Member)
  - Bouton : Send Invite

- [ ] **Modifier GuildRolesView.vue**
  - Ajouter bouton "Invite Member"
  - Ouvre InviteMemberModal

#### Fichiers (Simplifié)
```
backend/src/Controller/GuildMembershipController.php (modify)
frontend/src/components/InviteMemberModal.vue
frontend/src/views/GuildRolesView.vue
```

**Tests de Validation :**
- [ ] GM peut inviter user existant
- [ ] Officer ne peut PAS inviter
- [ ] Error si user déjà membre

---

### Livrable Phase 1
✅ Dashboard fonctionnel avec stats réelles  
✅ Roster builder drag & drop  
✅ Système d'invitation membres  

**→ Guilde gérable au quotidien**

---

## 📅 Phase 2 : Planification de Raids

**Objectif :** Créer et organiser des raids, gérer les inscriptions  
**Durée :** 3-4 semaines  
**Priorité :** 🟡 MOYENNE-HAUTE

### F2.1 - Calendrier de Raids & Événements

**User Story :** "En tant qu'Officer, je crée un raid Naxx25H pour Mercredi 20h. Les membres s'inscrivent avec leur main ou alt."

#### Fonctionnalités
- 📆 Calendrier visuel (mois / semaine)
- ➕ Créer événement
  - Nom, description
  - Date/heure, durée
  - Difficulté (Normal/Heroic/Mythic)
  - Max participants
- 🔁 Événements récurrents (ex: tous les Mercredis)
- ✅ Système d'inscription (RSVP)
  - Confirmed / Tentative / Declined
  - Par personnage
  - Voir composition actuelle

#### Backend Tasks
- [ ] **Créer migrations**
  ```sql
  CREATE TABLE raid_event (
    id UUID PRIMARY KEY,
    guild_id UUID REFERENCES game_guild(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    event_date TIMESTAMP NOT NULL,
    duration INT DEFAULT 180,
    difficulty VARCHAR(50),
    max_participants INT DEFAULT 25,
    recurring_pattern VARCHAR(100),
    created_by UUID REFERENCES user(id),
    created_at TIMESTAMP,
    updated_at TIMESTAMP
  );

  CREATE TABLE event_signup (
    id UUID PRIMARY KEY,
    event_id UUID REFERENCES raid_event(id) ON DELETE CASCADE,
    character_id UUID REFERENCES game_character(id) ON DELETE CASCADE,
    status VARCHAR(50) NOT NULL,
    notes TEXT,
    signup_date TIMESTAMP NOT NULL,
    updated_at TIMESTAMP,
    UNIQUE(event_id, character_id)
  );
  ```

- [ ] **Créer enums**
  - [ ] `RaidDifficulty.php` (Normal/Heroic/Mythic)
  - [ ] `EventSignupStatus.php` (Confirmed/Tentative/Declined)

- [ ] **Créer entités**
  - [ ] `RaidEvent.php`
  - [ ] `EventSignup.php`

- [ ] **Créer RaidEventController.php**
  - [ ] `POST /api/guild/{id}/events`
    - Check : Officer+
    - Create event
  - [ ] `GET /api/guild/{id}/events?from=DATE&to=DATE`
    - Check : Member (full) OR Public (upcoming only)
    - Filter by date range
  - [ ] `GET /api/event/{id}`
    - Check : Member OR Public (if event is public)
    - Include signups with character details
  - [ ] `PUT /api/event/{id}`
    - Check : Officer+ (creator or higher role)
  - [ ] `DELETE /api/event/{id}`
    - Check : Officer+ (creator or higher)
  - [ ] `POST /api/event/{id}/signup`
    - Check : Member (own characters only)
    - Body : `{characterId, status, notes}`
  - [ ] `PATCH /api/signup/{id}`
    - Check : Owner OR Officer+
    - Update status

- [ ] **Créer EventVoter.php**
  - `EVENT_VIEW` : Member OR (Public AND event future)
  - `EVENT_CREATE` : Officer+
  - `EVENT_EDIT` : Officer+ AND (creator OR higher role)
  - `EVENT_DELETE` : Officer+ AND (creator OR higher role)

- [ ] **Créer RaidEventDTO.php**
  ```php
  {
    id: string,
    name: string,
    description: string,
    eventDate: string (ISO),
    duration: int,
    difficulty: string,
    maxParticipants: int,
    signupCounts: {
      confirmed: int,
      tentative: int,
      declined: int,
      total: int
    },
    composition: {
      tanks: int,
      healers: int,
      dps: int
    },
    signups: [{
      id: string,
      character: {id, name, class, role},
      status: string,
      signupDate: string
    }],
    createdBy: {id, username}
  }
  ```

#### Frontend Tasks
- [ ] **Installer V-Calendar**
  ```bash
  npm install v-calendar@next
  ```

- [ ] **Compléter RaidCalendarView.vue**
  - Toolbar : View switcher (Month/Week/List)
  - V-Calendar avec événements
  - Click event → ouvre EventDetailModal

- [ ] **Créer composants calendar/**
  - [ ] `EventCreateModal.vue`
    - Form : name, description, date/time
    - Date picker avec V-Calendar
    - Select : difficulty, max participants
    - Checkbox : recurring (+ pattern input)
  - [ ] `EventDetailModal.vue`
    - En-tête : nom, date, difficulté
    - Section : Description
    - Section : Signups (liste groupée par status)
    - Bouton : Sign Up (ouvre signup form)
    - Bouton : Edit/Delete (si Officer+)
  - [ ] `SignupForm.vue` (dans EventDetailModal)
    - Select character
    - Radio : Confirmed / Tentative / Declined
    - Textarea : Notes
  - [ ] `SignupList.vue`
    - Tabs : Confirmed | Tentative | Declined
    - Affiche composition : 2 tanks, 5 healers, 18 DPS
    - Progress bar : 25/25 slots

- [ ] **Créer event.service.ts**
  - `createEvent(guildId, data)`
  - `getEvents(guildId, from, to)`
  - `getEvent(id)`
  - `updateEvent(id, data)`
  - `deleteEvent(id)`
  - `signup(eventId, data)`
  - `updateSignup(signupId, status)`

#### Fichiers
```
backend/migrations/VersionXXX_CreateEventTables.php
backend/src/Entity/RaidEvent.php
backend/src/Entity/EventSignup.php
backend/src/Enum/RaidDifficulty.php
backend/src/Enum/EventSignupStatus.php
backend/src/Controller/RaidEventController.php
backend/src/Security/Voter/EventVoter.php
backend/src/DTO/RaidEventDTO.php
frontend/src/views/RaidCalendarView.vue
frontend/src/components/calendar/EventCreateModal.vue
frontend/src/components/calendar/EventDetailModal.vue
frontend/src/components/calendar/SignupForm.vue
frontend/src/components/calendar/SignupList.vue
frontend/src/services/event.service.ts
package.json (add v-calendar)
```

**Tests de Validation :**
- [ ] Officer peut créer événement
- [ ] Événement apparaît dans calendrier
- [ ] Member peut s'inscrire avec son perso
- [ ] Composition se calcule correctement
- [ ] Cannot signup si event full

---

(Le fichier continue avec Phase 3, 4, 5, BDD, API, etc. - trop long pour un seul message)

Voulez-vous que je continue avec les phases suivantes ?

### F2.2 - Import d'Événements JSON

**User Story :** "En tant qu'Officer, j'importe 20 raids depuis mon addon RCLootCouncil pour remplir le calendrier rapidement."

#### Fonctionnalités
- 📤 Upload fichier JSON
- 🔍 Validation & Preview
- ✅ Import en batch
- ⚠️ Gestion des doublons

#### Backend Tasks
- [ ] **Créer EventImportController.php**
  - [ ] `POST /api/guild/{id}/events/import`
    - Check : Officer+
    - Parse JSON, return preview
  - [ ] `POST /api/guild/{id}/events/import/confirm`
    - Check : Officer+
    - Create events in batch

- [ ] **Créer EventImportService.php**
  - Méthode `parseJson(string $json): ImportResult`
  - Validation schema
  - Detect duplicates (same name + date)

#### Frontend Tasks
- [ ] **Compléter ImportEventsView.vue**
- [ ] **Utiliser EventJsonImportModal.vue** (existe déjà)
- [ ] **Créer ImportPreviewTable.vue**
  - Tableau des événements à importer
  - Marquer les doublons

#### Fichiers
```
backend/src/Controller/EventImportController.php
backend/src/Service/EventImportService.php
frontend/src/views/ImportEventsView.vue
frontend/src/components/ImportPreviewTable.vue
```

**Tests :**
- [ ] Import valide crée les événements
- [ ] Doublons sont détectés
- [ ] JSON invalide → error

---

### Livrable Phase 2
✅ Calendrier de raids fonctionnel
✅ Système d'inscription RSVP
✅ Import événements en masse

**→ Raids organisables efficacement**

---

## 💎 Phase 3 : Système DKP & Loot

**Objectif :** Transparence totale sur le loot et les points
**Durée :** 4-5 semaines
**Priorité :** 🔴 CRITIQUE pour la confiance

### Principe Fondamental : TRANSPARENCE = CONFIANCE

**🌐 DKP Standings = PUBLIC (ou visible par tous membres)**

Pourquoi ? 
- Élimine les soupçons de favoritisme
- Chacun sait où il en est
- Les recrues peuvent voir le système avant de join
- Encourage la participation équitable

### F3.1 - Infrastructure DKP

#### Fonctionnalités
- 🏦 Pools DKP multiples
  - Ex: "Naxxramas", "Ulduar", "Season 1"
  - Chaque pool a ses règles (decay, min/max)
- 💰 Comptes DKP par personnage/pool
  - Balance actuelle
  - Lifetime earned
  - Lifetime spent
- 📊 **Standings PUBLIC**
  - Classement par balance
  - Filtrable par pool
  - Recherche par personnage

#### Backend Tasks
- [ ] **Créer migrations**
  ```sql
  CREATE TABLE dkp_pool (
    id UUID PRIMARY KEY,
    guild_id UUID REFERENCES game_guild(id) ON DELETE CASCADE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    decay_rate DECIMAL(5,2) DEFAULT 0,
    max_balance INT,
    min_balance INT DEFAULT 0,
    is_active BOOLEAN DEFAULT true,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
  );

  CREATE TABLE dkp_account (
    id UUID PRIMARY KEY,
    character_id UUID REFERENCES game_character(id) ON DELETE CASCADE,
    pool_id UUID REFERENCES dkp_pool(id) ON DELETE CASCADE,
    balance INT DEFAULT 0,
    lifetime_earned INT DEFAULT 0,
    lifetime_spent INT DEFAULT 0,
    updated_at TIMESTAMP,
    UNIQUE(character_id, pool_id)
  );

  CREATE TABLE dkp_transaction (
    id UUID PRIMARY KEY,
    account_id UUID REFERENCES dkp_account(id) ON DELETE CASCADE,
    amount INT NOT NULL,
    reason TEXT NOT NULL,
    transaction_type VARCHAR(50) NOT NULL,
    event_id UUID REFERENCES raid_event(id),
    created_by UUID REFERENCES user(id),
    created_at TIMESTAMP NOT NULL
  );

  CREATE TABLE loot_record (
    id UUID PRIMARY KEY,
    character_id UUID REFERENCES game_character(id) ON DELETE CASCADE,
    item_name VARCHAR(255) NOT NULL,
    item_id INT,
    item_quality VARCHAR(50),
    dkp_spent INT DEFAULT 0,
    event_id UUID REFERENCES raid_event(id),
    created_by UUID REFERENCES user(id),
    received_at TIMESTAMP NOT NULL
  );
  ```

- [ ] **Créer enums**
  - [ ] `TransactionType.php`
    ```php
    enum TransactionType: string {
        case EARN = 'earn';           // Gagné (présence, boss kill)
        case SPEND = 'spend';         // Dépensé (loot)
        case ADJUSTMENT = 'adjustment'; // Correction manuelle
        case DECAY = 'decay';         // Déclin automatique
        case BONUS = 'bonus';         // Bonus (on-time, performance)
        case PENALTY = 'penalty';     // Pénalité (absence, retard)
    }
    ```
  - [ ] `ItemQuality.php` (Common/Uncommon/Rare/Epic/Legendary)

- [ ] **Créer entités**
  - [ ] `DkpPool.php`
    - Relations : oneToMany DkpAccount
  - [ ] `DkpAccount.php`
    - Relations : manyToOne Character, manyToOne DkpPool
    - Relations : oneToMany DkpTransaction
  - [ ] `DkpTransaction.php`
    - Relations : manyToOne DkpAccount, User, RaidEvent (optional)
  - [ ] `LootRecord.php`
    - Relations : manyToOne Character, User, RaidEvent (optional)

- [ ] **Créer DkpController.php**
  - [ ] `GET /api/public/guild/{id}/dkp/standings?pool={poolId}`
    - **NO AUTH REQUIRED** (si show_dkp_public=true)
    - OR Check : Member (si show_dkp_public=false)
    - Returns : DkpStandingsDTO
  - [ ] `GET /api/guild/{id}/dkp/pools`
    - Check : Member
    - Liste des pools actifs
  - [ ] `POST /api/guild/{id}/dkp/pools`
    - Check : GM
    - Créer nouveau pool
  - [ ] `PATCH /api/dkp/pool/{id}`
    - Check : GM
    - Update settings (decay_rate, etc)
  - [ ] `GET /api/character/{id}/dkp/history?pool={poolId}`
    - Check : Member
    - Historique transactions du personnage
  - [ ] `POST /api/dkp/transaction`
    - Check : Officer+
    - Body : `{accountId, amount, reason, type, eventId?}`
    - Create transaction, update balance
  - [ ] `POST /api/dkp/transaction/batch`
    - Check : Officer+
    - Award DKP to multiple characters at once
    - Body : `{eventId, characterIds[], amount, reason}`

- [ ] **Créer DkpVoter.php**
  - `DKP_VIEW_STANDINGS` : Public OR Member
  - `DKP_VIEW_HISTORY` : Member
  - `DKP_CREATE_TRANSACTION` : Officer+
  - `DKP_MANAGE_POOL` : GM

- [ ] **Créer DkpCalculationService.php**
  - Méthode `createTransaction(account, amount, reason, type): Transaction`
    - Update balance
    - Update lifetime_earned/spent
    - Enforce min/max
  - Méthode `applyDecay(pool): int`
    - Apply decay to all accounts in pool
    - Create DECAY transactions
    - Return number affected

- [ ] **Créer DkpStandingsDTO.php**
  ```php
  {
    pool: {id, name, description},
    standings: [{
      characterId: string,
      characterName: string,
      class: string,
      balance: int,
      lifetimeEarned: int,
      lifetimeSpent: int,
      lastTransaction: string (date)
    }],
    totalCharacters: int
  }
  ```

#### Frontend Tasks
- [ ] **Compléter DkpSystemView.vue**
  - Tabs : Standings | Transactions | Loot History | Settings (GM only)

- [ ] **Créer composants dkp/**
  - [ ] `DkpStandings.vue`
    - Tableau trié par balance DESC
    - Colonnes : Rank, Character, Class, Balance, Earned, Spent
    - Search bar
    - Pool selector
    - **Affichage PUBLIC** (badge si public)
  - [ ] `DkpTransactionForm.vue`
    - Mode : Single character OR Batch (multiple)
    - Select event (optional)
    - Amount (can be negative for penalty)
    - Reason (required)
    - Type (earn/bonus/penalty/adjustment)
  - [ ] `DkpHistory.vue`
    - Timeline des transactions
    - Filter par type, date range
    - Show event link si présent
  - [ ] `DkpPoolManager.vue` (GM only)
    - Liste pools
    - Create/Edit pool
    - Configure decay rate
    - Run decay manually
  - [ ] `DkpPoolCard.vue`
    - Stats : Total DKP in pool, Average per char
    - Settings display

- [ ] **Créer dkp.service.ts**
  - `getStandings(guildId, poolId)` → public endpoint
  - `getPools(guildId)`
  - `createPool(guildId, data)`
  - `updatePool(poolId, data)`
  - `getCharacterHistory(characterId, poolId)`
  - `createTransaction(data)`
  - `createBatchTransaction(data)`

#### Fichiers
```
backend/migrations/VersionXXX_CreateDkpTables.php
backend/src/Entity/DkpPool.php
backend/src/Entity/DkpAccount.php
backend/src/Entity/DkpTransaction.php
backend/src/Entity/LootRecord.php
backend/src/Enum/TransactionType.php
backend/src/Enum/ItemQuality.php
backend/src/Controller/DkpController.php
backend/src/Security/Voter/DkpVoter.php
backend/src/Service/DkpCalculationService.php
backend/src/DTO/DkpStandingsDTO.php
frontend/src/views/DkpSystemView.vue
frontend/src/components/dkp/DkpStandings.vue
frontend/src/components/dkp/DkpTransactionForm.vue
frontend/src/components/dkp/DkpHistory.vue
frontend/src/components/dkp/DkpPoolManager.vue
frontend/src/components/dkp/DkpPoolCard.vue
frontend/src/services/dkp.service.ts
```

**Tests de Validation :**
- [ ] **CRITIQUE** : Visiteur non-loggué peut voir standings si public
- [ ] Officer peut créer transaction
- [ ] Balance se met à jour correctement
- [ ] Decay appliqué correctement
- [ ] Cannot go below min_balance

---

### F3.2 - Enregistrement de Loot

**User Story :** "Après le raid, l'officer enregistre que PlayerX a reçu [Gressil] pour 150 DKP. Le DKP est automatiquement déduit."

#### Fonctionnalités
- 📦 Enregistrer loot reçu
  - Sélectionner personnage
  - Nom/ID item (autocomplete future: Wowhead API)
  - DKP dépensé (ou 0 pour free loot)
  - Event lié (optional)
- 📜 **Historique de loot PUBLIC**
  - Qui a reçu quoi
  - Quand
  - Combien de DKP dépensé
- 🔗 Lien avec événement de raid

#### Backend Tasks
- [ ] **Ajouter à DkpController.php**
  - [ ] `POST /api/dkp/loot`
    - Check : Officer+
    - Body : `{characterId, itemName, itemId?, dkpSpent, eventId?, poolId}`
    - Steps :
      1. Create LootRecord
      2. If dkpSpent > 0 : Create SPEND transaction
      3. Update balance
  - [ ] `GET /api/public/guild/{id}/loot/history`
    - **NO AUTH** (si show_dkp_public=true) OR Member
    - Returns : List of LootRecord with character info
  - [ ] `GET /api/character/{id}/loot`
    - Check : Member
    - Loot history for specific character

- [ ] **Créer LootRecordDTO.php**
  ```php
  {
    id: string,
    character: {id, name, class},
    itemName: string,
    itemId: int?,
    itemQuality: string,
    dkpSpent: int,
    event: {id, name, date}?,
    receivedAt: string,
    createdBy: {id, username}
  }
  ```

#### Frontend Tasks
- [ ] **Ajouter à DkpSystemView.vue**
  - Tab "Loot History"

- [ ] **Créer composants**
  - [ ] `LootRecordForm.vue`
    - Select character
    - Input item name (avec autocomplete si Wowhead API)
    - Input DKP spent
    - Select event (optional)
    - Select pool
  - [ ] `LootHistory.vue`
    - Tableau : Date | Character | Item | DKP | Event
    - Filter par character, date range
    - **Badge PUBLIC** si visible publiquement

#### Fichiers
```
backend/src/Controller/DkpController.php (extend)
backend/src/DTO/LootRecordDTO.php
frontend/src/components/dkp/LootRecordForm.vue
frontend/src/components/dkp/LootHistory.vue
```

**Tests :**
- [ ] Officer enregistre loot
- [ ] DKP déduit automatiquement
- [ ] Loot history visible publiquement
- [ ] Cannot spend more DKP than balance

---

### F3.3 - Attribution Automatique de DKP

**User Story :** "Après avoir créé un événement de raid, je veux pouvoir marquer les présents et attribuer automatiquement 50 DKP à chacun."

#### Fonctionnalités
- ✅ Lier event → signups → DKP
- 🎯 Award DKP to all "Confirmed" signups
- 📊 Award DKP based on boss kills (future)

#### Backend Tasks
- [ ] **Ajouter à DkpController.php**
  - [ ] `POST /api/event/{id}/award-dkp`
    - Check : Officer+
    - Body : `{amount, reason, poolId, includeTentative: bool}`
    - Steps :
      1. Get all confirmed (+ tentative if flag) signups
      2. Create transactions for each character
      3. Return summary

- [ ] **Créer DkpAwardSummaryDTO.php**
  ```php
  {
    eventId: string,
    eventName: string,
    poolId: string,
    amount: int,
    charactersAwarded: int,
    totalDkpAwarded: int,
    transactions: [{characterId, characterName, newBalance}]
  }
  ```

#### Frontend Tasks
- [ ] **Ajouter dans EventDetailModal.vue**
  - Bouton "Award DKP" (visible si Officer+)
  - Ouvre DkpAwardModal

- [ ] **Créer DkpAwardModal.vue**
  - Select pool
  - Input amount
  - Input reason
  - Checkbox : Include tentative?
  - Preview : X characters will receive Y DKP
  - Confirm → Award

#### Fichiers
```
backend/src/Controller/DkpController.php (extend)
backend/src/DTO/DkpAwardSummaryDTO.php
frontend/src/components/calendar/EventDetailModal.vue (modify)
frontend/src/components/dkp/DkpAwardModal.vue
```

**Tests :**
- [ ] DKP awarded to all confirmed
- [ ] Tentative included if flag true
- [ ] Summary correct

---

### Livrable Phase 3
✅ Système DKP complet avec pools multiples
✅ **Standings DKP PUBLICS** (transparence totale)
✅ Enregistrement de loot avec déduction auto
✅ Historique de loot PUBLIC
✅ Attribution automatique post-raid

**→ Système de loot transparent et équitable**

---

## 📊 Phase 4 : Analytics & Insights

**Objectif :** Comprendre la santé de la guilde
**Durée :** 2-3 semaines
**Priorité :** 🟡 MOYENNE

### F4.1 - Statistiques de Raid

**User Story :** "Je veux voir qui a le meilleur taux de présence, et qui a reçu le plus de loot ce mois."

#### Fonctionnalités
- 📈 Taux de présence
  - Par personnage
  - Par joueur (tous ses persos)
  - Par période (mois, tier, saison)
- 💎 Distribution de loot
  - Par personnage
  - Par classe
  - Par slot (tête, arme, etc)
- 🎯 Performance
  - Signups vs attendance réelle
  - Main vs alt participation

#### Backend Tasks
- [ ] **Créer RaidStatsController.php**
  - [ ] `GET /api/guild/{id}/stats/attendance`
    - Check : Member
    - Query params : from, to, characterId?, userId?
    - Returns : AttendanceStatsDTO
  - [ ] `GET /api/guild/{id}/stats/loot`
    - Check : Member
    - Query params : from, to, class?, slot?
    - Returns : LootStatsDTO
  - [ ] `GET /api/guild/{id}/stats/overview`
    - Check : Member
    - Returns : OverviewStatsDTO (aggregate)

- [ ] **Créer RaidStatsService.php**
  - Méthode `calculateAttendance(guild, filters): array`
    - Count events per character
    - Calculate % présence
  - Méthode `calculateLootDistribution(guild, filters): array`
    - Count loot per character/class
    - Calculate DKP spent

- [ ] **Créer DTOs**
  - [ ] `AttendanceStatsDTO.php`
  - [ ] `LootStatsDTO.php`
  - [ ] `OverviewStatsDTO.php`

#### Frontend Tasks
- [ ] **Compléter RaidStatsView.vue**
  - Tabs : Attendance | Loot | Overview

- [ ] **Créer composants stats/**
  - [ ] `AttendanceChart.vue`
    - Line chart : présence over time
  - [ ] `AttendanceTable.vue`
    - Tableau : Character, Events attended, % présence
    - Sort by %
  - [ ] `LootDistributionChart.vue`
    - Bar chart : loot per class
  - [ ] `LootTable.vue`
    - Tableau : Character, Items received, DKP spent

- [ ] **Créer stats.service.ts**
  - `getAttendanceStats(guildId, filters)`
  - `getLootStats(guildId, filters)`
  - `getOverviewStats(guildId)`

#### Fichiers
```
backend/src/Controller/RaidStatsController.php
backend/src/Service/RaidStatsService.php
backend/src/DTO/AttendanceStatsDTO.php
backend/src/DTO/LootStatsDTO.php
frontend/src/views/RaidStatsView.vue
frontend/src/components/stats/AttendanceChart.vue
frontend/src/components/stats/AttendanceTable.vue
frontend/src/components/stats/LootDistributionChart.vue
frontend/src/components/stats/LootTable.vue
frontend/src/services/stats.service.ts
```

**Tests :**
- [ ] Stats calculées correctement
- [ ] Filtres fonctionnent
- [ ] Charts s'affichent

---

### F4.2 - Rapports de Guilde

**User Story :** "Je veux un rapport hebdomadaire automatique sur l'activité de ma guilde."

#### Fonctionnalités
- 📋 Rapports automatiques
  - Hebdomadaires
  - Mensuels
- 📊 Contenu du rapport
  - Nouveaux membres / départs
  - Raids effectués
  - Loot distribué
  - Top DKP earners
  - Taux de présence moyen

#### Backend Tasks
- [ ] **Créer GuildActivityLog entity** (si pas déjà fait Phase 0)
  - Logger toutes les actions importantes

- [ ] **Créer GuildReportsController.php**
  - [ ] `GET /api/guild/{id}/reports/weekly`
    - Check : Member
    - Returns : WeeklyReportDTO
  - [ ] `GET /api/guild/{id}/reports/monthly`
    - Check : Member
    - Returns : MonthlyReportDTO
  - [ ] `POST /api/guild/{id}/reports/generate`
    - Check : Officer+
    - Generate report manually

- [ ] **Créer ActivityLoggerService.php**
  - Méthode `log(guild, type, description, metadata)`
  - Inject dans tous les controllers qui font des actions importantes

- [ ] **Créer ReportGeneratorService.php**
  - Méthode `generateWeeklyReport(guild): WeeklyReport`
  - Méthode `generateMonthlyReport(guild): MonthlyReport`

#### Frontend Tasks
- [ ] **Compléter GuildReportsView.vue**
  - Tabs : Weekly | Monthly | Activity Log

- [ ] **Créer composants reports/**
  - [ ] `WeeklyReport.vue`
    - Sections : Members, Raids, Loot, Top Players
  - [ ] `MonthlyReport.vue`
    - Agrégations mensuelles
  - [ ] `ActivityFeed.vue`
    - Timeline d'activité

#### Fichiers
```
backend/migrations/VersionXXX_CreateActivityLog.php
backend/src/Entity/GuildActivityLog.php
backend/src/Controller/GuildReportsController.php
backend/src/Service/ActivityLoggerService.php
backend/src/Service/ReportGeneratorService.php
frontend/src/views/GuildReportsView.vue
frontend/src/components/reports/WeeklyReport.vue
frontend/src/components/reports/MonthlyReport.vue
frontend/src/components/reports/ActivityFeed.vue
```

**Tests :**
- [ ] Rapports générés correctement
- [ ] Activity log enregistre les actions

---

### Livrable Phase 4
✅ Statistiques de présence et loot
✅ Rapports hebdo/mensuels automatiques
✅ Activity log complet

**→ Insights pour prendre des décisions**

---

## 🔔 Phase 5 : Communication & Intégrations

**Objectif :** Notifications et intégrations externes
**Durée :** 1-2 semaines
**Priorité :** 🟢 BASSE

### F5.1 - Notifications Discord

**User Story :** "Je veux que mon serveur Discord reçoive un message automatique quand quelqu'un join la guilde ou qu'un raid est créé."

#### Fonctionnalités
- 🔗 Configuration webhook Discord
- 🔔 Types de notifications :
  - Nouveau membre
  - Membre retiré
  - Changement de rôle
  - Raid créé
  - Raid dans X heures (reminder)
  - DKP awarded (optionnel)
  - Loot reçu (optionnel)
- ✏️ Templates personnalisables
- ✅ Test webhook

#### Backend Tasks
- [ ] **Créer migration**
  ```sql
  CREATE TABLE discord_webhook_config (
    id UUID PRIMARY KEY,
    guild_id UUID UNIQUE REFERENCES game_guild(id) ON DELETE CASCADE,
    webhook_url TEXT NOT NULL,
    enabled BOOLEAN DEFAULT true,
    enabled_notifications JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
  );
  ```

- [ ] **Créer DiscordWebhookConfig entity**

- [ ] **Créer DiscordNotificationController.php**
  - [ ] `GET /api/guild/{id}/discord/config`
    - Check : GM
  - [ ] `PUT /api/guild/{id}/discord/config`
    - Check : GM
    - Body : `{webhookUrl, enabled, enabledNotifications[]}`
  - [ ] `POST /api/guild/{id}/discord/test`
    - Check : GM
    - Send test message

- [ ] **Créer DiscordNotificationService.php**
  - Méthode `sendNotification(guild, type, data)`
  - Méthode `formatEmbed(type, data): array`
  - Handle errors (webhook deleted, etc)

- [ ] **Intégrer dans controllers existants**
  - GuildMembershipController → onMemberJoin, onMemberLeave
  - RaidEventController → onEventCreate
  - DkpController → onDkpAwarded, onLootReceived

#### Frontend Tasks
- [ ] **Compléter DiscordNotificationsView.vue**

- [ ] **Créer composants discord/**
  - [ ] `WebhookConfigForm.vue`
    - Input webhook URL
    - Toggle enabled
    - Test button
  - [ ] `NotificationTypeToggle.vue`
    - Checkboxes pour chaque type
  - [ ] `NotificationPreview.vue`
    - Preview du message Discord (embed)

#### Fichiers
```
backend/migrations/VersionXXX_CreateDiscordWebhook.php
backend/src/Entity/DiscordWebhookConfig.php
backend/src/Controller/DiscordNotificationController.php
backend/src/Service/DiscordNotificationService.php
backend/src/Controller/GuildMembershipController.php (modify)
backend/src/Controller/RaidEventController.php (modify)
backend/src/Controller/DkpController.php (modify)
frontend/src/views/DiscordNotificationsView.vue
frontend/src/components/discord/WebhookConfigForm.vue
frontend/src/components/discord/NotificationTypeToggle.vue
frontend/src/components/discord/NotificationPreview.vue
```

**Tests :**
- [ ] GM peut configurer webhook
- [ ] Test webhook envoie message
- [ ] Notifications envoyées sur events

---

### Livrable Phase 5
✅ Notifications Discord configurables
✅ Intégration automatique avec événements

**→ Communication seamless avec Discord**

---

## 🗄️ Schéma Base de Données Complet

### Vue d'ensemble des Relations

```
User (Discord Account)
  ├─→ 1:N GuildMembership
  ├─→ 1:N GameCharacter (owner)
  └─→ Created: RaidEvent, DkpTransaction, LootRecord

GameGuild
  ├─→ 1:N GuildMembership
  ├─→ 1:N GameCharacter
  ├─→ 1:N RaidEvent
  ├─→ 1:N RaidRoster
  ├─→ 1:N DkpPool
  ├─→ 1:1 DiscordWebhookConfig
  └─→ 1:N GuildActivityLog

GameCharacter
  ├─→ N:1 User (owner, nullable)
  ├─→ N:1 GameGuild
  ├─→ 1:N EventSignup
  ├─→ 1:N RosterAssignment
  ├─→ 1:N DkpAccount (per pool)
  └─→ 1:N LootRecord

RaidEvent
  ├─→ N:1 GameGuild
  ├─→ N:1 User (creator)
  ├─→ 1:N EventSignup
  ├─→ 1:N DkpTransaction (optional link)
  └─→ 1:N LootRecord (optional link)

DkpPool
  ├─→ N:1 GameGuild
  └─→ 1:N DkpAccount

DkpAccount (per character per pool)
  ├─→ N:1 GameCharacter
  ├─→ N:1 DkpPool
  └─→ 1:N DkpTransaction
```

---

### Tables Existantes (à Modifier)

#### `user`
```sql
CREATE TABLE user (
  id UUID PRIMARY KEY,
  discord_id VARCHAR(255) UNIQUE NOT NULL,
  username VARCHAR(255) NOT NULL,
  email VARCHAR(255),
  avatar VARCHAR(500),                    -- ADD
  roles JSON,
  created_at TIMESTAMP DEFAULT NOW(),     -- ADD
  updated_at TIMESTAMP DEFAULT NOW()      -- ADD
);
```

#### `game_guild`
```sql
CREATE TABLE game_guild (
  id UUID PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  faction VARCHAR(50) NOT NULL,           -- Horde/Alliance
  description TEXT,                       -- ADD
  realm VARCHAR(100),                     -- ADD (server name)
  region VARCHAR(10),                     -- ADD (US/EU/KR/TW)
  nbr_guild_members INT DEFAULT 0,
  nbr_characters INT DEFAULT 0,
  is_public BOOLEAN DEFAULT TRUE,         -- ADD (public page)
  show_dkp_public BOOLEAN DEFAULT TRUE,   -- ADD (public DKP)
  recruiting_status VARCHAR(50) DEFAULT 'open', -- ADD (open/closed/trial)
  created_at TIMESTAMP DEFAULT NOW(),     -- ADD
  updated_at TIMESTAMP DEFAULT NOW()      -- ADD
);
```

#### `game_character`
```sql
CREATE TABLE game_character (
  id UUID PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  class VARCHAR(50) NOT NULL,
  class_spec VARCHAR(50),
  role VARCHAR(50) NOT NULL,              -- Tank/Healer/Melee/Ranged
  level INT,                              -- ADD
  item_level INT,                         -- ADD
  is_main BOOLEAN DEFAULT TRUE,           -- ADD (main vs alt)
  status VARCHAR(50) DEFAULT 'active',    -- ADD (active/inactive/bench/absence)
  verified BOOLEAN DEFAULT FALSE,         -- ADD (future: armory link)
  guild_id UUID REFERENCES game_guild(id) ON DELETE CASCADE,
  user_player_id UUID REFERENCES user(id) ON DELETE SET NULL, -- nullable for imports
  created_at TIMESTAMP DEFAULT NOW(),     -- ADD
  updated_at TIMESTAMP DEFAULT NOW()      -- ADD
);

CREATE INDEX idx_character_guild ON game_character(guild_id);
CREATE INDEX idx_character_user ON game_character(user_player_id);
```

#### `guild_membership`
```sql
CREATE TABLE guild_membership (
  id UUID PRIMARY KEY,
  user_id UUID NOT NULL REFERENCES user(id) ON DELETE CASCADE,
  guild_id UUID NOT NULL REFERENCES game_guild(id) ON DELETE CASCADE,
  role VARCHAR(50) NOT NULL DEFAULT 'Member', -- GM/Officer/Member
  joined_at TIMESTAMP DEFAULT NOW(),      -- ADD
  left_at TIMESTAMP,                      -- ADD (nullable, for tracking departures)
  is_active BOOLEAN DEFAULT TRUE,         -- ADD
  UNIQUE(user_id, guild_id)
);

CREATE INDEX idx_membership_user ON guild_membership(user_id);
CREATE INDEX idx_membership_guild ON guild_membership(guild_id);
```

---

### Nouvelles Tables Phase 1

#### `raid_roster`
```sql
CREATE TABLE raid_roster (
  id UUID PRIMARY KEY,
  guild_id UUID NOT NULL REFERENCES game_guild(id) ON DELETE CASCADE,
  name VARCHAR(255) NOT NULL,
  size INT NOT NULL,                      -- 10, 20, 25, 40
  notes TEXT,
  created_by UUID REFERENCES user(id),
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_roster_guild ON raid_roster(guild_id);
```

#### `roster_assignment`
```sql
CREATE TABLE roster_assignment (
  id UUID PRIMARY KEY,
  roster_id UUID NOT NULL REFERENCES raid_roster(id) ON DELETE CASCADE,
  character_id UUID NOT NULL REFERENCES game_character(id) ON DELETE CASCADE,
  slot_number INT NOT NULL,
  assigned_role VARCHAR(50),              -- Tank/Healer/DPS
  UNIQUE(roster_id, slot_number)
);

CREATE INDEX idx_assignment_roster ON roster_assignment(roster_id);
CREATE INDEX idx_assignment_character ON roster_assignment(character_id);
```

---

### Nouvelles Tables Phase 2

#### `raid_event`
```sql
CREATE TABLE raid_event (
  id UUID PRIMARY KEY,
  guild_id UUID NOT NULL REFERENCES game_guild(id) ON DELETE CASCADE,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  event_date TIMESTAMP NOT NULL,
  duration INT DEFAULT 180,               -- minutes
  difficulty VARCHAR(50),                 -- Normal/Heroic/Mythic
  max_participants INT DEFAULT 25,
  recurring_pattern VARCHAR(100),         -- cron expression for recurring
  created_by UUID REFERENCES user(id),
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_event_guild ON raid_event(guild_id);
CREATE INDEX idx_event_date ON raid_event(event_date);
CREATE INDEX idx_event_guild_date ON raid_event(guild_id, event_date);
```

#### `event_signup`
```sql
CREATE TABLE event_signup (
  id UUID PRIMARY KEY,
  event_id UUID NOT NULL REFERENCES raid_event(id) ON DELETE CASCADE,
  character_id UUID NOT NULL REFERENCES game_character(id) ON DELETE CASCADE,
  status VARCHAR(50) NOT NULL,            -- confirmed/tentative/declined
  notes TEXT,
  signup_date TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW(),
  UNIQUE(event_id, character_id)          -- One signup per character per event
);

CREATE INDEX idx_signup_event ON event_signup(event_id);
CREATE INDEX idx_signup_character ON event_signup(character_id);
```

---

### Nouvelles Tables Phase 3 (DKP)

#### `dkp_pool`
```sql
CREATE TABLE dkp_pool (
  id UUID PRIMARY KEY,
  guild_id UUID NOT NULL REFERENCES game_guild(id) ON DELETE CASCADE,
  name VARCHAR(255) NOT NULL,
  description TEXT,
  decay_rate DECIMAL(5,2) DEFAULT 0,      -- % decay per week (e.g., 5.00 = 5%)
  max_balance INT,                        -- optional cap
  min_balance INT DEFAULT 0,              -- floor (can't go below)
  is_active BOOLEAN DEFAULT TRUE,
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_pool_guild ON dkp_pool(guild_id);
```

#### `dkp_account`
```sql
CREATE TABLE dkp_account (
  id UUID PRIMARY KEY,
  character_id UUID NOT NULL REFERENCES game_character(id) ON DELETE CASCADE,
  pool_id UUID NOT NULL REFERENCES dkp_pool(id) ON DELETE CASCADE,
  balance INT DEFAULT 0,                  -- current DKP
  lifetime_earned INT DEFAULT 0,          -- total ever earned
  lifetime_spent INT DEFAULT 0,           -- total ever spent
  updated_at TIMESTAMP DEFAULT NOW(),
  UNIQUE(character_id, pool_id)           -- One account per character per pool
);

CREATE INDEX idx_account_character ON dkp_account(character_id);
CREATE INDEX idx_account_pool ON dkp_account(pool_id);
CREATE INDEX idx_account_balance ON dkp_account(balance DESC); -- for standings
```

#### `dkp_transaction`
```sql
CREATE TABLE dkp_transaction (
  id UUID PRIMARY KEY,
  account_id UUID NOT NULL REFERENCES dkp_account(id) ON DELETE CASCADE,
  amount INT NOT NULL,                    -- positive = earn, negative = spend
  reason TEXT NOT NULL,
  transaction_type VARCHAR(50) NOT NULL,  -- earn/spend/adjustment/decay/bonus/penalty
  event_id UUID REFERENCES raid_event(id) ON DELETE SET NULL,
  created_by UUID REFERENCES user(id),
  created_at TIMESTAMP DEFAULT NOW()      -- IMMUTABLE (audit trail)
);

CREATE INDEX idx_transaction_account ON dkp_transaction(account_id);
CREATE INDEX idx_transaction_event ON dkp_transaction(event_id);
CREATE INDEX idx_transaction_date ON dkp_transaction(created_at DESC);
```

#### `loot_record`
```sql
CREATE TABLE loot_record (
  id UUID PRIMARY KEY,
  character_id UUID NOT NULL REFERENCES game_character(id) ON DELETE CASCADE,
  item_name VARCHAR(255) NOT NULL,
  item_id INT,                            -- WoW item ID (optional)
  item_quality VARCHAR(50),               -- common/uncommon/rare/epic/legendary
  dkp_spent INT DEFAULT 0,                -- 0 for free loot
  event_id UUID REFERENCES raid_event(id) ON DELETE SET NULL,
  created_by UUID REFERENCES user(id),
  received_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_loot_character ON loot_record(character_id);
CREATE INDEX idx_loot_event ON loot_record(event_id);
CREATE INDEX idx_loot_date ON loot_record(received_at DESC);
```

---

### Nouvelles Tables Phase 4 (Analytics)

#### `guild_activity_log`
```sql
CREATE TABLE guild_activity_log (
  id UUID PRIMARY KEY,
  guild_id UUID NOT NULL REFERENCES game_guild(id) ON DELETE CASCADE,
  activity_type VARCHAR(100) NOT NULL,    -- enum: MEMBER_JOIN, MEMBER_LEAVE, ROLE_CHANGE, EVENT_CREATE, DKP_TRANSACTION, LOOT_RECEIVED, etc.
  description TEXT NOT NULL,
  metadata JSON,                          -- additional data (old_role, new_role, etc.)
  user_id UUID REFERENCES user(id) ON DELETE SET NULL,
  character_id UUID REFERENCES game_character(id) ON DELETE SET NULL,
  created_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_activity_guild ON guild_activity_log(guild_id);
CREATE INDEX idx_activity_date ON guild_activity_log(created_at DESC);
CREATE INDEX idx_activity_type ON guild_activity_log(activity_type);
```

---

### Nouvelles Tables Phase 5 (Discord)

#### `discord_webhook_config`
```sql
CREATE TABLE discord_webhook_config (
  id UUID PRIMARY KEY,
  guild_id UUID UNIQUE NOT NULL REFERENCES game_guild(id) ON DELETE CASCADE,
  webhook_url TEXT NOT NULL,
  enabled BOOLEAN DEFAULT TRUE,
  enabled_notifications JSON,             -- array of notification types
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

CREATE INDEX idx_webhook_guild ON discord_webhook_config(guild_id);
```

---

### Enums à Créer (PHP)

```php
// backend/src/Enum/GuildRole.php
enum GuildRole: string {
    case GM = 'GM';
    case OFFICER = 'Officer';
    case MEMBER = 'Member';
}

// backend/src/Enum/CharacterStatus.php
enum CharacterStatus: string {
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case BENCH = 'bench';
    case ABSENCE = 'absence';
}

// backend/src/Enum/RaidDifficulty.php
enum RaidDifficulty: string {
    case NORMAL = 'Normal';
    case HEROIC = 'Heroic';
    case MYTHIC = 'Mythic';
}

// backend/src/Enum/EventSignupStatus.php
enum EventSignupStatus: string {
    case CONFIRMED = 'confirmed';
    case TENTATIVE = 'tentative';
    case DECLINED = 'declined';
}

// backend/src/Enum/TransactionType.php
enum TransactionType: string {
    case EARN = 'earn';
    case SPEND = 'spend';
    case ADJUSTMENT = 'adjustment';
    case DECAY = 'decay';
    case BONUS = 'bonus';
    case PENALTY = 'penalty';
}

// backend/src/Enum/ItemQuality.php
enum ItemQuality: string {
    case COMMON = 'common';
    case UNCOMMON = 'uncommon';
    case RARE = 'rare';
    case EPIC = 'epic';
    case LEGENDARY = 'legendary';
}

// backend/src/Enum/ActivityType.php
enum ActivityType: string {
    case MEMBER_JOIN = 'member_join';
    case MEMBER_LEAVE = 'member_leave';
    case ROLE_CHANGE = 'role_change';
    case CHARACTER_ADD = 'character_add';
    case CHARACTER_REMOVE = 'character_remove';
    case EVENT_CREATE = 'event_create';
    case EVENT_UPDATE = 'event_update';
    case DKP_TRANSACTION = 'dkp_transaction';
    case LOOT_RECEIVED = 'loot_received';
}
```

---

## 🌐 API Endpoints Complets

### Endpoints Publics (No Auth Required)

```
GET  /api/public/guilds
     → Liste des guildes publiques (is_public=true)

GET  /api/public/guild/{id}
     → Détails guilde publique
     → 404 si is_public=false

GET  /api/public/guild/{id}/roster
     → Personnages de la guilde
     → Returns : Character[] (no owner info)

GET  /api/public/guild/{id}/dkp/standings?pool={poolId}
     → Standings DKP si show_dkp_public=true
     → 403 si show_dkp_public=false

GET  /api/public/guild/{id}/loot/history
     → Historique loot si show_dkp_public=true
     → 403 si show_dkp_public=false
```

---

### Authentication

```
GET  /api/auth/discord
     → Initiate Discord OAuth flow

GET  /api/auth/callback
     → OAuth callback, create session

GET  /api/auth/logout
     → Destroy session

GET  /api/user/me
     → Current user + guildes
```

---

### Guild Management

```
POST   /api/guilds
       → Create guild (any auth user → becomes GM)
       → Body: {name, faction, realm?, region?}

GET    /api/guilds
       → List user's guilds (via GuildMembership)

GET    /api/guild/{id}
       → Guild details (check GUILD_VIEW)
       → Returns: full details for members

PATCH  /api/guild/{id}
       → Update guild settings (GM only)
       → Body: {description?, is_public?, show_dkp_public?, recruiting_status?}

DELETE /api/guild/{id}
       → Delete guild (GM only)
```

---

### Membership Management

```
GET    /api/guild/{id}/members
       → List members (check MEMBERSHIP_VIEW)
       → Returns: User[] with roles

POST   /api/guild/{id}/members/invite
       → Invite user (GM only)
       → Body: {discordId, role}

PATCH  /api/membership/{id}
       → Update role (GM only)
       → Body: {role}

DELETE /api/membership/{id}
       → Remove member (GM only)
       → Cannot remove last GM
```

---

### Character Management

```
GET    /api/guild/{id}/characters
       → List characters (check CHARACTER_VIEW)
       → Query: ?role=Tank&class=Warrior&status=active

POST   /api/guild/{id}/characters
       → Create character (member only, must own)
       → Body: {name, class, spec, role, is_main?, status?}

GET    /api/character/{id}
       → Character details (check CHARACTER_VIEW)

PUT    /api/character/{id}
       → Update character (check CHARACTER_EDIT)
       → Body: {name?, class?, spec?, role?, is_main?, status?}

DELETE /api/character/{id}
       → Delete character (check CHARACTER_DELETE)
       → Cascade: signups, DKP accounts, loot
```

---

### Roster Management

```
GET    /api/guild/{id}/rosters
       → List rosters (Member)

POST   /api/guild/{id}/rosters
       → Create roster (Officer+)
       → Body: {name, size, notes?}

GET    /api/roster/{id}
       → Roster details + assignments (Member)

PUT    /api/roster/{id}/assignments
       → Update assignments (Officer+)
       → Body: {assignments: [{characterId, slotNumber, role}]}

DELETE /api/roster/{id}
       → Delete roster (Officer+)
```

---

### Event Management

```
GET    /api/guild/{id}/events?from=DATE&to=DATE
       → List events (Member: all, Public: upcoming only)

POST   /api/guild/{id}/events
       → Create event (Officer+)
       → Body: {name, description, eventDate, duration, difficulty, maxParticipants, recurringPattern?}

GET    /api/event/{id}
       → Event details + signups (Member or Public)

PUT    /api/event/{id}
       → Update event (Officer+, creator or higher)

DELETE /api/event/{id}
       → Delete event (Officer+, creator or higher)

POST   /api/event/{id}/signup
       → Signup (Member, own characters)
       → Body: {characterId, status, notes?}

PATCH  /api/signup/{id}
       → Update signup status (Owner or Officer+)
       → Body: {status}

DELETE /api/signup/{id}
       → Remove signup (Owner or Officer+)
```

---

### Event Import

```
POST   /api/guild/{id}/events/import
       → Upload JSON, return preview (Officer+)
       → Body: {json: string}
       → Returns: {events: Event[], duplicates: Event[]}

POST   /api/guild/{id}/events/import/confirm
       → Confirm and import (Officer+)
       → Body: {events: Event[]}
```

---

### DKP Management

```
GET    /api/guild/{id}/dkp/pools
       → List pools (Member)

POST   /api/guild/{id}/dkp/pools
       → Create pool (GM)
       → Body: {name, description?, decayRate?, maxBalance?, minBalance?}

PATCH  /api/dkp/pool/{id}
       → Update pool settings (GM)

GET    /api/public/guild/{id}/dkp/standings?pool={poolId}
       → Standings (PUBLIC if show_dkp_public, else Member)
       → Returns: DkpStandingsDTO

GET    /api/character/{id}/dkp/history?pool={poolId}
       → Transaction history (Member)

POST   /api/dkp/transaction
       → Create transaction (Officer+)
       → Body: {accountId, amount, reason, type, eventId?}

POST   /api/dkp/transaction/batch
       → Batch transactions (Officer+)
       → Body: {eventId?, characterIds[], amount, reason, type}

POST   /api/event/{id}/award-dkp
       → Award DKP to event signups (Officer+)
       → Body: {amount, reason, poolId, includeTentative?}
```

---

### Loot Management

```
POST   /api/dkp/loot
       → Record loot (Officer+)
       → Body: {characterId, itemName, itemId?, dkpSpent, eventId?, poolId}
       → Auto-creates SPEND transaction if dkpSpent > 0

GET    /api/public/guild/{id}/loot/history
       → Loot history (PUBLIC if show_dkp_public, else Member)

GET    /api/character/{id}/loot
       → Character loot history (Member)
```

---

### Analytics & Stats

```
GET    /api/guild/{id}/analytics/dashboard
       → Dashboard stats (Member)
       → Returns: DashboardStatsDTO

GET    /api/guild/{id}/stats/attendance?from=DATE&to=DATE
       → Attendance stats (Member)
       → Returns: AttendanceStatsDTO

GET    /api/guild/{id}/stats/loot?from=DATE&to=DATE
       → Loot distribution stats (Member)
       → Returns: LootStatsDTO

GET    /api/guild/{id}/stats/overview
       → Overview stats (Member)
```

---

### Reports

```
GET    /api/guild/{id}/reports/weekly
       → Weekly report (Member)

GET    /api/guild/{id}/reports/monthly
       → Monthly report (Member)

POST   /api/guild/{id}/reports/generate
       → Generate report manually (Officer+)

GET    /api/guild/{id}/reports/activity?from=DATE&to=DATE
       → Activity log (Member)
```

---

### Discord Integration

```
GET    /api/guild/{id}/discord/config
       → Get webhook config (GM only)

PUT    /api/guild/{id}/discord/config
       → Update webhook config (GM only)
       → Body: {webhookUrl, enabled, enabledNotifications[]}

POST   /api/guild/{id}/discord/test
       → Test webhook (GM only)
```

---

## 🎭 Flows Utilisateurs Détaillés

### Flow 1 : Nouveau Utilisateur Crée une Guilde

**Acteur :** Visiteur non-loggué

1. Visite la homepage
2. Clique "Login with Discord"
3. → Redirigé vers Discord OAuth
4. Approuve les permissions demandées
5. ← Retour homepage (maintenant authentifié)
6. Sidebar affiche "Create Guild"
7. Clique "Create Guild"
8. Remplit form :
   - Name : "Knights of Azeroth"
   - Faction : Horde
   - Realm : "Mograine"
   - Region : EU
9. Submit
10. ✅ Guild créée, user devient GM automatiquement
11. → Redirigé vers `/guild/{id}` (FeaturesView)
12. Voit toutes les features disponibles

**Résultat :** User possède maintenant une guilde et peut inviter des membres

---

### Flow 2 : GM Invite un Membre

**Acteur :** User (rôle GM)

1. Navigate to guild features
2. Clique "Gestion des membres"
3. → Redirigé vers GuildRolesView
4. Voit liste actuelle (1 membre : lui-même, GM)
5. Clique "Invite Member"
6. Modal s'ouvre : InviteMemberModal
7. Entre Discord ID : "123456789"
8. Sélectionne rôle : "Member"
9. Clique "Send Invite"
10. ✅ User ajouté à la guilde
11. ✅ GuildActivityLog enregistre MEMBER_JOIN
12. ✅ Discord notification envoyée (si configuré)
13. Liste membres se refresh
14. Nouveau membre apparaît

**Résultat :** Nouveau membre peut maintenant accéder à la guilde

---

### Flow 3 : Member Ajoute son Personnage

**Acteur :** User (rôle Member)  
**Pré-requis :** Member de la guilde

1. Navigate to guild features
2. Clique "Liste des personnages"
3. → Redirigé vers ListPlayerView
4. Voit roster existant (vide ou avec autres persos)
5. Clique "Add Character"
6. CharacterForm s'ouvre
7. Remplit :
   - Name : "Thexor"
   - Class : "Warrior"
   - Spec : "Arms"
   - Role : "Melee"
   - Is Main : true
   - Status : Active
8. Submit
9. ✅ Character créé
10. ✅ Lié à user (owner)
11. ✅ Lié à guild
12. ✅ ActivityLog : CHARACTER_ADD
13. Character apparaît dans roster
14. **IMPORTANT :** Character maintenant visible dans page publique de guilde

**Résultat :** Member possède un personnage dans la guilde

---

### Flow 4 : Officer Crée un Raid

**Acteur :** User (rôle Officer ou GM)

1. Navigate to guild features
2. Clique "Calendrier raids"
3. → Redirigé vers RaidCalendarView
4. Voit calendrier du mois
5. Clique "+ Create Event"
6. EventCreateModal s'ouvre
7. Remplit :
   - Name : "Naxxramas 25"
   - Date : 2025-11-05
   - Time : 20:00
   - Duration : 180 min
   - Difficulty : Heroic
   - Max participants : 25
8. Submit
9. ✅ RaidEvent créé
10. ✅ ActivityLog : EVENT_CREATE
11. ✅ Discord notification envoyée
12. Event apparaît dans calendrier
13. **PUBLIC :** Event visible sur page publique guilde

**Résultat :** Raid planifié, membres peuvent s'inscrire

---

### Flow 5 : Member S'inscrit à un Raid

**Acteur :** User (rôle Member)  
**Pré-requis :** Possède un personnage

1. Navigate to "Calendrier raids"
2. Voit événements à venir
3. Clique sur "Naxxramas 25"
4. EventDetailModal s'ouvre
5. Voit :
   - Détails (date, heure, difficulté)
   - Liste inscrits (groupés par status)
   - Composition actuelle : 2 tanks, 4 healers, 15 DPS
6. Clique "Sign Up"
7. SignupForm affiche
8. Sélectionne personnage : "Thexor"
9. Sélectionne status : "Confirmed"
10. (Optional) Notes : "Can bring my Paladin alt if needed"
11. Submit
12. ✅ EventSignup créé
13. Modal se refresh
14. "Thexor" apparaît dans liste "Confirmed"
15. Composition update : 2 tanks, 4 healers, **16 DPS**

**Résultat :** Personnage inscrit au raid

---

### Flow 6 : Officer Attribue du DKP après Raid

**Acteur :** User (rôle Officer)  
**Pré-requis :** Raid terminé, signups existent

1. Navigate to "Calendrier raids"
2. Clique sur raid passé : "Naxxramas 25"
3. EventDetailModal s'ouvre
4. Voit "23/25 confirmed"
5. Clique "Award DKP" (bouton Officer only)
6. DkpAwardModal s'ouvre
7. Sélectionne :
   - Pool : "Season 1"
   - Amount : 50 DKP
   - Reason : "Naxx 25H clear"
   - Include tentative : No
8. Preview affiche : "23 characters will receive 50 DKP"
9. Clique "Confirm"
10. ✅ 23 DkpTransactions créées (type: EARN)
11. ✅ Balances updated pour chaque character
12. ✅ ActivityLog : DKP_TRANSACTION
13. ✅ Discord notification (optional)
14. Modal affiche summary :
    - "Awarded 1150 DKP total to 23 characters"
15. Clique "Close"

**Résultat :** Tous les participants ont reçu 50 DKP

---

### Flow 7 : Officer Enregistre du Loot

**Acteur :** User (rôle Officer)  
**Pré-requis :** Raid terminé, DKP system actif

1. Navigate to "Système DKP/Points"
2. Onglet "Loot History"
3. Clique "Record Loot"
4. LootRecordForm s'ouvre
5. Remplit :
   - Character : "Thexor" (autocomplete)
   - Item name : "Gressil, Dawn of Ruin"
   - Item ID : 22988 (optional, autocomplete from Wowhead API future)
   - DKP spent : 150
   - Event : "Naxxramas 25 - 2025-11-05"
   - Pool : "Season 1"
6. Submit
7. ✅ LootRecord créé
8. ✅ DkpTransaction créée (type: SPEND, amount: -150)
9. ✅ Thexor balance : 50 → -100 (50 earned - 150 spent)
10. ✅ ActivityLog : LOOT_RECEIVED
11. ✅ Discord notification : "Thexor received [Gressil] for 150 DKP"
12. Loot apparaît dans history
13. **PUBLIC :** Loot visible dans page publique (si show_dkp_public=true)

**Résultat :** Loot tracké, DKP déduit, transparence totale

---

### Flow 8 : Member Consulte Ses DKP

**Acteur :** User (rôle Member)

1. Navigate to "Système DKP/Points"
2. Onglet "Standings"
3. Voit classement complet :
   - Rank | Character | Class | Balance | Earned | Spent
   - 1. Healer1 | Priest | 200 | 250 | 50
   - 2. Tank1 | Warrior | 150 | 200 | 50
   - ...
   - 15. **Thexor | Warrior | -100 | 50 | 150**
4. Sélectionne pool : "Season 1"
5. Search : "Thexor"
6. Clique sur "Thexor"
7. → Redirigé vers character details
8. Onglet "DKP History"
9. Voit transactions :
   - 2025-11-05 | +50 | EARN | "Naxx 25H clear"
   - 2025-11-05 | -150 | SPEND | "Gressil, Dawn of Ruin"
10. Total : -100 DKP

**Résultat :** Member sait exactement où il en est

---

### Flow 9 : Visiteur Non-Loggué Consulte Page Publique

**Acteur :** Public guest (no auth)  
**URL :** `/public/guild/abc-123`

1. Visite URL directe (partagée sur Discord, forum, etc.)
2. Pas de login requis
3. Voit page publique :
   - **Header :**
     - Nom : "Knights of Azeroth"
     - Faction : Horde
     - Realm : Mograine (EU)
     - Members : 23
     - Recruiting : Open
   - **Roster :**
     - Tableau : Name | Class | Role
     - Thexor | Warrior | Melee
     - Healer1 | Priest | Healer
     - ...
     - (Pas d'info sur owners)
   - **DKP Standings** (si show_dkp_public=true) :
     - Classement DKP complet
     - Healer1 : 200 DKP
     - Tank1 : 150 DKP
     - ...
   - **Loot History** (si show_dkp_public=true) :
     - Date | Character | Item | DKP
     - 2025-11-05 | Thexor | Gressil | 150
   - **Upcoming Raids :**
     - Naxxramas 25 - 2025-11-12 20:00
4. Badge : "PUBLIC GUILD"
5. Bouton (disabled) : "Apply to Join" (future feature)

**Résultat :** Visiteur peut évaluer la guilde avant de join

---

### Flow 10 : GM Configure Discord Notifications

**Acteur :** User (rôle GM)

1. Navigate to guild features
2. Clique "Notifications Discord"
3. → Redirigé vers DiscordNotificationsView
4. Voit WebhookConfigForm
5. Entre webhook URL (copié depuis Discord server settings)
6. Toggle "Enabled" : ON
7. Sélectionne notifications types :
   - ✅ New member joins
   - ✅ Raid created
   - ✅ DKP awarded
   - ❌ Loot received (trop spam)
8. Clique "Test Webhook"
9. ✅ Message envoyé à Discord : "Test message from Guild Tracker"
10. Confirme que message reçu dans Discord
11. Clique "Save"
12. ✅ DiscordWebhookConfig saved
13. Toast : "Webhook configured successfully"

**Résultat :** Notifications automatiques activées

**Bonus :** Prochain member join → Discord reçoit :
```
🎉 New Member
PlayerName has joined Knights of Azeroth!
Guild Tracker • 2025-11-05 14:30
```

---

### Flow 11 : Officer Crée un Roster pour Encounter

**Acteur :** User (rôle Officer)

1. Navigate to "Assignations"
2. → AssignementView
3. Sidebar : liste rosters existants
4. Clique "Create New Roster"
5. Form :
   - Name : "Naxx 25 - Sapphiron"
   - Size : 25
   - Notes : "Need frost resist"
6. Submit
7. ✅ RaidRoster créé
8. Interface drag & drop affiche :
   - **Sections :**
     - [Tanks (0/3)]
     - [Healers (0/7)]
     - [DPS (0/15)]
   - **Character Pool (en bas) :**
     - Tous les personnages actifs de la guilde
9. Drag "Tank1" → Tanks slot 1
10. Drag "Healer1" → Healers slot 1
11. ... continue assigning
12. Validation banner (bottom) :
    - ⚠️ "Need 2 more tanks"
    - ⚠️ "Need 6 more healers"
13. Continue assigning jusqu'à validation ✅
14. Clique "Save Assignments"
15. ✅ 25 RosterAssignments créées
16. Roster sauvegardé
17. Clique "Export to Discord"
18. Copie texte formaté :
```
**Naxx 25 - Sapphiron**
Tanks: Tank1, Tank2, Tank3
Healers: Healer1, Healer2, ..., Healer7
DPS: DPS1, DPS2, ..., DPS15
```

**Résultat :** Roster prêt pour le raid

---

## ⏱️ Timeline & Estimation

### Vue d'ensemble

**Total : ~14-16 semaines (3.5-4 mois)**

```
Phase 0: [====]               1 week
Phase 1: [=========]          2-3 weeks
Phase 2: [============]       3-4 weeks
Phase 3: [================]   4-5 weeks
Phase 4: [========]           2-3 weeks
Phase 5: [====]               1-2 weeks
Polish:  [======]             2 weeks
```

---

### Phase 0 : Fondations Critiques
**Durée :** 1 semaine  
**Priorité :** 🔴 CRITIQUE

| Tâche | Estimation | Complexité |
|-------|------------|------------|
| F0.1 - Système permissions (Voters) | 2 jours | 🟡 Moyenne |
| F0.2 - Routes publiques | 1.5 jours | 🟢 Faible |
| F0.3 - CRUD personnages | 1.5 jours | 🟢 Faible |
| F0.4 - Améliorer modèle données | 1 jour | 🟢 Faible |
| **TOTAL** | **6 jours** | |

**Bloquant pour :** Tout le reste

---

### Phase 1 : Core Guild Management
**Durée :** 2-3 semaines  
**Priorité :** 🔴 HAUTE

| Tâche | Estimation | Complexité |
|-------|------------|------------|
| F1.1 - Dashboard avec vraies données | 3-4 jours | 🟡 Moyenne |
| F1.2 - Roster builder (drag & drop) | 5-7 jours | 🔴 Élevée |
| F1.3 - Système invitation | 1-2 jours | 🟢 Faible |
| **TOTAL** | **9-13 jours** | |

**Débloque :** Gestion quotidienne de guilde

---

### Phase 2 : Planification de Raids
**Durée :** 3-4 semaines  
**Priorité :** 🟡 MOYENNE-HAUTE

| Tâche | Estimation | Complexité |
|-------|------------|------------|
| F2.1 - Calendrier & événements | 5-7 jours | 🔴 Élevée |
| F2.1 - Système RSVP | (inclus) | 🔴 Élevée |
| F2.2 - Import événements JSON | 2-3 jours | 🟡 Moyenne |
| Testing & bug fixes | 2 jours | |
| **TOTAL** | **9-12 jours** | |

**Débloque :** Organisation des raids

---

### Phase 3 : Système DKP & Loot
**Durée :** 4-5 semaines  
**Priorité :** 🔴 CRITIQUE (pour confiance)

| Tâche | Estimation | Complexité |
|-------|------------|------------|
| F3.1 - Infrastructure DKP | 6-8 jours | 🔴 Très élevée |
| F3.1 - Standings PUBLIC | (inclus) | 🔴 Très élevée |
| F3.2 - Enregistrement loot | 2-3 jours | 🟡 Moyenne |
| F3.3 - Attribution auto DKP | 2 jours | 🟡 Moyenne |
| Testing approfondi | 3-4 jours | |
| **TOTAL** | **13-17 jours** | |

**Note :** Feature la plus complexe mais aussi la plus importante

---

### Phase 4 : Analytics & Insights
**Durée :** 2-3 semaines  
**Priorité :** 🟡 MOYENNE

| Tâche | Estimation | Complexité |
|-------|------------|------------|
| F4.1 - Statistiques de raid | 4-5 jours | 🔴 Élevée |
| F4.2 - Rapports de guilde | 3-4 jours | 🟡 Moyenne |
| F4.2 - Activity logging | (inclus) | 🟡 Moyenne |
| **TOTAL** | **7-9 jours** | |

**Débloque :** Insights et décisions data-driven

---

### Phase 5 : Communication & Intégrations
**Durée :** 1-2 semaines  
**Priorité :** 🟢 BASSE

| Tâche | Estimation | Complexité |
|-------|------------|------------|
| F5.1 - Notifications Discord | 3-4 jours | 🟡 Moyenne |
| F5.1 - Intégration controllers | (inclus) | 🟢 Faible |
| **TOTAL** | **3-4 jours** | |

**Bonus :** Nice to have, pas bloquant

---

### Phase Polish & Production
**Durée :** 2 semaines  
**Priorité :** 🔴 CRITIQUE

| Tâche | Estimation |
|-------|------------|
| Tests end-to-end complets | 3 jours |
| Documentation API (Swagger) | 2 jours |
| Guide utilisateur | 1 jour |
| Corrections bugs | 3 jours |
| Optimisations performance | 2 jours |
| Audit sécurité | 1 jour |
| Préparation déploiement | 1 jour |
| **TOTAL** | **13 jours** |

---

### Ordre de Développement Recommandé

#### Semaines 1-2 : Fondations & Core
- [ ] Phase 0 (1 sem)
- [ ] Début Phase 1 (1 sem)

#### Semaines 3-4 : Core Management
- [ ] Fin Phase 1 (1-2 sem)
- [ ] Dashboard fonctionnel
- [ ] Roster builder opérationnel

**Milestone 1 :** Application utilisable pour gestion basique

#### Semaines 5-8 : Raids & Events
- [ ] Phase 2 complète (3-4 sem)
- [ ] Calendrier + RSVP
- [ ] Import JSON

**Milestone 2 :** Raids organisables

#### Semaines 9-13 : DKP System
- [ ] Phase 3 complète (4-5 sem)
- [ ] DKP infrastructure
- [ ] Loot tracking
- [ ] **Standings PUBLIC**

**Milestone 3 :** Transparence totale sur loot

#### Semaines 14-16 : Analytics & Polish
- [ ] Phase 4 (2-3 sem)
- [ ] Phase 5 (1-2 sem en parallèle)
- [ ] Polish final (2 sem)

**Milestone 4 :** Application production-ready

---

## 🎯 Métriques de Succès

### Critères de Validation par Phase

#### Phase 0
- [ ] Tests: Permissions fonctionnent pour tous les rôles
- [ ] Tests: Visiteur peut voir guilde publique
- [ ] Tests: CRUD personnages complet
- [ ] Tests: Migrations appliquées sans erreur

#### Phase 1
- [ ] Tests: Dashboard affiche vraies stats
- [ ] Tests: Roster drag & drop fonctionne
- [ ] Tests: Validation composition correcte
- [ ] User feedback: "Dashboard utile"

#### Phase 2
- [ ] Tests: Événements créés apparaissent dans calendrier
- [ ] Tests: Signups fonctionnent
- [ ] Tests: Import JSON valide crée events
- [ ] User feedback: "Simple de planifier raids"

#### Phase 3 (CRITIQUE)
- [ ] **Tests: Visiteur non-loggué voit standings DKP**
- [ ] Tests: DKP correctement calculés
- [ ] Tests: Loot enregistré déduit DKP
- [ ] Tests: Decay appliqué correctement
- [ ] User feedback: "Système transparent et équitable"

#### Phase 4
- [ ] Tests: Stats présence correctes
- [ ] Tests: Loot distribution charts affichés
- [ ] User feedback: "Insights utiles"

#### Phase 5
- [ ] Tests: Discord notifications reçues
- [ ] Tests: Webhook test fonctionne
- [ ] User feedback: "Pratique"

---

## 📊 KPIs Globaux

### Métriques Techniques
- [ ] 80%+ couverture tests (backend + frontend)
- [ ] < 200ms temps réponse API (p95)
- [ ] 0 vulnérabilités critiques (audit)
- [ ] 100% endpoints documentés (Swagger)
- [ ] Lighthouse score > 90

### Métriques Produit
- [ ] 100% Phase 0, 1, 2, 3 complètes
- [ ] 80%+ Phase 4, 5 complètes
- [ ] Documentation utilisateur complète
- [ ] < 5 bugs critiques en production

### Métriques Utilisateur (post-launch)
- [ ] 10+ guildes actives (1er mois)
- [ ] 100+ personnages trackés
- [ ] 50+ raids créés
- [ ] 500+ transactions DKP
- [ ] Taux retention > 70% (semaine 2)

---

## 🚀 Stratégie de Lancement

### Soft Launch (Beta)
**Semaine 15-16**

- Invite 3-5 guildes beta testers
- Feedback intensif
- Bug fixes rapides
- Monitoring performance

### Public Launch
**Semaine 17**

- Annonce sur forums WoW
- Post Reddit /r/classicwow
- Discord communities
- Documentation complète live
- Support actif

### Post-Launch (v1.1+)
**Mois 2-3**

- Blizzard API integration (armory)
- WarcraftLogs integration
- Application system (recrutement)
- Mobile app (future)

---

## 📝 Notes Finales

### Technologies Recommandées

**Backend**
- Symfony 6.4+ (PHP 8.2+)
- Doctrine ORM
- PHP CS Fixer
- PHPUnit
- Redis (caching)
- Symfony Messenger (queues)

**Frontend**
- Vue 3 + TypeScript
- Vite
- Pinia (state)
- Chart.js (graphiques)
- V-Calendar (calendrier)
- VueDraggable (roster)
- Vitest (tests)
- Playwright (E2E)

**Infrastructure**
- Docker (dev + prod)
- PostgreSQL 15+
- Nginx
- Cloudflare (CDN + DDoS)

### Principes de Développement

1. **Test-Driven** : Tests avant code
2. **Iterative** : Livrer par petites increments
3. **User feedback** : Tester avec vrais users
4. **Documentation** : Code documenté au fur et à mesure
5. **Performance** : Optimiser dès le début
6. **Security** : Voters + validation partout

### Contact & Support

Pour contribuer au projet :
- GitHub : [repository URL]
- Discord : [server invite]
- Email : [support email]

---

**Dernière mise à jour :** 2025-10-27  
**Version :** 2.0.0 (Roadmap complète)  
**Statut :** 🟢 Prêt pour développement  

**Créé par :** Claude (Anthropic)  
**Basé sur :** Analyse approfondie codebase + besoins réels guildes WoW

---

# 🎉 Bonne chance pour le développement !

N'oubliez pas : **La TRANSPARENCE est la clé**. Le DKP public n'est pas une feature, c'est la fondation de la confiance dans votre guilde.

