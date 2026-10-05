# Programming Stack

This document describes the current project as of 5 October 2026. Version entries below are dependency constraints, not a measurement of the installed runtime.

| Technology | Purpose | Declared version / configuration |
|---|---|---|
| PHP | Backend language | Composer requires `^8.3` |
| Laravel | Web framework | `^13.17` |
| Eloquent | Relational models and database access | Included with Laravel |
| Blade | Server-rendered HTML | Included with Laravel |
| Bootstrap | Styling and interactive components | `^5.3.8` |
| JavaScript | Questionnaire interactions and overview filters | Browser modules |
| Vite | Frontend asset tooling | `^8.0.0` |
| Vite Plus | Development, build and JavaScript test commands | `0.3.0` |
| SQLite | Example local database and PHP test database | `.env.example`; in-memory tests |
| PHPUnit | PHP feature testing | `^12.5.36` |
| Laravel Pint | PHP formatting | `^1.27` |
| Larastan / PHPStan | Static analysis | Larastan `^3.9` |
| Composer | PHP dependencies and project scripts | `composer.json`, `composer.lock` |
| npm | Frontend dependencies and scripts | `package.json`, `package-lock.json` |
| Git | Version control | Repository metadata |

The committed lockfiles record resolved package versions. Actual local runtime and database settings can differ from dependency constraints and `.env.example`. No private `.env` was inspected for this review.

Livewire, Tailwind CSS and Pest are not declared dependencies in the current project. The selected stack in [[02 Project Requirements|Project Requirements]] now reflects the repository: PHP `^8.3`, Blade, Bootstrap, JavaScript, Vite/Vite Plus, SQLite and PHPUnit. CI uses PHP 8.4 and Node 22; these are workflow settings rather than verified local runtime versions.

Related notes: [[01 Documentation Index|Documentation Index]], [[04 Application Architecture|Application Architecture]], [[06 Development Setup|Development Setup]] and [[07 Implementation Status|Implementation Status]].

## Backend

Routes in `routes/web.php` map requests to controllers or Blade views. Controllers handle registration, login/logout, user administration, session creation, the course/session overview, questionnaire editor/template saving and participant submission. Eloquent models represent domain records and migrations define tables and constraints.

Authentication uses Laravel's authentication/session facilities. Login requires `is_approved = true` and is throttled. Administrator checks are explicit in `UserController`. Authentication and role authorization are separate: session creation currently requires authentication without an editor/admin check, and the overview loads all sessions for authenticated users with a role.

## Frontend

Blade templates live in `resources/views`. `resources/js/app.js` imports Bootstrap modal, collapse and dropdown components, refreshes questionnaire-editor answer types with scroll/focus restoration and filters overview cards by course and instructor. Filtering occurs in the browser after sessions have been loaded; it does not limit the records returned by the server.

## Database and questionnaire content

Database configuration is defined in `config/database.php` and environment settings. `.env.example` defaults to SQLite. PHPUnit uses an in-memory SQLite database. The standard questionnaire's text and answer options are currently defined in `database/seeders/QuestionnaireSeeder.php`, rather than a separate questionnaire configuration file.

## Development and verification commands

| Command | Purpose |
|---|---|
| `composer install` | Install locked PHP dependencies |
| `npm ci` | Install locked frontend dependencies |
| `composer dev` | Run local server, queue listener and frontend development server |
| `npm run build` | Compile frontend assets |
| `php artisan test` | Run PHP application tests |
| `npm run test:js` | Run JavaScript tests |
| `composer lint:check` | Check PHP formatting |
| `composer types:check` | Run PHPStan analysis |
| `composer test` | Clear configuration, check formatting, run static analysis and PHP tests |

This document records available commands; it does not claim they were executed or passed during the documentation review.

The GitHub Actions workflow runs setup/build and `composer ci:check`. JavaScript tests have a separate npm command and are not explicitly included in that workflow.
