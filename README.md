# Post SMTP 4.x Greenfield

Greenfield revamp structure for Post SMTP with shared `kernel/`, modern `src/`, React `admin-app/`, and frozen legacy `Postman/`.

## Structure

- `kernel/` — Shared libraries, data layer, providers, migration engine
- `src/` — Bootstrap, REST v2, admin loaders, mail pipeline
- `admin-app/` — React SPA (HashRouter)
- `Postman/` — Legacy zone (frozen, copied from 3.9.5)
- `post-smtp-pro-4/` — Pro companion plugin (separate folder)

## Setup

```bash
cd post-smtp-4
composer install
cd admin-app && npm install && npm run build
```

## Customer routing

| Cohort | Storage | Admin UI |
|--------|---------|----------|
| legacy | postman_options | Postman wizard |
| new_install | postman_connections | React SPA |
| migrated | postman_connections | React SPA |

Migration: PHP notice links to `#/migration` in React SPA.
