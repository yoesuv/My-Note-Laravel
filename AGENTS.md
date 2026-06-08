# AGENTS.md

## Project snapshot
- Laravel 13 app (`laravel/framework` `^13.8`) requiring PHP `^8.3`.
- API service auth is installed with Laravel Sanctum (`laravel/sanctum`), using token-based Bearer authentication.
- Current API entrypoint is `routes/api.php`.
- Current web entrypoint is `routes/web.php` with only `/ -> resources/views/welcome.blade.php`.
- Frontend toolchain is Vite 8 + Tailwind 4 via `laravel-vite-plugin`; Vite inputs are `resources/css/app.css` and `resources/js/app.js`.

## Commands
- Full first-time setup: `composer run setup` (runs Composer install, creates `.env`, generates key, migrates, then `npm install --ignore-scripts` and `npm run build`).
- Local dev stack: `composer run dev` starts Laravel server, queue listener, Pail logs, and Vite together via `concurrently`.
- Backend tests: `composer run test` (clears config, then `php artisan test`).
- Focused test: `php artisan test --filter=TestName`.
- Frontend only: `npm run dev`; production assets: `npm run build`.
- PHP formatting: `./vendor/bin/pint` (Pint is installed; no repo-specific Pint config yet).

## Database and tests
- `.env.example` defaults to SQLite, but this project is intended to use MySQL for local app development; keep DB credentials only in `.env`.
- `phpunit.xml` forces tests to SQLite in-memory (`DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`), so tests do not use the local MySQL database unless config is changed.
- Queue, session, and cache default to database-backed drivers in `.env.example`; migrations include users, cache, jobs, Sanctum `personal_access_tokens`, and categories tables.
- `DatabaseSeeder` calls `UserSeeder` and `CategorySeeder`; seeded test user is `test@example.com` / `password` with categories `Personal`, `Work`, and `Ideas`.

## API auth conventions
- Auth endpoints live in `routes/api.php`.
- API auth uses Sanctum tokens with `auth:sanctum`.
- Auth flow follows `Controller -> Service -> Repository`.
- Public endpoints:
  - `POST /api/register`
  - `POST /api/login`
- Protected endpoints:
  - `GET /api/me`
  - `POST /api/logout`
- Login/register are rate limited using named Laravel rate limiters in `App\Providers\AppServiceProvider`.
- API exposes `full_name`, but stores it in the `users.name` database column.
- Login intentionally returns a generic invalid credentials message for unknown email and wrong password.

## API categories conventions
- Category endpoints live in `routes/api.php` and are protected by `auth:sanctum`.
- Category flow follows `Controller -> Service -> Repository`.
- Protected endpoints:
  - `GET /api/categories`
  - `POST /api/categories`
  - `GET /api/categories/{category}`
  - `PUT/PATCH /api/categories/{category}`
  - `DELETE /api/categories/{category}`
- Categories belong to users; all category lookups must be scoped to the authenticated user.
- Category names are trimmed before validation/storage.
- Category names are unique per user case-insensitively via `categories.name_normalized` and a unique index on `user_id + name_normalized`.
- Category color is stored as lowercase 6-digit hex varchar, e.g. `#f39c12`.
- Malformed category route IDs should return 404; `routes/api.php` constrains `{category}` to numeric IDs.

## Documentation
- API login documentation: `docs/login.md`
- API register documentation: `docs/register.md`
- API categories documentation: `docs/categories.md`

## Conventions/gotchas
- `.npmrc` has `ignore-scripts=true`; use the Composer `setup` script or `npm install --ignore-scripts` to match repo behavior.
- Laravel 13 skeleton uses PHP attributes on `App\Models\User` (`#[Fillable]`, `#[Hidden]`) instead of the older `$fillable` / `$hidden` properties.
- If adding Laravel features, check current Laravel 13 docs first and follow the existing skeleton style unless the app architecture is explicitly changed.
