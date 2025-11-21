# GuildeTracker — Vue d'ensemble

## Mission du produit
- Centraliser les données des guildes World of Warcraft (Retail et Classic) et les synchroniser avec les APIs officielles Blizzard.
- Offrir un outil de planification de raids (drag & drop, templates publics, partage de stratégies) connecté au roster de la guilde.
- Faciliter l’onboarding des joueurs via Discord OAuth2, les invitations sécurisées et la gestion fine des rôles et permissions.
- Servir un front moderne (Vue 3 + Vite + Pinia) consommant une API Symfony 7 robuste, sécurisée par Traefik et des en-têtes CSP stricts.

## Composants principaux

| Dossier | Stack | Responsabilités |
| --- | --- | --- |
| `backend/` | PHP 8.2, Symfony 7.3, Doctrine ORM, PostgreSQL | API REST, intégrations Blizzard & Discord, commandes d’import WoW, sécurité, migrations & fixtures. |
| `frontend/` | Vue 3, Vite 7, TypeScript, Tailwind | Interface raid planner et gestion de guilde, proxy local HTTPS avec certificats `certs/`, consommation de l’API `/api`. |
| `bot/` | Node.js 18+/24 (Docker multi-stage) | Automatisation Discord/raid (placeholder : Dockerfile prêt pour un bot temps réel). |
| `infra/traefik/` | Traefik v3, scripts shell | Reverse proxy local (TLS, CSP, rate limiting). Voir `docs/TRAEFIK_INFRA.md`. |
| `docker/` | Docker Compose | Point d’entrée pour futurs stacks conteneurisés (actuellement squelette). |
| `docs/` & `backend/docs/` | Markdown | Base de connaissance (schéma BDD, guild sync, sécurité, etc.). |

## Vue architecture
```
[Navigateur]
    │ HTTPS (Traefik) - hôtes front/api distincts
    ▼
[Traefik] ──→ [Frontend Vite Dev Server] (https://127.0.0.1:5173)
    │
    └────→ [Backend Symfony] (https://127.0.0.1:8000) ── PostgreSQL
                            │
                            ├── Blizzard APIs (Roster, OAuth)
                            └── Discord OAuth2
```

## Capacités clé (API & Front)
- **Guildes** : création, édition, lecture, validation d’existence (`/api/guilds`, `GameGuildController`), règles `GUILD_VIEW`/`GUILD_MANAGE`.
- **Roster** : synchronisation depuis Blizzard avec conservation des rôles manuels (`/api/guilds/{id}/sync`, `BlizzardService`, voir `backend/docs/GUILD_SYNC_API.md`), listing de personnages et mapping classes (`WowClassMapper`).
- **Membres et invitations** : invitations à durée limitée (`GuildInvitationController`), rôles `GuildRole`, révocation de membres et purge des personnages liés (`GuildMembershipController`).
- **Planification de raids** : CRUD sur `RaidPlan`, templates publics, lien de partage, stockage JSON (métadonnées + blocs limités à 500KB) et autorisations par guilde.
- **Données WoW** : commandes `app:import-wow-raids-db` et `app:populate-realm-metadata`, endpoints `WowDataController` exposant raids/boss pour l’UI.
- **Auth & sécurité** : login Discord (PKCE, rate limiting), tokens Battle.net, rate limit Traefik, CSP strictes (`infra/traefik/dynamic/security.yml`), headers forcés (STS, referrer policy).

## Données & stockage
- PostgreSQL 16+ recommandé (configurable via `DATABASE_URL`), entités principales : `GameGuild`, `GameCharacter`, `GuildMembership`, `GuildInvitation`, `User`, `BlizzardGameRealm`, `RaidPlan`.
- Migrations Doctrine (`migrations/`), commande d’initialisation `app:init-database` pour créer schéma + importer raids/realms.
- Fichiers volumineux (raid blocks/metadata) stockés en JSONB côté base pour conserver l’historique des compositions.

## Intégrations externes
- OAuth2 Discord (`knpuniversity/oauth2-client-bundle`, endpoints `/connect/discord` + `/logout`).
- OAuth2 Battle.net pour récupérer tokens de roster, géré dans `BlizzardService` avec rafraîchissement et stockage chiffré en base.
- Traefik agit comme proxy local TLS et ajoute les en-têtes sécurité (HSTS, CSP, Permissions-Policy) ainsi que des rate limits dédiées OAuth/API.

## Ressources complémentaires
- `docs/GETTING_STARTED.md` : installation locale end-to-end.
- `docs/TRAEFIK_INFRA.md` : configuration du reverse proxy.
- `docs/DEVELOPMENT_WORKFLOWS.md` : commandes récurrentes et bonnes pratiques.
- `backend/docs/*` : documentation approfondie (schéma BDD, architecture realms, refactor Blizzard, etc.).
