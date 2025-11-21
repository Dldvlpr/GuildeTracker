# Exploitation de Traefik et de l’infra locale

Traefik sert de reverse proxy TLS entre le navigateur, le frontend Vite et l’API Symfony. Il ajoute des en-têtes de sécurité, applique des quotas et vous permet de tester des noms de domaines proches de la production.

## 1. Préparer l’environnement
1. Copiez `.env` → `.env.local` à la racine (si ce n’est pas déjà fait).
2. Renseignez les variables suivantes (chemins absolus recommandés) :

| Variable | Rôle |
| --- | --- |
| `TRAEFIK_FRONT_HOST` | Nom DNS utilisé pour le frontend (ex: `app.localhost`). |
| `TRAEFIK_API_HOST` | Nom DNS pour l’API (ex: `api.localhost`). |
| `TRAEFIK_FRONTEND_URL` | URL réelle du serveur Vite (souvent `https://127.0.0.1:5173`). |
| `TRAEFIK_BACKEND_API_URL` | URL réelle du serveur Symfony (`https://127.0.0.1:8000`). |
| `TRAEFIK_TLS_CERT_FILE` / `TRAEFIK_TLS_KEY_FILE` | Certificats utilisés par Traefik pour servir les hôtes déclarés. |
| `TRAEFIK_CA_ROOT` | Certificat racine mkcert (utilisé pour faire confiance aux upstreams HTTPS). |
| `TRAEFIK_CSP_CONNECT_SRC` | Entrées additionnelles injectées dans la CSP `connect-src`. |

> Astuce : stockez vos certificats dans `frontend/certs` ou un dossier central `~/.config/mkcert` et référencez-les dans `.env.local`.

## 2. Générer/installer les certificats
```bash
mkcert -install
mkcert app.localhost api.localhost localhost 127.0.0.1 ::1
```
- Copiez les `.pem` obtenus vers les chemins utilisés par Traefik.
- Importez le certificat racine mkcert dans votre OS pour éviter les avertissements TLS.

## 3. Démarrer/arrêter Traefik
Depuis `backend/` :
```bash
make traefik-start   # wrap sur infra/traefik/start-traefik.sh
make traefik-stop    # wrap sur infra/traefik/stop-traefik.sh
```
Le script `start-traefik.sh` :
- Charge `.env.local` (racine du repo) et exporte les variables.
- Vérifie la présence des certificats/TLS/CA.
- Lance Traefik via `sudo -E traefik …` (ports 80/443 nécessitent des privilèges). Pensez à conserver la session sudo active (`sudo -v`) pour éviter le prompt.
- Affiche les URLs utiles : dashboard (`http://localhost:8080`), frontend/API publiés.

`stop-traefik.sh` recense les processus `traefik` et les termine (SIGTERM puis SIGKILL si nécessaire).

## 4. Comprendre la configuration dynamique
`infra/traefik/dynamic/security.yml` définit :
- **Middlewares de sécurité** : en-têtes HSTS, CSP restrictive (`connect-src` extensible via `TRAEFIK_CSP_CONNECT_SRC`), Permissions-Policy, `frame-ancestors 'none'`.
- **Rate limiting** :
  - `guildforge-oauth-rate-limit` : 10 req/min pour `/connect/discord` et `/api/oauth/blizzard`.
  - `guildforge-api-rate-limit` : 120 req/min sur `/api`.
- **Routeurs** :
  - `frontend` : `Host($TRAEFIK_FRONT_HOST)` → service `frontend`.
  - `api-general` : `Host($TRAEFIK_API_HOST)` + `PathPrefix(/api)` → service `backend-api`.
  - `oauth-discord` & `oauth-blizzard` pour cibler les middlewares adaptés.
- **Services** :
  - `frontend` → `TRAEFIK_FRONTEND_URL`
  - `backend-api` → `TRAEFIK_BACKEND_API_URL`

La section `tls.certificates` charge les fichiers indiqués dans `.env.local`. Vous pouvez remplacer ces certificats par une configuration ACME (Let’s Encrypt) si vous ciblez un environnement exposé.

## 5. Brancher vos services
1. Démarrez le backend (`symfony server:start` ou `make serve`) et le frontend (`npm run dev`), idéalement en HTTPS.
2. Ajoutez dans `/etc/hosts` :
   ```
   127.0.0.1 app.localhost
   127.0.0.1 api.localhost
   ```
3. Lancez Traefik puis rendez-vous sur :
   - `https://app.localhost` → frontend via Traefik.
   - `https://api.localhost/api/...` → API Symfony proxifiée (vérifiez les en-têtes de sécurité dans l’inspecteur réseau).
4. Dashboard : `http://localhost:8080` (mode `--api.insecure=true` par défaut). Filtrez les routers/services pour diagnostiquer.

## 6. Personnalisation & bonnes pratiques
- **Environnements multiples** : créez plusieurs fichiers `.env.local.<nom>` et exportez `$(cat ...)` avant de démarrer Traefik si vous jonglez entre plusieurs DNS.
- **Sécurité** : ajustez/ou désactivez `--api.insecure` si vous exposez Traefik ailleurs que sur votre machine de développement.
- **Monitoring** : Traefik fournit des métriques Prometheus (non activées ici). Ajoutez `--metrics.prometheus=true` si besoin.
- **Scripts supplémentaires** : pour intégrer Traefik dans Docker Compose, réutilisez la configuration `dynamic/` et faites pointer `providers.file.directory` vers un volume partagé.

## 7. Troubleshooting rapide
- **Certificat introuvable** : le script stoppe immédiatement si `TRAEFIK_TLS_CERT_FILE`/`KEY_FILE` manquent. Vérifiez les chemins absolus.
- **Boucles de redirection HTTPS** : assurez-vous que vos serveurs upstream acceptent HTTPS (Symfony `symfony server:start` expose déjà TLS). Sinon, utilisez des URLs HTTP dans `TRAEFIK_*_URL` et laissez Traefik s’occuper de TLS côté client.
- **Rate limit inattendu** : adaptez les valeurs dans `security.yml` si vos tests automatisés dépassent les quotas.
- **Ports occupés** : arrêtez Apache/nginx locaux ou éditez les entrypoints (`--entrypoints.web.address=:8081`) si vous avez déjà un reverse proxy sur 80/443.
