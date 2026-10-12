# AGENTS.md — backend

Laravel 13 REST API of Expenses Manager. Follows the stack, layers and rules of the
superproject `AGENTS.md`; this file adds what is specific to this repository.
The HTTP contract is `contracts/openapi.yaml` (OpenAPI 3.1), copied here by `make contract`
in the superproject. Never edit the copy: change the source of truth and re-copy.

## Language rules

Everything written to disk is English, except the Italian translation files in `lang/it/`.
Commit messages follow Conventional Commits, without AI attribution lines.

## Commands

| Command | Purpose |
| --- | --- |
| `make setup` | `composer install`, create `.env`, generate the key, migrate |
| `make test` | Pest suite |
| `make coverage` | Pest with PCOV, fails below 90% |
| `make lint` | Pint, check only |
| `make typecheck` | Larastan at level `max` |
| `make format` | Pint, writes fixes |

CI (`.github/workflows/backend.yml`, manual trigger until a remote exists) runs the same targets
(`make lint`, `make typecheck`, `make coverage`) plus `composer audit`.

Enable the repository hooks once with `git config core.hooksPath .githooks`
(`commit-msg` checks Conventional Commits; `pre-commit` runs lint and typecheck when PHP files are staged).

## Architecture map

Request flow: `routes` → middleware → **Controller** → **FormRequest** (input) → **Action** → **Repository contract** → **Eloquent repository** → **Model**; output through **API Resources**.

| Path | Role |
| --- | --- |
| `app/Http/Controllers` | Single-action, thin. Depend only on Actions, FormRequests and Resources. |
| `app/Http/Requests` | Validation and mapping to DTOs. |
| `app/Http/Resources` | Output shape, matches the contract. |
| `app/Http/Middleware` | `AssignRequestId` (X-Request-Id and log context), `SetLocale`, `CacheStatsResponse` and `AddStatsEtag` (statistics routes). |
| `app/Actions` | One use case per class. Know nothing about HTTP or Eloquent. |
| `app/Data` | `final readonly` DTOs. |
| `app/Repositories/Contracts` | Persistence interfaces used by Actions. |
| `app/Repositories/Eloquent` | The only place that builds queries (plus models and `Support/Owner`). |
| `app/Models` | Eloquent models. UUID keys. |
| `app/Observers` | `StatsInvalidationObserver`: the one place that bumps an owner's statistics version after expense or category writes. |
| `app/Support/Periods` | `Period` enum, `PeriodResolver` and one strategy per period (day, ISO week, month, custom). Only place that buckets dates. |
| `app/Support/Stats` | `StatsVersion` (per-owner cache version token) and `StatsMath` (rounding rules). |
| `app/Support/Owner` | `CurrentOwner` contract, `OwnedByCurrentOwner` trait, `OwnerScope`. |
| `app/Support/Problem` | RFC 9457 Problem Details renderer (wired in `bootstrap/app.php`). |
| `app/Support/Locale` | `LocaleRegistry` over `config/locales.php`. |
| `app/Support/Health` | Readiness probe behind a contract. |
| `lang/{it,en}` | Translations. Identical key sets are enforced by a test. |
| `database/seeders/Reference` | Idempotent, production-safe data. Always run. |
| `database/seeders/Demo` | Factory-built sample data, `local` only. |

Probes `GET /health` and `GET /ready` live outside `/api/v1` (`routes/health.php`), with no session and no auth.

## Rules

- `declare(strict_types=1)` in every file; classes are `final`; DTOs are `readonly`; enums for fixed sets.
- Eloquent and the query builder only. No raw SQL, no dialect functions, no JSON-column queries.
  The database engine is selected only through `.env` (SQLite by default).
  Named exception: `EloquentStatsRepository` passes the standard aggregates `SUM(amount_cents)` and
  `COUNT(*)` to `selectRaw`; the query builder has no non-raw way to select a grouped aggregate.
  Date bucketing stays in PHP (`PeriodResolver`).
- Money is integer minor units plus ISO currency. IDs are UUIDs.
- Every domain table has `owner_id`; models use `OwnedByCurrentOwner`. Moving from users to tenants
  is one change: the `CurrentOwner` binding in `AppServiceProvider`.
- Errors are `application/problem+json` with localized `title` and `detail`; internal exception
  messages never reach the client.
- User-facing strings live in `lang/`; add a locale by adding a folder and one entry in `config/locales.php`.
- Migrations are schema only. Data goes in seeders.
- Statistics are cached by `CacheStatsResponse` under the owner's `StatsVersion`; never invalidate by key
  or tag, write through models so `StatsInvalidationObserver` bumps the version (query-builder bulk writes must bump it explicitly).
- Rate limits are named limiters (`login` has two buckets: email+IP and IP alone; `register`, `api`, `stats`) tuned in `config/throttle.php`.
  Login keys use `EmailKey::normalise()`, the same normalisation as `LoginRequest`.

## Known limits (follow-ups)

Deliberately not fixed yet; each needs a product or infrastructure decision.

- Statistics cache growth: entries live in the `database` cache store under a versioned key and are only
  superseded, never deleted early. Expired rows are not pruned automatically. Follow-up: a scheduled prune
  or a cache store with native eviction (Redis).
- `q` filter on the expense list: `%` and `_` typed by the user act as `LIKE` wildcards. Follow-up: escape
  them in the repository once the desired search semantics are decided.
- Category names are unique per owner by validation only. Two concurrent requests can both pass the check
  and create duplicates. Follow-up: a unique index on `(owner_id, name)` plus a conflict response.
- Registration reveals whether an email is taken (422). Inherent to open sign-up with email as identity;
  mitigated by the per-IP `register` throttle.
- `TRUSTED_PROXIES` must be set behind a load balancer; empty trusts no proxy, so IP-based limits then see
  the proxy address. Expired Sanctum tokens are pruned by the scheduler (`schedule:run` must be cron-ed).

## Testing

Pest, test first. Layers: `tests/Unit` (value objects, DTOs, actions with in-memory fakes),
`tests/Feature` (endpoints, auth, ownership, i18n, health, logging, seeders),
`tests/Arch` (strict types, final classes, layering, no query building outside repositories).

Every response of the `api` route group is validated against the contract by `hotmeteor/spectator`
(`assertValidResponse`). Routes outside that group (health probes) use the `assertMatchesContract()`
helper from `tests/Pest.php`. `tests/Feature/ContractTest.php` proves the validator rejects
violations on the OpenAPI 3.1 document. Test-only models live in `tests/Fixtures`.

## Definition of done

Tests pass · coverage ≥ 90% · `make lint` and `make typecheck` clean · contract copy in sync ·
docs updated · no dead or duplicated code · clean tree before merge.
