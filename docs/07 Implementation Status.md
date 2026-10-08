# Implementation Status

Reviewed against source code on 8 October 2026. [[02 Project Requirements|Project Requirements]] remains the specification; this note records implementation progress without changing its scope or priorities.

## Requirements and current behavior

| Requirement area | Current implementation | Remaining work |
|---|---|---|
| Web application and SQL storage | Laravel, Blade, Eloquent and migrations | Production database/deployment configuration and complete SQL dump procedure |
| Roles and least privilege | Separate Gates, instructor ownership for results and questionnaire session usage, admin-only deletion and admin/editor creation/editing | Run new questionnaire visibility regression tests with compatible PHP |
| Registration and approval | New instructor accounts are unapproved; admin approves and assigns roles; login requires approval; guest password reset through the Laravel broker | Production reset-email delivery and automated reset verification |
| Course management | Validated create/edit pages, questionnaire defaults and admin-only transactional deletion with dependent feedback | Optional archiving |
| Course sessions | Create/edit, saved templates, numeric per-course numbers, computed identifiers and transactional admin deletion | Concurrent number allocation; optional archiving |
| Evaluation phase | Hourly start-date opening and closing on end date plus 14 days, authorized manual controls, code lifecycle and status checks on GET/POST feedback | Production scheduler setup and operational monitoring |
| Anonymous evaluation (T-01) | Public code-based questionnaire; no participant foreign key on forms | Full anonymity assessment beyond domain storage; code lifecycle and access controls |
| Cancel/back (T-02/T-03) | All questions appear on one page; no answer writes before POST | Explicit cancel interaction and any required navigation behavior |
| Optional questions (T-04) | Browser allows skipped answers; server accepts skipped/blank responses; feature tests cover this | Define consistent abstention counting and validate payloads; comment-only handling remains open |
| Comments (T-05) | Comment fields and answer comment storage | Server checks for allowed comments, types and lengths; behavior for comment-only responses |
| Review/edit (T-06/T-07) | Answers can be edited on the questionnaire page | Separate pre-submission answer overview and correction flow |
| Final storage (T-08) | Answers are created on POST; forms exist beforehand | Define submitted-form lifecycle, atomic save and duplicate/replay handling |
| Standard questionnaire | Seeder defines ten questions, options and template positions | Verify end-to-end behavior after changes |
| Question configuration | Database content initialized from PHP seeder | Required separate configuration-file approach remains absent |
| Optional template management (O-01–O-03) | Creation, independent duplication, usage previews scoped to instructor-owned sessions, comments, course assignment and unused-template deletion | Direct editing/versioning |
| Evaluation and filtering | Authorized per-session counts, percentages, pie charts, written responses/comments and overview filters | Combined result filters; abstention/submission counting policy |
| One-page A4 output | Browser print/PDF button and compact portrait A4 chart summary; written answers/comments remain on screen | Verify standard ten-question summary fits one page; long questionnaire handling |
| Account profile | Personal detail updates and password changes with current-password validation; regression tests | Browser review |
| Backups and CSV import | Admin settings page; POST routes store/download an existing demo SQL file through services, with error reporting and regression tests; admin-only course/session CSV imports with row errors and an import selection page | Real database export tool, administrator backup/restore procedure and instructor/questionnaire imports |
| Deletion | Admin course/session deletion with feedback cleanup; template assignment guard and unused-question cleanup; user assignment guard | Individual submitted feedback-form deletion |
| Validation/security | Auth/session handling, login throttling, escaped Blade output, CSRF, shared authorization gates and validated atomic feedback submission | Comprehensive role authorization and production scheduler setup |

## Data interpretation and integrity

- Multiple submissions currently append answers for the same form/question, including identical retries. The existing feature test explicitly expects this behavior; it does not establish compliance with one completed evaluation per form.
- Sessions now save their questionnaire template on creation; course default changes affect only new sessions. The column is defined in the original session-table migration and assumes fresh migration. This does not freeze question/option content: template versioning and protection against editing used questions remain unfinished. Forms still have no submission timestamp.
- Database foreign keys validate existence, but do not enforce that a selected option belongs to the submitted question or that the question belongs to the assigned template.
- Feedback submission validates question/option membership and saves the complete answer set in a transaction; a failed write rolls back all answers from that request.
- Role names and gates define authorization. Instructors receive only their own sessions, including course-modal session counts; admins/editors can create courses/questionnaires and edit courses/sessions. Instructor session creation enforces self-assignment. Session filters are shown only to admins/editors.

Schema details are in [[05 Entity Relationship Model|Entity Relationship Model]]; request behavior is in [[04 Application Architecture|Application Architecture]].

## Existing verification coverage

The PHP feature suite includes authentication, users, navigation, overview, session creation, domain models, seeding, questionnaire pages and answer persistence. JavaScript tests cover combined overview filters and questionnaire-editor type refresh/scroll behavior using mocked browser objects and Bootstrap imports. PHP tests now also cover course pages, optional participant answers and questionnaire creation/validation.

The PHP suite and static analysis were run during this cleanup. Course update tests cover role access, duplicate/unchanged names, invalid templates, restored input and preservation of session templates. Current tests also encode repeated submissions appending answers to the same form; one form per session does not group answers into separate participant submissions. Passing those tests alone would not establish that all project requirements are met.

Backend cleanup verification on 6 October 2026: PHP feature tests, PHPStan, Pint, the frontend build and JavaScript tests passed using a temporary PHP 8.4 runtime. Regression tests cover unrelated questions/options, disabled comments, malformed payloads, and transaction rollback.

## Verification on 7 October 2026

The full PHP suite passed after session numbering changes (209 tests). Later targeted checks passed for duplication (35 questionnaire tests), results and pie charts (3 tests, 29 assertions), seeding (8 tests), navigation (10 JavaScript tests), formatting, PHPStan and frontend builds. These were different-stage runs; a final combined CI run is pending. The compatible local PHP is `C:/Users/yanni/.config/herd-lite/bin/php.exe`; PHPStan required a 512 MB CLI memory limit after the local 128 MB limit caused a worker crash.

The session-number migration was applied after a local database backup. Existing session fields, courses, forms and answers were compared before/after; foreign-key checks passed. Visual browser review and A4 page-count verification remain open. Answer totals are not participant/submission totals: one feedback form is shared by a session's submissions.

## CSV import update on 8 October 2026

Course imports resolve existing questionnaires by unique name. Session imports resolve courses by name and approved instructors by email, validate dates, assign session numbers and inherit course questionnaires. Invalid rows are skipped and reported; file/header errors reject the upload. Shared CSV parsing handles BOMs, quoted values, blank records and reordered headers. See [[08 CSV Imports|CSV Imports]].

Regression coverage was added for import navigation, permissions, mixed valid/invalid rows, dates, instructor eligibility, numbering, template inheritance, duplicate names, upload validation and escaped errors. The earlier herd-lite runtime documented above is not available in this workspace. Current PHP 8.2 cannot run dependencies requiring PHP 8.4; feature tests, Pint and PHPStan remain pending. Syntax checks passed for 13 PHP files, and diff checks passed. Isolated CSV reader checks passed for BOMs, quoted commas/multiline values, trimming, blank records, record numbering, malformed rows and invalid headers, using lightweight framework stubs; these do not replace the Laravel feature suite.

## Password reset update on 8 October 2026

Forgot-password and reset forms use the auth layout, generic request confirmations, guest-only routes, rate limits and Form Requests. The Laravel broker handles tokens; successful resets rotate remember tokens and preserve approval/roles. Operational logs record broker statuses without account identifiers or reset secrets. The user confirmed local log-mail delivery works. See [[09 Password Reset|Password Reset]].

Password reset regression tests were added; the available PHP 8.2 runtime cannot run installed dependencies requiring PHP 8.4. Automated Laravel, formatting and static-analysis verification remain pending.

## Questionnaire visibility update on 8 October 2026

The library now requires an authenticated user with a role. Its session usage query restricts instructors to their own sessions before eager-loading feedback forms and answers. Counts, session identifiers, dates, links and feedback badges therefore exclude other instructors' sessions. Admins/editors retain full visibility; questionnaire definitions and course assignments remain shared. Instructor empty-state text explicitly describes their assignments.

Regression tests cover shared templates, templates used only by another instructor, own-session links, absence of other-session details, the loaded session collections, full admin/editor visibility and denial for users without a role. PHP syntax checks passed for the controller, routes and test file; `git diff --check` passed. The targeted Laravel test command was attempted but blocked by Composer's PHP 8.4 requirement with the available PHP 8.2.4 runtime. Feature tests, Pint and PHPStan remain pending on a compatible runtime.
