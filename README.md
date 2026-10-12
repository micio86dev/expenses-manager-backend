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
| `make review` | Guardian Angel review of the files changed in the branch (`gga run --pr-mode`, needs `gga`) |
| `make memory-sync` | Run `engram sync` from the superproject root (project-wide memory) |

`make help` lists them. Review and memory are described in the [root README](../README.md).

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

### Add a listable resource

Follow the expense listing (`GET /api/v1/expenses`) as the reference. Work test first, one checklist item at a time:

1. Model with `OwnedByCurrentOwner` (or `IncludesUnownedRows` for shared rows), UUID key, a `matching(Filter)` scope that is the single definition of "which rows", and a migration with `owner_id` (schema only).
2. Repository contract in `app/Repositories/Contracts` and its Eloquent implementation in `app/Repositories/Eloquent`; bind it in `AppServiceProvider`.
3. Filter DTO in `app/Data` (`final readonly`, validates its own bounds).
4. Action in `app/Actions/<Resource>` that takes the filter and returns a page DTO.
5. FormRequest that validates the query string and maps it to the filter (`toFilter()`).
6. API Resource and collection in `app/Http/Resources`; relations through `whenLoaded`.
7. Single-action controller and the route in `routes/api.php` under the `auth:sanctum` and `throttle:api` group.
8. Contract entry: edit `contracts/openapi.yaml` in the superproject, then run `make contract` there to copy it here. Never edit the copy.
9. Translation keys for new validation messages in both `lang/it` and `lang/en`.
10. Tests: feature tests (filters, pagination, owner isolation, `assertValidResponse` against the contract) and arch coverage via the existing arch tests. Then `make lint`, `make typecheck`, `make coverage`.

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
| `CORS_ALLOWED_ORIGINS` | `http://localhost:5173` | Comma-separated origins allowed by CORS (`config/cors.php`) |
| `DB_BUSY_TIMEOUT` / `DB_JOURNAL_MODE` | `5000` / `WAL` | SQLite lock wait in milliseconds and journal mode (`config/database.php`) |

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
triggers. It also holds a `gga` job (`gga run --ci`) that needs the `claude` CLI and its credentials on the runner. `.github/dependabot.yml` proposes weekly Composer and GitHub Actions updates.

## Git hooks

```bash
git config core.hooksPath .githooks
```

`commit-msg` enforces Conventional Commits; `pre-commit` runs `make lint` and `make typecheck` when PHP files are staged, then Guardian Angel (`gga run`, configured in `.gga`) as the last step. It is skipped with a message when `gga` is not installed. Do not run `gga install`: it writes to `.git/hooks`, which `core.hooksPath` ignores.

## Architecture

See [AGENTS.md](AGENTS.md) for the layer map, the rules and the review rules checked by Guardian Angel.
