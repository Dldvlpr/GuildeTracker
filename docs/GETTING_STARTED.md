# Démarrer GuildeTracker en local

> Cette procédure couvre l’installation complète (backend Symfony + frontend Vue + proxy Traefik optionnel). Toutes les commandes sont à exécuter depuis la racine du dépôt sauf mention contraire.

## 1. Prérequis système
- **PHP 8.2+**, Composer 2.x et l’outil `symfony` CLI (pour `symfony server:*`).
- **PostgreSQL 15/16** (ou autre SGBD supporté par Doctrine) avec un utilisateur disposant des droits de création de base.
- **Node.js 20+** (22 recommandé pour correspondre au `tsconfig`), npm 10+.
- **mkcert** (ou des certificats HTTPS équivalents) si vous activez Traefik ou le serveur Vite en HTTPS.
- **Traefik v3** installé localement si vous souhaitez le proxy inverse fourni (`infra/traefik`).

## 2. Préparer les fichiers d’environnement
1. Créez les variantes locales à partir des exemples :
   ```bash
   cp .env .env.local
   cp backend/.env backend/.env.local        # si besoin d’une base propre
   cp frontend/.env frontend/.env.local      # optionnel
   ```
2. Dans `.env.local` (racine), renseignez les hôtes et certificats utilisés par Traefik :
   ```
   TRAEFIK_FRONT_HOST=app.localhost
   TRAEFIK_API_HOST=api.localhost
   TRAEFIK_FRONTEND_URL=https://127.0.0.1:5173
   TRAEFIK_BACKEND_API_URL=https://127.0.0.1:8000
   TRAEFIK_TLS_CERT_FILE="/abs/path/to/certs/localhost.pem"
   TRAEFIK_TLS_KEY_FILE="/abs/path/to/certs/localhost-key.pem"
   TRAEFIK_CA_ROOT="/abs/path/to/mkcert/rootCA.pem"
   TRAEFIK_CSP_CONNECT_SRC="https://localhost:5173 https://localhost:8000"
   ```
3. Dans `backend/.env.local`, configurez au minimum :
   - `DATABASE_URL` (ex: `postgresql://user:pass@127.0.0.1:5432/guildetracker?serverVersion=16&charset=utf8`).
   - `FRONT_ORIGIN`, `FRONT_SUCCESS_URI`, `FRONT_ERROR_URI` (souvent `https://localhost:5173`).
   - Crédentials OAuth (Discord et Battle.net) si vous testez les flux d’authentification.

## 3. Installer les dépendances
```bash
cd backend && composer install
cd ../frontend && npm install
```
> Astuce : `make install` dans `backend/` effectue l’installation Composer et affiche l’aide des autres commandes.

## 4. Initialiser la base de données
1. Démarrez PostgreSQL (ou votre SGBD équivalent).
2. Depuis `backend/`, exécutez :
   ```bash
   php bin/console doctrine:database:create --if-not-exists
   php bin/console doctrine:migrations:migrate
   php bin/console app:init-database    # crée le schéma et importe raids/realms
   ```
   - Ajoutez `--drop` si vous voulez repartir d’une base vide.
   - Utilisez `--skip-wow-data` ou `--skip-realms` si vous n’avez pas besoin des imports Blizzard pendant vos tests.
3. (Optionnel) Chargez des fixtures personnalisées via `php bin/console doctrine:fixtures:load`.

## 5. Lancer les serveurs applicatifs
### Backend Symfony
```bash
cd backend
make serve            # alias de symfony server:start
```
- HTTPS est disponible sur `https://127.0.0.1:8000` (ou `https://api.localhost:8000` si vous utilisez `make serve-tls` avec les certificats mkcert).
- Utilisez `make serve-bg` pour lancer le serveur en arrière-plan et `make stop` pour l’arrêter proprement.

### Frontend Vite
```bash
cd frontend
npm run dev -- --host
```
- Le fichier `vite.config.ts` active automatiquement HTTPS s’il trouve `certs/localhost.pem` et `localhost-key.pem`. Sinon Vite fonctionnera en HTTP.
- Le proxy Vite redirige `/api` et `/connect` vers `https://127.0.0.1:8000`, ce qui évite les soucis de CORS pendant le développement.

### Traefik (optionnel mais recommandé pour tester la prod locale)
Voir `docs/TRAEFIK_INFRA.md` pour les détails. En bref :
```bash
cd backend
make traefik-start    # wrapper sur infra/traefik/start-traefik.sh
```
Puis accédez à :
- Frontend via `https://$TRAEFIK_FRONT_HOST`
- API via `https://$TRAEFIK_API_HOST`

## 6. Vérifications rapides
- `symfony server:status` confirme que l’API tourne et vous donne l’URL exacte.
- `curl https://127.0.0.1:8000/api/guilds/exists?name=Test` doit répondre (401 si non authentifié, ce qui prouve que l’API écoute).
- `npm run lint` (frontend) et `php bin/phpunit` (backend) doivent réussir avant de pousser.

## 7. OAuth2 & intégrations externes
- **Discord** : créez une application Discord, autorisez l’URL de callback `https://localhost:8000/connect/discord/check` et remplissez `OAUTH_DISCORD_*`.
- **Blizzard** : configurez un client Battle.net (region/locale dans `backend/.env.local`) pour activer `POST /api/guilds/{id}/sync`.
- **Certificats** : exécutez `mkcert -install` puis `mkcert app.localhost api.localhost localhost 127.0.0.1 ::1` afin d’obtenir les fichiers attendus par Traefik et Vite.

## 8. Prochaines étapes
- Consultez `docs/DEVELOPMENT_WORKFLOWS.md` pour les commandes récurrentes (imports, migrations, tests).
- Parcourez `backend/docs/*` pour approfondir l’architecture Roster & Blizzard.
- Si vous préparez une démo, script Traefik + comptes OAuth préconfigurés vous donneront un environnement quasi production.
