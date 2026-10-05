# Development Setup

Source review: 5 October 2026. Related: [[03 Programmier-Stack|Programmier-Stack]], [[04 Application Architecture|Application Architecture]], [[07 Implementation Status|Implementation Status]].

## Prerequisites

Use PHP compatible with Composer's `^8.3` constraint, Composer, Node.js/npm and an SQLite-capable PHP installation for the default local database. The GitHub Actions workflow uses PHP 8.4, Composer v2 and Node 22. Install exact dependencies from the lockfiles.

Run commands from the project root, currently `C:\Users\yanni\Documents\FeedbackForm`.

## Fresh local installation

For a new checkout without an existing environment/database:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
New-Item -ItemType File -Path database/database.sqlite
php artisan migrate
npm ci
npm run build
```

Skip environment copying, key generation and database-file creation when these already exist. `.env.example` selects SQLite and database-backed sessions, cache and queues. Keep `.env` local. The bundled `composer setup` script also installs dependencies, creates a missing environment file, generates a key, migrates and builds assets; it uses `npm install` and regenerates the key on each invocation.

## Demo data

On a dedicated local demo database:

```powershell
php artisan db:seed
```

`DatabaseSeeder` runs `RoleSeeder` and `QuestionnaireSeeder`, then `DemoSeeder` only in `local` or `testing` environments. The demo seeder creates an approved `admin@example.com` account with password `password`, five approved instructors, three additional unapproved users, five courses, fifteen sessions and three feedback forms per session with random six-digit codes.

The demo credentials are for local demonstration. Re-running the demo seeder resets the demo administrator's password and adds more factory-generated records; it is not an idempotent reset. The standard questionnaire seeder updates shared question content and rebuilds its template pivot. Seeding is therefore a data mutation, not a routine application startup step.

Log in as the demo administrator and open Overview to find existing feedback codes. Participants enter a code on the home page without logging in. New sessions created through the UI have no generated feedback codes yet.

## Start development

```powershell
composer dev
```

This starts the Laravel server, queue listener and frontend development server through Concurrently. The example application URL is `http://localhost:8000`.

## Verification

| Command | Scope |
|---|---|
| `php artisan test` | PHP feature tests; configured in-memory SQLite database |
| `npm run test:js` | JavaScript tests under `tests/js` through Vite Plus |
| `npm run build` | Frontend production build |
| `composer lint:check` | Pint formatting check |
| `composer types:check` | PHPStan/Larastan analysis |
| `composer test` | Clear configuration, formatting, static analysis and PHP tests |
| `composer ci:check` | Delegates to `composer test` |

The GitHub Actions workflow runs `composer setup` and `composer ci:check` on pushes to `main` and pull requests. Setup builds frontend assets, but the workflow currently does not explicitly run `npm run test:js`.

No test or build was run as part of this source-based documentation update. This note is a local development guide; the repository does not yet provide application backup, CSV import or a production deployment workflow.
