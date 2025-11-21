# Workflows de développement

Ce guide compile les actions récurrentes pour travailler efficacement sur GuildeTracker : commandes utiles, gestion de la base, qualité, intégrations externes et préparation d’une mise en test.

## 1. Commandes rapides (backend/Makefile)

| Commande | Description |
| --- | --- |
| `make install` | Installe les dépendances Composer. |
| `make db-init` | Lance `app:init-database` (création, migrations, import raids + realms). |
| `make db-reset` | Même chose avec option `--drop` (⚠️ supprime toutes les données). |
| `make db-import-wow` | Réimporte uniquement les raids/bosses Blizzard. |
| `make db-status` | Résumé migrations + stats raids/bosses. |
| `make serve`, `make serve-bg`, `make serve-tls` | Variantes pour démarrer le serveur Symfony (foreground, background, p12 mkcert). |
| `make stop` | Stoppe le serveur Symfony. |
| `make test` | Exécute la suite PHPUnit. |
| `make traefik-start` / `make traefik-stop` | Proxy TLS local (voir `docs/TRAEFIK_INFRA.md`). |

> Astuce : `make help` liste toutes les cibles disponibles avec un descriptif formaté.

## 2. Gestion de la base & des données WoW
- **Initialisation complète** : `php bin/console app:init-database` (options `--skip-wow-data`, `--skip-realms` si nécessaire). Cette commande orchestre toutes les étapes et affiche un plan d’exécution.
- **Migrations Doctrine** : créez une nouvelle migration via `php bin/console make:migration` puis `php bin/console doctrine:migrations:migrate`.
- **Fixtures** : contrôlez leur volume avec les variables `FIXTURES_*` dans `backend/.env.local` (utilisateur par défaut, nombre de guildes, etc.), puis `php bin/console doctrine:fixtures:load`.
- **Imports spécifiques** :
  - `php bin/console app:import-wow-raids-db --truncate` pour rafraîchir la liste des raids/bosses.
  - `php bin/console app:populate-realm-metadata` pour regénérer les realms Blizzard (utile quand Blizzard ajoute de nouveaux serveurs).

## 3. Frontend & qualité
- **Serveur dev** : `npm run dev -- --host` (HTTPS auto si `frontend/certs/*.pem` existent).
- **Tests unitaires** : `npm run test` (Vitest) ou `npm run test:watch`.
- **Lint/format** : `npm run lint` (ESLint + fix auto) et `npm run format` (Prettier sur `src/`).
- **Build prod** : `npm run build` génère les assets (assure-vous que le proxy Vite est désactivé en prod).

## 4. Tests backend et outils
- **Unitaires / fonctionnels** : `php bin/phpunit`.
- **Logs** : `make logs` (alias `tail -f var/log/dev.log`) pour suivre les requêtes ou les erreurs OAuth.
- **Cache & var** : `make clean` supprime `var/cache/*` et `var/log/*` – pratique après un changement majeur de config.

## 5. Authentification & intégrations
- **Discord OAuth2** :
  - Limiteurs configurés (`discordOauthStartLimiter`, `discordOauthCallbackLimiter`). Si vous obtenez `Too many requests`, patientez ou ajustez la config RateLimiter.
  - Les redirections de succès/erreur proviennent de `FRONT_SUCCESS_URI`/`FRONT_ERROR_URI`.
- **Battle.net** :
  - `BlizzardService::getValidAccessToken()` rafraîchit automatiquement les tokens. Sur un token expiré, `/api/guilds/{id}/sync` retourne `blizzard_token_expired` + `reconnect_url`.
  - Pour rejouer un onboarding complet, supprimez les tokens dans la table `user` ou régénérez vos credentials.

## 6. Synchronisation du roster
Flux recommandé :
1. L’utilisateur claim la guilde (`POST /api/guilds`).
2. Il corrige manuellement les rôles via `/api/guilds/{guildId}/characters/{characterId}/role`.
3. Programmez des sync régulières (bouton UI ou CRON) vers `/api/guilds/{guildId}/sync`. Les rôles `Unknown` seront écrasés, les rôles manuels sont conservés (voir `backend/docs/GUILD_SYNC_API.md`).
4. Utilisez les invitations (`/api/guilds/{id}/invitations`) pour onboarder d’autres officiers qui pourront lancer leurs propres sync.

## 7. Checklist avant merge/déploiement
1. `php bin/phpunit` et `npm run test` passent.
2. `npm run lint` + `composer run-script auto-scripts` (si vous avez ajouté des assets ou des templates).
3. `php bin/console doctrine:migrations:diff` ne génère pas de changement inattendu (ou committez la migration).
4. Revue des variables `.env` : ne committez jamais les secrets réels (`.env.local` doit rester gitignored).
5. Si vous touchez à la sécurité (CSP, Traefik), vérifiez le comportement sur `https://$TRAEFIK_FRONT_HOST` et dans le dashboard Traefik.

## 8. Ressources utiles
- `docs/PROJECT_OVERVIEW.md` pour recadrer le périmètre fonctionnel.
- `docs/GETTING_STARTED.md` si vous onboardez un nouveau contributeur.
- `docs/TRAEFIK_INFRA.md` pour toutes les opérations reverse proxy.
- `backend/docs/*` pour les détails bas niveau (schéma, architecture Blizzard, guides de refactor).
