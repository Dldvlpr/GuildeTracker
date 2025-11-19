# Edge hardening (Traefik / Cloudflare)

## Traefik

The `infra/traefik/dynamic/security.yml` file ships hardened middlewares:

- `guildforge-secure-headers` &mdash; forces TLS with HSTS (preload + subdomains), blocks MIME sniffing, locking CSP/Permissions-Policy/Referrer headers.
- `guildforge-oauth-rate-limit` &mdash; aggressive throttling on `/connect/discord` and `/api/oauth/blizzard/**`.
- `guildforge-api-rate-limit` &mdash; softer throttling for general `/api/**` traffic.

Attach the middlewares to your routers or reference the routers that are pre-defined in the file. The sample routers assume:

```
entryPoints: websecure
Host(`api.mondomaine.fr`)
service: backend-api (http://backend:8080)
```

Adjust host/service names to match your stack, then mount the file in Traefik:

```yaml
services:
  traefik:
    volumes:
      - ./infra/traefik/dynamic:/etc/traefik/dynamic
    command:
      - --providers.file.directory=/etc/traefik/dynamic
      - --entrypoints.websecure.http.tls=true
```

For Nginx, mirror the same headers/rate limiting via `limit_req_zone`, `add_header Strict-Transport-Security ... always;`, etc.

## Cloudflare WAF / CDN

Set the application behind Cloudflare and create Firewall Rules (example):

1. **OAuth abuse protection**
   - Expression: `(http.request.uri.path contains "/connect/discord" and cf.threat_score > 10) or (http.request.uri.path contains "/api/oauth/blizzard" and cf.client.bot)`
   - Action: Block
2. **Generic API flood**
   - Expression: `(http.host eq "api.mondomaine.fr" and http.request.uri.path starts_with "/api/" and cf.threat_score > 20)`
   - Action: Block or Challenge
3. **Country restrictions (optional)**
   - Expression: `(http.host eq "api.mondomaine.fr" and http.request.uri.path contains "/connect/discord" and ip.geoip.country in {"CN" "RU"})`
   - Action: Managed Challenge

Additionally:

- Enable “Automatic HTTPS Rewrites”, “Always Use HTTPS”, and HSTS (min 2 years, include subdomains, preload).
- Set “Bot Fight Mode” to `enabled` for the API zone.
- Pin a CSP report endpoint (`contentSecurityPolicyReportOnly`) to watch for CSP violations before enforcing.
