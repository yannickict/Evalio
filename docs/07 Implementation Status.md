# Implementation Status

Reviewed against source code on 12 October 2026. [[02 Project Requirements|Project Requirements]] remains the specification; this note records implemented behavior and remaining gaps without changing requirement priorities.

## Requirements and current behavior

| Requirement area | Current implementation | Remaining work |
|---|---|---|
| Web application and SQL storage | Laravel, Blade, Eloquent and SQL migrations | Production database/deployment configuration |
| Roles and least privilege | Role-name gates, instructor ownership checks, admin-only deletion and admin/editor management | Production security and anonymity review |
| Registration and approval | Unapproved instructor registration, admin approval/role assignment, approval-gated login and password reset | Production reset-email delivery |
| Courses and sessions | Validated create/edit, saved session templates, numeric per-course numbering and transactional admin deletion | Collision-safe manual session numbering; forward migrations for schema changes made in original creation migrations |
| Evaluation phase | Hourly automatic opening/closing, manual controls, code creation/invalidation and status checks on questionnaire GET/POST | Production scheduler and monitoring; missed closing days require manual correction |
| Anonymous evaluation (T-01) | Public six-digit code access; no participant identity foreign key | Anonymity review of logs/infrastructure/code distribution; individual submission lifecycle |
| Cancel/back (T-02/T-03) | All questions appear on one page; no answer records before final POST | Explicit cancellation interaction |
| Optional questions (T-04) | Skipped questions accepted; completely blank answer/comment pairs are not stored | Explicit abstention reporting and individual submission totals |
| Comments (T-05) | Comment types/lengths and allowed-question membership validated; comment-only responses saved and displayed | No known storage gap; comments do not count as selected options |
| Review/edit (T-06/T-07) | Answers editable on the questionnaire page | Pre-submission answer overview and correction flow |
| Final storage (T-08) | Validated answer/comment records saved atomically on POST | Separate participant submissions, submission state/time, empty submission policy and duplicate/replay handling |
| Standard questionnaire | Seeder defines ten German questions/options; stored content retains its original language | One-page print review of the newly seeded German content; existing questionnaires are not rewritten |
| Question configuration | PHP seeder initializes database questionnaire content | Separate configuration-file approach (Should) |
| Optional template management (O-01–O-03) | Creation, independent duplication, course assignment, ownership-scoped usage previews and unused-template deletion | Direct editing/versioning and preservation of historical content |
| Evaluation and filtering | Authorized per-session counts, percentages, SVG pie charts, written answers/comments and course/instructor overview filters | Combined filters on actual evaluation results; submission/abstention totals |
| A4 output | Browser printing; tested ten-question demo summary fits one A4 page | Seeded German content and long custom questionnaires need separate print review |
| Account profile | Personal detail editing and password changes with current-password validation | No known implementation gap |
| Backups and CSV import | Admin course/session CSV imports with row errors; explicitly labeled demo-file backup/export | Real SQL export and restore procedure; instructor/questionnaire imports |
| Deletion | Admin users/courses/sessions/template deletion with assignment guards and dependent-answer cleanup | Individual submitted feedback deletion |
| Duplicate course detection | Unique names rejected during creation/import | Potential-duplicate warnings (Should) |

## Data interpretation and integrity

- A session has at most one feedback form/code. This form is shared by participants; it does not represent one completed participant questionnaire. Repeated POSTs append answers, including repeated identical submissions.
- Submission processes question IDs from both `answers` and `answers_comment`. A permitted comment without an answer creates one row with null selected-option/text fields. Fully blank pairs are skipped. Validation rejects unrelated questions/options and disabled comments; all writes share one transaction.
- Charts count selected options per question. Comment-only records remain visible as comments and do not increase option totals. Written-response counts include only nonblank text. These totals are not participant/submission totals, and skipped questions are not individually recorded as abstentions.
- Session template assignments are saved on creation. Changing a course default affects new sessions only. Content is not snapshotted or versioned; changing question/option content can alter historical interpretation.
- The template-assignment, unique-template-name and one-form-per-session constraints were added in original creation migrations. Existing databases need forward migrations/data reconciliation; ordinary migration does not replay altered creation migrations. A separate forward session-number migration is present.
- Automatic evaluation updates open on the start date, catch up unset sessions within the open window and close exactly on end date plus 14 days. Manual changes can be overridden on boundary dates; missed closing days are not caught up automatically.

## Interface and browser state

Authenticated workspace pages use a responsive Bootstrap sidebar/offcanvas drawer, a compact account/language bar, light backgrounds and blue actions. Course/session/questionnaire collections use rows. Forms, imports, user management and participant pages share restrained typography and spacing. Both layouts include `public/favicon.svg`.

Modal controls use compact secondary styles and separate destructive actions. After a manual evaluation-status update, the redirect selects the same session modal. JavaScript restores matching scroll position and course/instructor filters from temporary session storage before reopening it. Without JavaScript or browser storage, this enhancement is limited; server-side status updates still work.

German is the interface default; DE/EN controls remember the preference and preserve participant/editor drafts with JavaScript. The seeded standard questionnaire is German independently of the interface language. Existing database content is not translated or rewritten. See [[10 Localization|Localization]].

## Latest local verification

On 12 October 2026, the full PHP suite passed: **271 tests and 2,071 assertions**. This includes comment-only persistence/result display, blank-answer handling and evaluation-status redirect regressions. All **17 JavaScript tests**, the frontend build, Pint, PHPStan, Blade compilation and diff checks passed. PHP checks used the compatible herd-lite PHP 8.4 runtime.

The redesign was reviewed in a headless browser with overflow checks at 1440, 768, 375 and 320 pixels. Checks covered drawer focus/Escape dismissal, session dialogs, course filtering and participant answer selection. The tested ten-question demo result produced a single A4 PDF. That snapshot used existing stored questionnaire content; newly seeded German questions and custom content require separate print review.

These are local checks. Remote CI and production deployment/email/scheduling/backup operations have not been verified. CI builds the frontend and runs PHP checks, but does not currently run the separate JavaScript test command.

See [[04 Application Architecture|Application Architecture]], [[05 Entity Relationship Model|Entity Relationship Model]] and [[06 Development Setup|Development Setup]] for implementation details and verification commands. [[Todo]] summarizes the main remaining work.
