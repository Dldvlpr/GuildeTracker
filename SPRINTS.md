# Sprints — Guild Tracker

Consolidation des sprints d'après README_FULL.md et ROADMAP.md. Ordre, durées et objectifs clés récapitulés pour planifier l'exécution.

## Vue d'ensemble

- Sprint 0: 1 semaine
- Sprint 1: 2–3 semaines
- Sprint 2: 1–2 semaines
- Sprint 3: 3–4 semaines
- Sprint 4: 4–5 semaines
- Sprint 5: 2–3 semaines
- Sprint 6: 1–2 semaines
- Sprint Polish: 2 semaines

---

## Sprint 0 — Fondations

- Durée: 1 semaine · Priorité: Critique
- Objectifs:
  - Permissions complètes via Voters; décommenter/renforcer les checks d'accès
  - Routes publiques et visibilité guilde (`is_public`, `show_dkp_public`, `recruiting_status`)
  - CRUD Personnages complet (ajout des `PUT/DELETE`), validations
  - Enrichir le modèle (level, ilvl, status) et migrations
- Résultat attendu: Base sécurisée, données cohérentes, pages publiques contrôlées

---

## Sprint 1 — Core Guild Management

- Durée: 2–3 semaines · Priorité: Haute
- Objectifs:
  - Dashboard guilde avec vraies statistiques (service + DTO)
  - Roster Builder (drag & drop) avec entités `raid_roster`, `roster_assignment` et validations de compo
  - Système d'invitations (création, envoi, acceptation)
- Résultat attendu: Gestion quotidienne de guilde opérationnelle

---

## Sprint 2 — Intégration Blizzard

- Durée: 1–2 semaines · Priorité: Haute
- Objectifs:
  - OAuth Battle.net: endpoints `GET /api/oauth/blizzard/connect` et callback; scopes `wow.profile`, `profile-{region}`
  - Lier le compte Blizzard à l'utilisateur; stockage sécurisé des tokens; config `BLIZZARD_REGION` et `BLIZZARD_LOCALE`
  - Import des personnages via `GET /profile/user/wow` → `POST /api/sync/blizzard/characters`
  - Claim guilde: `POST /api/guild/{id}/claim` en vérifiant GM/Officer via `GET /data/wow/guild/{realmSlug}/{guildSlug}/roster`
  - Sync roster: `POST /api/guild/{id}/sync-roster` (ajouts/retraits, mise à jour level/ilvl/realm)
  - Gestion d'erreurs/rate‑limit et caching léger des réponses (si nécessaire)
- Résultat attendu: Personnages importés automatiquement, rôle GM/Officer vérifié, roster synchronisé

---

## Sprint 3 — Planification de Raids

- Durée: 3–4 semaines · Priorité: Moyenne-Haute
- Objectifs:
  - Calendrier & événements (V-Calendar), CRUD `raid_event`
  - Système d'inscriptions/RSVP (`event_signup`) et états
  - Import/seed d'événements (JSON) et qualité de vie
  - Testing ciblé et corrections
- Résultat attendu: Organisation et visibilité des raids

---

## Sprint 4 — DKP & Loot

- Durée: 4–5 semaines · Priorité: Critique
- Objectifs:
  - Infrastructure DKP: tables `dkp_pool`, `dkp_account`, `dkp_transaction`; standings publics
  - Enregistrement du loot et attribution automatique par boss
  - Exports CSV/PDF (standings/historiques) si temps
  - Tests approfondis du calcul et des règles
- Résultat attendu: Transparence et confiance sur les attributions

---

## Sprint 5 — Analytics & Rapports

- Durée: 2–3 semaines · Priorité: Moyenne
- Objectifs:
  - Statistiques de raid (présence, progression, distributions)
  - Rapports de guilde (tableaux/graphiques)
  - Activity logging des opérations clés
- Résultat attendu: Aide à la décision data‑driven

---

## Sprint 6 — Communication & Intégrations

- Durée: 1–2 semaines · Priorité: Basse
- Objectifs:
  - Notifications Discord via webhooks par guilde; intégration des contrôleurs
  - Pages publiques (exposition contrôlée du roster/DKP)
- Résultat attendu: Communication externe et engagement

---

## Sprint Polish — Production

- Durée: 2 semaines · Priorité: Critique
- Objectifs:
  - Tests end‑to‑end complets (Playwright)
  - Documentation API (Swagger) et guide utilisateur
  - Corrections de bugs, optimisations de performance
  - Audit sécurité et check‑list de release
- Résultat attendu: Qualité production et lancement serein
