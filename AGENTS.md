# AGENTS.md

## Project snapshot
- Fresh Laravel 13 app (`laravel/framework` `^13.8`) requiring PHP `^8.3`; not an Inertia/Livewire/API starter yet.
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
- Queue, session, and cache default to database-backed drivers in `.env.example`; migrations include users, cache, and jobs tables.

## Conventions/gotchas
- `.npmrc` has `ignore-scripts=true`; use the Composer `setup` script or `npm install --ignore-scripts` to match repo behavior.
- Laravel 13 skeleton uses PHP attributes on `App\Models\User` (`#[Fillable]`, `#[Hidden]`) instead of the older `$fillable` / `$hidden` properties.
- If adding Laravel features, check current Laravel 13 docs first and follow the existing skeleton style unless the app architecture is explicitly changed.
