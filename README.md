# Expenses Manager — backend

Laravel 13 REST API for a personal expense tracker. It is the `backend/` repository of the
Expenses Manager superproject and implements the contract in `contracts/openapi.yaml`
(OpenAPI 3.1, served under `/api/v1`).

Status: phase 3. Available today: Sanctum token authentication, owner scoping, i18n (`it`, `en`),
RFC 9457 error responses, health probes, JSON logs with request ids, categories (shared read-only
system categories plus per-owner custom ones, archive instead of delete) and expenses (CRUD and a
filtered, paginated listing). Stats follow in a later phase.

System categories are shared rows with a NULL `owner_id`; models marked `IncludesUnownedRows` see
`owner_id = current OR owner_id IS NULL`, implemented once in `OwnerScope`.

## Requirements

- PHP 8.4 or newer (developed on 8.5), Composer 2
- SQLite (default). Other engines work through `.env`; the code uses no dialect-specific SQL.
- PCOV for coverage runs

## Getting started

```bash
make setup           # composer install, .env, app key, migrate
php artisan serve    # http://localhost:8000
```

Or run the whole stack with Docker from the superproject (`make up`).

## Commands

| Command | Purpose |
| --- | --- |
| `make test` | Run the Pest suite |
| `make coverage` | Run with PCOV; fails below 90% |
| `make lint` | Pint, check only |
| `make typecheck` | Larastan at level `max` |
| `make format` | Apply Pint fixes |

## API overview

| Method | Path | Notes |
| --- | --- | --- |
| POST | `/api/v1/auth/register` | 201, returns `{data: {token, user}}`; throttled per IP |
| POST | `/api/v1/auth/login` | 200, or 401 problem on bad credentials; throttled per email and IP |
| POST | `/api/v1/auth/logout` | 204, revokes the current token |
| GET | `/api/v1/auth/me` | Signed-in user |
| PATCH | `/api/v1/auth/me` | Update `name` and/or `locale` |
| GET | `/health` | Liveness, `{status: "ok"}` |
| GET | `/ready` | Readiness, 503 problem when the database is unavailable |

Authenticate with `Authorization: Bearer <token>`. Send `Accept-Language: it|en` to localize messages.
Every error is `application/problem+json` (RFC 9457) with a localized `title` and `detail`, and
`errors` keyed by field on 422. Rate-limited responses carry `Retry-After`.

## Configuration

Copy `.env.example`; notable settings:

| Variable | Default | Meaning |
| --- | --- | --- |
| `DB_CONNECTION` | `sqlite` | Database engine |
| `SANCTUM_EXPIRATION` | `43200` | Token lifetime in minutes (empty = never) |
| `THROTTLE_LOGIN_PER_MINUTE` | `5` | Login attempts per email and IP |
| `THROTTLE_REGISTER_PER_MINUTE` | `10` | Registrations per IP |
| `LOG_CHANNEL` | `json` | JSON lines on stderr, each with `request_id` |

Supported languages are listed in `config/locales.php`; add a folder under `lang/` and one entry there.

## Seeding

`php artisan db:seed` always runs the reference seeders (idempotent, safe in production) and runs the
demo seeder only when `APP_ENV=local` (user `demo@example.com`).

## Docker

`docker/Dockerfile` has three targets: `dev` (PHP-FPM + Composer), `test` (adds PCOV and runs the
coverage gate) and `prod` (non-root, production OPcache, no dev dependencies). Laravel caches are
built at container start by `docker/php/entrypoint.sh`; set `RUN_MIGRATIONS=true` to migrate on start.
Nginx configs for dev and production are in `docker/nginx/`.

```bash
docker build -f docker/Dockerfile --target prod -t expenses-backend:prod .
```

## Git hooks

```bash
git config core.hooksPath .githooks
```

`commit-msg` enforces Conventional Commits; `pre-commit` runs `make lint` and `make typecheck` when PHP files are staged.

## Architecture

See [AGENTS.md](AGENTS.md) for the layer map and rules.
