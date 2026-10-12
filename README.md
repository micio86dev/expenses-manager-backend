# Expenses Manager — backend

Laravel 13 REST API for a personal expense tracker. It is the `backend/` repository of the
Expenses Manager superproject and implements the contract in `contracts/openapi.yaml`
(OpenAPI 3.1, served under `/api/v1`).

Features: Sanctum token authentication, owner scoping, i18n (`it`, `en`), RFC 9457 error responses,
health probes, JSON logs with request ids, categories (shared read-only system categories plus
per-owner custom ones, archive instead of delete), expenses (CRUD and a filtered, paginated
listing) and cached statistics.

For the whole project (frontend, Docker stack, release flow) see the [root README](../README.md).

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
| `make setup` | `composer install`, create `.env`, generate the key, migrate |
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
| GET | `/api/v1/categories` | System and own categories; `include_archived=true` adds archived ones |
| POST | `/api/v1/categories` | Create a custom category |
| GET / PUT / DELETE | `/api/v1/categories/{category}` | Show, update, delete (own categories only) |
| POST | `/api/v1/categories/{category}/archive`, `/unarchive` | Archive instead of delete |
| GET | `/api/v1/expenses` | Paginated list, see below |
| POST | `/api/v1/expenses` | Create an expense |
| GET / PUT / DELETE | `/api/v1/expenses/{expense}` | Show, update, delete |
| GET | `/api/v1/stats/summary` | Total, count, daily average, change vs the previous period, top category, biggest expense |
| GET | `/api/v1/stats/by-category` | Totals and share per category, largest first |
| GET | `/api/v1/stats/timeseries` | Continuous day, ISO week (Monday) or month buckets (`granularity`) |
| GET | `/api/v1/stats/top-expenses` | Biggest expenses in the range (`limit`, default 5, max 10) |
| GET | `/health` | Liveness, `{status: "ok"}` |
| GET | `/ready` | Readiness, 503 problem when the database is unavailable |

Authenticate with `Authorization: Bearer <token>`. Send `Accept-Language: it|en` to localize messages.
Every error is `application/problem+json` (RFC 9457) with a localized `title` and `detail`, and
`errors` keyed by field on 422. Rate-limited responses carry `Retry-After`.

### Listing expenses

`GET /api/v1/expenses` requires `from` and `to` (inclusive `Y-m-d`, `to` not before `from`) and accepts
`category_id` (UUID), `q` (text search, 1 to 100 characters), `page` (default 1) and `per_page`
(default 50, max 100). Categories are returned in full, without pagination.

### Statistics

All four take `from` and `to` (inclusive, `Y-m-d`, at most 5 years apart, otherwise 422) and an optional
`currency` (default `EUR`); currencies are never mixed. The daily average is the total divided by the
number of days in the range, rounded half up on integers. The previous period is the range of equal
length that ends the day before `from`; its change is `null` when it has no spending. Percentages have
two decimals. Week and month buckets are summed in PHP from per-day SQL aggregates (portable on every
database); a week or month that starts before `from` is still labelled by its first day but only holds
days inside the range.

Responses are cached per owner for one hour under a version key (`stats:version:{owner}`) that every
expense or category write replaces, so invalidation works on any cache driver. Each response carries a
strong `ETag` (quoted SHA-256 of the body); a matching `If-None-Match` gets an empty 304. Statistics
have their own rate limit (`THROTTLE_STATS_PER_MINUTE`).

## Configuration

Copy `.env.example` (`make setup` does it for local development, switching to `APP_ENV=local` and `APP_DEBUG=true`; the file itself holds production-safe values). Every variable below is read by `config/*.php` or by the framework defaults. Notable settings:

| Variable | Default | Meaning |
| --- | --- | --- |
| `DB_CONNECTION` | `sqlite` | Database engine |
| `TRUSTED_PROXIES` | empty | Comma-separated IPs or CIDR ranges of the reverse proxies allowed to set `X-Forwarded-*`; empty trusts none. Set it behind a load balancer so IP-based rate limits see the real client |
| `SANCTUM_EXPIRATION` | `43200` | Token lifetime in minutes (empty = 30 days); expired tokens are pruned daily by the scheduler |
| `THROTTLE_LOGIN_PER_MINUTE` | `5` | Login attempts per email and IP |
| `THROTTLE_LOGIN_IP_PER_MINUTE` | `30` | Login attempts per IP across all emails |
| `THROTTLE_REGISTER_PER_MINUTE` | `10` | Registrations per IP |
| `THROTTLE_STATS_PER_MINUTE` | `120` | Statistics requests per user |
| `THROTTLE_API_PER_MINUTE` | `120` | Authenticated API requests per user |
| `LOG_CHANNEL` | `json` | JSON lines on `LOG_JSON_STREAM` (default stderr), each with `request_id` |
| `CACHE_STORE` | `database` | Store used by the statistics cache; any driver works |
| `APP_LOCALE` / `APP_FALLBACK_LOCALE` | `it` / `en` | Default and fallback language |

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

## Continuous integration

`.github/workflows/backend.yml` runs Pint, PHPStan, Pest with coverage (minimum 90%, PHP 8.5 with PCOV)
and `composer audit`, through the same `make` targets used locally. It is dormant: it only runs by
hand (`workflow_dispatch`) until a remote exists, then enable the commented `push`/`pull_request`
triggers. `.github/dependabot.yml` proposes weekly Composer and GitHub Actions updates.

## Git hooks

```bash
git config core.hooksPath .githooks
```

`commit-msg` enforces Conventional Commits; `pre-commit` runs `make lint` and `make typecheck` when PHP files are staged.

## Architecture

See [AGENTS.md](AGENTS.md) for the layer map and rules.
