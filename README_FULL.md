# Guild Tracker — README + ROADMAP consolidés

Plateforme de gestion de guilde World of Warcraft (multi‑guilde) centrée sur la transparence: roster, personnages, rôles, DKP et rapports — avec authentification Discord et intégration planifiée à l’API Battle.net/Blizzard.

Note API Blizzard: l’API est en lecture seule. On ne peut ni créer une guilde ni devenir GM via l’API. Le projet synchronise et vérifie des données existantes (personnages, roster, raids/boss/objets) pour automatiser et fiabiliser la gestion.

---

## Vision & Principes

- Transparence par défaut: standings DKP et historiques visibles aux membres, options publiques activables par guilde.
- Permissions par rôles: GM > Officer > Member > Public.
- Centré workflow raiding: planifier → raider → distribuer loot/DKP → analyser.
- Architecture multi‑guilde: un utilisateur peut appartenir à plusieurs guildes; bascule d’une guilde à l’autre.

---

## État Actuel (réalisé)

Backend (Symfony)
- Auth Discord OAuth: complet (KnpU OAuth2, sessions)
- Entités & Repositories: `User`, `GameGuild`, `GuildMembership`, `GameCharacter`, `GuildInvitation`
- Contrôleurs: Guild, Members, Characters, Invitations (CRUDs principaux)
- Voters: présents (Guild/Character/Membership) et utilisés dans le code (à finaliser sur certains contrôleurs)
- DTOs: `GameGuildDTO`, `GuildMembershipDTO`, `CharacterDTO`
- CORS/Twig/Doctrines/Fixtures: configurés

Frontend (Vue 3 + Vite)
- Vues: Dashboard Guilde, Liste/Cartes Personnages, DKP, Calendrier, Stats/Rapports, Notifications, Invitations, Création de guilde
- Services API: `auth`, `gameGuild`, `guildMembership`, `character`, `guildInvitation`
- Store utilisateur (Discord), router configuré, assets de base

Infrastructure
- Dockerfiles (frontend, bot), docker‑compose
- Roadmap détaillée (ancien+nouveau), migrations Doctrine récentes

Ce qui manque encore (priorités proches)
- Personnages: endpoints `PUT/DELETE`
- Vérifications d’accès: décommenter/distiller `denyAccessUnlessGranted` partout
- Pages publiques (routes sans auth) contrôlées par `is_public`/`show_dkp_public`
- DKP/Rosters/Calendrier/Analytics: écrans branchés aux vraies données

---

## Intégration Blizzard (plan)

Objectifs
- Lier un compte Blizzard (OAuth) pour récupérer les personnages du joueur
- Vérifier le rôle en jeu (GM/Officer) pour « revendiquer » une guilde dans l’app
- Synchroniser le roster guilde (ajouts/retraits, level/ilvl)
- Alimenter DKP via référentiels raids/boss (Journal) et lookup d’objets

API cibles
- Profil utilisateur: `GET /profile/user/wow` (scope `wow.profile`, `profile-{region}`)
- Roster guilde: `GET /data/wow/guild/{realmSlug}/{guildSlug}/roster` (`profile-{region}`)
- Référentiels: `journal-instance`, `journal-encounter`, `item` (`static-{region}`)

Contraintes
- Lecture seule (pas de création guilde, pas de changement de rôle en jeu)
- Région/locale: `BLIZZARD_REGION` (`eu|us|...`) et `BLIZZARD_LOCALE` (ex: `fr_FR`)

Endpoints prévus
- `GET /api/oauth/blizzard/connect` / `GET /api/oauth/blizzard/callback`
- `POST /api/sync/blizzard/characters`
- `POST /api/guild/{id}/claim` (vérifier GM/Officer via roster Blizzard)
- `POST /api/guild/{id}/sync-roster`

---

## Fonctionnalités (global)

Implémenté
- Auth Discord, multi‑guilde, CRUD membres, création/listing/détails de guilde, ajout de personnages, filtres/recherche

En cours / À livrer
- Personnages: édition/suppression
- Pages publiques: `/api/public/guilds`, `/api/public/guild/{id}` (+ roster, DKP si activé)
- Blizzard: OAuth + import persos + claim/sync roster
- DKP: pools/comptes/transactions, attribution par boss, historique loot/exports
- Calendrier & inscriptions, Roster Builder Drag&Drop
- Analytics: dashboard (stats, distributions, activité), rapports (présence/loot/progression)
- Notifications Discord: webhooks par guilde

---

## Architecture

Backend (backend/)
- Symfony 6, Doctrine, Voters, DTOs
- Controllers REST (guild, membership, character, invitation)
- Configs: CORS, Security, OAuth Discord, Migrations

Frontend (frontend/)
- Vue 3 + Vite, Router, Store user
- Vues: Dashboard, Players, DKP, Calendar, Reports, Notifications, Invitations, CreateGuild
- Services API: `services/*.ts`

Docker (docker/)
- docker‑compose, Dockerfiles frontend/bot

---

## Démarrage rapide

Backend
- PHP 8.2+, Composer, Symfony CLI, PostgreSQL
- `composer install` → `doctrine:migrations:migrate` → `symfony server:start -d`

Frontend
- Node 18+, npm
- `npm install` → `npm run dev`

Docker (optionnel)
- `docker compose up -d`

---

## Variables d’environnement (exemples)

Backend (`backend/.env.local`)
- `DATABASE_URL=postgresql://user:pass@127.0.0.1:5432/guildtracker?serverVersion=15&charset=utf8`
- Discord: `OAUTH_DISCORD_CLIENT_ID`, `OAUTH_DISCORD_CLIENT_SECRET`
- Blizzard (prévu): `OAUTH_BNET_CLIENT_ID`, `OAUTH_BNET_CLIENT_SECRET`, `BLIZZARD_REGION`, `BLIZZARD_LOCALE`

Frontend (`frontend/.env`)
- `VITE_API_BASE=http://localhost:8000`

---

## Permissions & Rôles

- GM: gestion complète guilde, rôles, settings, suppression; peut éditer tout personnage
- Officer: events/rosters, attribution DKP, loot
- Member: voir toutes les données internes, gérer ses persos, s’inscrire
- Public (si activé): page guilde, roster, standings DKP

Voters principaux
- `GuildVoter`: VIEW/MANAGE/DELETE
- `CharacterVoter`: VIEW/CREATE/EDIT/DELETE (owner ou GM)
- `MembershipVoter`: VIEW/MANAGE (GM)

---

## Endpoints (résumé)

Implémenté (exemples)
- Guildes: `GET /api/guild`, `POST /api/guild`, `GET /api/guild/{id}`
- Membres: `POST /api/guild/{id}/members`, `DELETE /api/guild/{id}/members/{mid}`, `PUT /api/guild/{id}/members/{mid}/role`
- Personnages: `POST /api/guild/{id}/characters`, `GET /api/guild/{id}/characters`

Backlog prioritaire
- Personnages: `PUT /api/guildcharacter/{id}`, `DELETE /api/guildcharacter/{id}`
- Public: `GET /api/public/guilds`, `GET /api/public/guild/{id}`, `/roster`, `/dkp`
- Blizzard: OAuth + import persos, claim/sync roster
- DKP: CRUD pools/comptes/transactions, standings, exports
- Events/Rosters: CRUD events/signups/assignments, validations composition
- Analytics: `GET /api/guild/{id}/analytics/dashboard`

---

## Modèle de données (extraits)

- `game_guild`: id, name, faction, region (à ajouter), realm (à ajouter), visibility (à ajouter), timestamps
- `guild_membership`: id, user_id, guild_id, role, joined_at/left_at (timestamps à ajouter)
- `game_character`: id, guild_id, owner (nullable), name, class, role, level/ilvl (à ajouter), status enum, timestamps
- DKP (à créer): `dkp_pool`, `dkp_account`, `dkp_transaction` (+ `item_id` WoW et `item_level` stockés)

Migrations à jour dans `backend/migrations/` (ex: 20251028152605).

---

## Roadmap (phases)

Phase 0 — Fondations (1 semaine)
- Permissions complètes via Voters; décommenter tous les checks d’accès
- Routes publiques et visibilité guilde (`is_public`, `show_dkp_public`, `recruiting_status`)
- CRUD personnages complet (PUT/DELETE); enrichir entités (level, ilvl, status)

Phase 1 — Core Guild Management (2–3 semaines)
- Dashboard guilde avec vraies stats (service + DTO)
- Roster Builder (entités `raid_roster`, `roster_assignment`, validations)

Phase 2 — Calendrier & Inscriptions (2–3 semaines)
- Entités `raid_event`, `event_signup` + vues calendrier (V-Calendar)

Phase 3 — DKP & Loot (2–3 semaines)
- Tables DKP, attribution par boss, historique loot, standings, exports CSV/PDF

Phase 4 — Analytics & Rapports (2 semaines)
- Présence, progression, distributions, rapports de guilde

Phase 5 — Communication & Intégrations (1–2 semaines)
- Webhooks Discord, messages paramétrables, pages publiques

---

## Monétisation (pistes)

- Free: gestion guilde, import persos, roster/players, page publique basique
- Pro: sync roster auto, decay DKP, exports, analytics avancés, notifications, templates, multi‑raid
- Business: branding, sous‑domaine, quotas élevés, support prioritaire, intégrations (WarcraftLogs)

---

## Prochains pas concrets

1) Ajouter config OAuth Blizzard + endpoints `connect/callback`
2) Endpoint `POST /api/sync/blizzard/characters` + mapping `GameCharacter`
3) `POST /api/guild/{id}/claim` + promotion GM/Officer si prouvé par roster Blizzard
4) `POST /api/guild/{id}/sync-roster` (ajouts/retraits, niveau/ilvl)
5) Activer pages publiques et sécuriser tous les contrôleurs par Voters

---

## Références internes

- Code Backend: `backend/src/*`
- Frontend: `frontend/src/*`
- Roadmap détaillée d’origine: `ROADMAP.md`
