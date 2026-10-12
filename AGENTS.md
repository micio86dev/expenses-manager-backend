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

Enable the repository hooks once with `git config core.hooksPath .githooks`
(`commit-msg` checks Conventional Commits; `pre-commit` runs lint and typecheck when PHP files are staged).

## Architecture map

Request flow: `routes` → middleware → **Controller** → **FormRequest** (input) → **Action** → **Repository contract** → **Eloquent repository** → **Model**; output through **API Resources**.

| Path | Role |
| --- | --- |
| `app/Http/Controllers` | Single-action, thin. Depend only on Actions, FormRequests and Resources. |
| `app/Http/Requests` | Validation and mapping to DTOs. |
| `app/Http/Resources` | Output shape, matches the contract. |
| `app/Http/Middleware` | `AssignRequestId` (X-Request-Id and log context), `SetLocale`. |
| `app/Actions` | One use case per class. Know nothing about HTTP or Eloquent. |
| `app/Data` | `final readonly` DTOs. |
| `app/Repositories/Contracts` | Persistence interfaces used by Actions. |
| `app/Repositories/Eloquent` | The only place that builds queries (plus models and `Support/Owner`). |
| `app/Models` | Eloquent models. UUID keys. |
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
- Money is integer minor units plus ISO currency. IDs are UUIDs.
- Every domain table has `owner_id`; models use `OwnedByCurrentOwner`. Moving from users to tenants
  is one change: the `CurrentOwner` binding in `AppServiceProvider`.
- Errors are `application/problem+json` with localized `title` and `detail`; internal exception
  messages never reach the client.
- User-facing strings live in `lang/`; add a locale by adding a folder and one entry in `config/locales.php`.
- Migrations are schema only. Data goes in seeders.
- Rate limits are named limiters (`login`, `register`, `api`) tuned in `config/throttle.php`.

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
