# Development Setup

Source review: 5 October 2026. Related: [[03 Programmier-Stack|Programmier-Stack]], [[04 Application Architecture|Application Architecture]], [[07 Implementation Status|Implementation Status]].

## Prerequisites

Use PHP compatible with Composer's `^8.3` constraint, Composer, Node.js/npm and an SQLite-capable PHP installation for the default local database. The GitHub Actions workflow uses PHP 8.4, Composer v2 and Node 22. Install exact dependencies from the lockfiles.

Run commands from the project root, currently `C:\Users\yanni\Documents\Evalio`.

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

The session-template column is defined in the original domain-table creation migration, with no follow-up migration or backfill. Use `php artisan migrate:fresh --seed` when rebuilding your disposable local database; this deletes existing data. New sessions copy the course default at creation; feedback uses the session's saved template. This does not freeze template questions/options.

On a dedicated local demo database:

```powershell
php artisan db:seed
```

`DatabaseSeeder` runs `RoleSeeder` and `QuestionnaireSeeder`, then `DemoSeeder` only in `local` or `testing` environments. The demo seeder creates an approved `admin@example.com` account with password `password`, five approved instructors, three additional unapproved users, five short-name courses (`AID`, `EPR`, `EXT`, `WEB`, `SQL`), fifteen sessions and one feedback form per session. Only open sessions receive random six-digit codes; closed/future sessions have null codes. Unused role permission columns were removed from the original role-table migration, so fresh migration uses role names and gates only.

The demo credentials are for local demonstration. Re-running the demo seeder resets the demo administrator's password and adds more factory-generated records; it is not an idempotent reset. The standard questionnaire seeder updates shared question content and rebuilds its template pivot. Seeding is therefore a data mutation, not a routine application startup step.

Log in as the demo administrator and open Overview to find existing feedback codes. Participants enter a code on the home page without logging in. New sessions receive an access code when their evaluation is opened manually or by the scheduled start-date job. Closed evaluations reject access even if a legacy/demo record still contains a code.

## Start development

```powershell
composer dev
```

This starts the Laravel server, queue listener, Laravel scheduler worker and frontend development server through Concurrently. The example application URL is `http://localhost:8000`.


## Evaluation scheduler

`composer dev` includes `php artisan schedule:work`; if you start the web and frontend servers separately, run that worker in another terminal. In production, configure cron or Windows Task Scheduler to run `php artisan schedule:run` every minute from the project root. Each production invocation finishes after checking the schedule; PHP does not need a permanently running web request.

For example, a Linux cron entry is:

```cron
* * * * * cd /absolute/path/to/Evalio && /absolute/path/to/php artisan schedule:run >> /dev/null 2>&1
```

On Windows, use the host's PHP executable, pass `artisan schedule:run` as arguments, set the project folder as the working directory, and repeat the task every minute.

The evaluation command runs on the hour and uses `EVALUATION_TIMEZONE=Europe/Zurich` by default. It opens on session start dates and closes on end dates plus 14 days. Sessions with an unset (`null`) status also open later within that evaluation window. Manually closed sessions remain closed on later dates. The scheduler must run on the closing calendar day for automatic closure. Repeated runs skip evaluations already in the requested state.

To inspect the registered schedule or run today's boundary updates manually:

```powershell
php artisan schedule:list
php artisan evaluations:update-statuses
```

The second command updates records and access codes in your configured database; it is not a read-only check.

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

## Existing database compatibility

The unique feedback_forms.course_session_id constraint was added to the original domain creation migration; there is no follow-up migration in the current tree. An existing database that already ran that migration will not gain the constraint just by running php artisan migrate. Plan a forward migration/data reconciliation for existing data. Rebuilding a disposable local database is an alternative, but destroys that database's contents.


## Session-number upgrade and results output

Run `php artisan migrate` for the forward session-number migration; a fresh reset is unnecessary for this change. Back up first because displayed identifiers are renumbered per course. The demo seeder reuses its named courses but still adds instructors and sessions on repeated runs.

Open View evaluation results from session details. Print / Save as PDF uses browser printing and an A4 chart summary. Disable browser-added headers/footers when checking one-page output. Actual standard-questionnaire page-count verification remains required.

The compatible local Windows runtime is `C:/Users/yanni/.config/herd-lite/bin/php.exe`. Put its directory before XAMPP PHP on PATH for Composer. If local PHPStan exceeds 128 MB, run `php vendor/bin/phpstan analyse --memory-limit=512M`. JavaScript tests use `npm run test:js`; these are not currently a separate CI workflow step.

## CSV imports and local runtime status (8 October 2026)

See [[08 CSV Imports|CSV Imports]] for administrator upload formats and import order. Questionnaire-name uniqueness is defined in the original domain migration; use a fresh disposable local database when applying this schema change. `migrate:fresh --seed` deletes existing data.

The herd-lite executable at `C:/Users/yanni/.config/herd-lite/bin/php.exe` was verified available as PHP 8.4.0 during localization work on 8 October 2026. Restricted execution may deny access to that path; run it with the appropriate local filesystem access. XAMPP provides PHP 8.2.4, which cannot run installed Composer dependencies requiring PHP 8.4 or newer. Do not bypass Composer platform checks.

## Language

German is the installation default (`APP_LOCALE=de`). Use the **Deutsch / English** buttons in the header or authentication pages to change the interface language; a cookie remembers the choice. `APP_LOCALE=en` can change the default for visitors without a preference. After changing `.env`, run `php artisan config:clear`. Tests keep their existing English assertions using the explicit English setting in `phpunit.xml`; German-specific tests switch the application locale. Stored questionnaire content remains in its original language. See [[10 Localization|Localization]].
