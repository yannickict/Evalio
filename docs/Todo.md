---

kanban-plugin: board

---

## Backlog

- [ ] [Can] Add direct editing/versioning of saved questionnaire content; creation, duplication and unused-template deletion are implemented.
- [ ] Preserve historical feedback through questionnaire snapshots/versioning or an explicit immutable-content policy.
- [ ] Document production configuration and deployment, including database choice and runtime requirements.


## Todo

- [x] [Must] Enforce role-based course/session writes, admin deletion and ownership checks on results routes.
- [x] [Must] Restrict instructor overview and evaluation results to their own sessions; test other-instructor access.
- [x] [Must] Implement forgot-password/reset forms, broker token handling, validation and local log-mail delivery.
- [ ] Configure and verify production password reset email delivery.
- [ ] [Should] Detect potential duplicate courses and show a useful warning before saving.
- [ ] Make generated session numbers safe under concurrent creation through locking/retry or another collision-safe strategy.
- [ ] Implement feedback-form/code creation and distribution for newly created sessions; define code reuse and expiry behavior.
- [ ] [Must] Calculate automatic evaluation window: opens at session start, closes 14 days after session end; verify date/time boundaries.
- [ ] [Must] Add editor/admin controls to manually open/close evaluation phases and define return to automatic mode.
- [x] [Must] Enforce evaluation status on questionnaire access and submission; display status in overview.
- [ ] [Must · T-02/T-03] Add explicit cancellation without saving and verify returning to earlier questions preserves draft answers.
- [ ] Define and validate abstention counting and comment-only answer behavior for evaluations.
- [ ] [Must · T-05] Validate optional comment types/lengths and allows_comment; define comment-only answer handling.
- [ ] [Must · T-06/T-07] Add pre-submission overview of answers and editing from that overview.
- [ ] [Must · T-08] Define completed-feedback lifecycle separately from pre-created coded forms; add submission state/time and handle empty submissions.
- [ ] [Must · T-08] Save final submission atomically in a database transaction; prevent duplicate/replayed submissions according to the chosen lifecycle.
- [x] [Must] Validate submission code, payload arrays, answer types and lengths on the server.
- [x] [Must] Validate questions belong to the form's assigned template and selected options belong to their questions.
- [ ] Define abstention and participant/submission totals; current graphs count selected answer records per question.
- [ ] [Should] Add session and instructor result filters, including combinations; filter actual evaluation results with server authorization.
- [ ] [Must] Verify the standard ten-question chart summary fits one A4 page in browser print/PDF; define handling for long custom questionnaires.
- [ ] [Must] Implement administrator backups and a documented restore procedure; support a complete SQL dump of structure and data.
  - Demo settings routes and services are connected and tested. Replace the placeholder `storage/app/private/dumps/demo.sql` in `SqlDumpService` with the server export tool before treating these files as backups.
- [x] Implement admin-only course/session CSV imports with validation, row error reporting and matching form layouts.
- [ ] Add instructor and questionnaire CSV imports; confirm any remaining specified CSV formats.
- [ ] [Must] Implement individual submitted feedback deletion; course/session deletion and related-answer cleanup are implemented.
- [ ] [Must] Review anonymity across application storage, logs and feedback-code distribution; avoid participant identity linkage.
- [ ] Add meaningful feature tests for authorization, evaluation windows, optional answers, review, atomic submission, deletion, reset, CSV import and backups as these features are implemented.
- [ ] Run final combined CI after the latest changes; compatible PHP is available through herd-lite, and targeted tests/static analysis/builds have passed.
- [ ] Add npm run test:js to CI; local JavaScript tests passed.
- [ ] Verify the full participant and administrator flows in the browser, including all ten standard questions and one-page A4 output.
- [ ] Update implementation-status and architecture notes after the remaining features are completed.


- [ ] [Should] Implement question/option management through a separate configuration file; standard content is still defined in the PHP seeder.
- [ ] Add a forward migration and reconcile existing records for the one-feedback-form-per-session constraint; the original creation migration was changed.

## Doing

- [ ] Configure the production host to run Laravel scheduling every minute.

- [x] [Must] Implement course creation with questionnaire assignment and server validation.


## Testing



## Done

- [x] Restrict questionnaire usage queries to instructors' own sessions and feedback; admins/editors retain all-session visibility. Regression tests added; compatible-PHP execution pending.

- [x] Implement course editing and template assignment; preserve existing session templates.
- [x] Implement session editing with instructor/date validation and read-only course/template/number.
- [x] Add admin-only transactional course/session deletion, confirmations and dependent feedback cleanup.
- [x] Add unused-questionnaire deletion with assignment checks and shared/answered-question preservation.
- [x] Add Duplicate and edit through the prefilled creation editor; save independent templates.
- [x] Store numeric session numbers per course and derive identifiers like AID.002; migrate existing records.
- [x] Use short demo course names: AID, EPR, EXT, WEB and SQL.
- [x] Show questionnaire usage, feedback status, collapsible questions/sessions and links opening detail dialogs.
- [x] Add authorized results pages with option counts, text answers and comments.
- [x] Add SVG pie charts with counts/percentages and browser print/PDF A4 summary styling.
- [x] Verify deletion, duplication, result access/counts, graph edge cases and detail-navigation behavior with targeted tests.

- [x] Automatically open evaluations on their start date and close them on end date plus 14 days; preserve manual changes on other dates.

**Complete**
- [x] Build database-backed course overview with session counts, instructor/session details and template display.
- [x] Implement dynamic questionnaire draft editor with add/remove questions/options, type refresh and scroll/focus restoration.
- [x] Validate and save new questionnaire templates, ordered questions and options atomically; list persisted templates with question counts.
- [x] Allow skipped participant answers and blank free-text responses; update feature coverage and remove incomplete-answer warning.
- [x] Enforce at most one feedback form/code per session in the fresh-schema migration and expose the singular feedbackForm relation.
- [x] Add course-page, questionnaire-creation and JavaScript questionnaire-editor tests; local checks passed.
- [x] Set up Laravel, Blade, Bootstrap, JavaScript and Vite/Vite Plus application structure and dependency manifests.
- [x] Define relational domain schema and Eloquent models for roles/users, courses/sessions, templates/questions/options, feedback forms and answers.
- [x] Store instructor/editor/admin roles; authorize through role-name gates.
- [x] Implement registration with name/email/password validation, hashed passwords, default instructor role and unapproved account state.
- [x] Implement approval-gated login, login throttling, session regeneration and logout.
- [x] Implement administrator user approval, role assignment/change and user deletion with self-deletion and assigned-session guards.
- [x] Implement session creation using an existing course and approved instructor, date validation and generated unique stored session numbers.
- [x] Build overview cards and accessible Bootstrap detail modals showing dates, status, instructor, template and existing feedback codes.
- [x] Implement combined course/instructor filtering of overview cards in JavaScript.
- [x] Implement public six-digit code lookup and rendering of assigned questionnaire questions in template order.
- [x] Render single-choice/free-text questions and optional comment fields with linked labels and escaped output.
- [x] Persist submitted answer/option/comment records and show one-time feedback confirmation; keep forms free of participant foreign keys.
- [x] Seed the standard ten-question questionnaire, ordered template questions and answer options.
- [x] Provide local demo administrator, instructors, courses, sessions and coded feedback forms through seeders.
- [x] Add PHP feature tests covering existing authentication, users, navigation, sessions, overview, models, seeders and questionnaire persistence; local checks passed.
- [x] Add JavaScript overview-filter tests and GitHub Actions setup/build/PHP checks; local checks passed.
- [x] Correct questionnaire spelling in controller/view/test filenames, route names, URLs, links and documentation.
- [x] Update stack and ER documentation; add architecture, setup, implementation-status and linked documentation index; order notes with numeric prefixes.




%% kanban:settings
```
{"kanban-plugin":"board","list-collapse":[false,false,false,false,true]}
```
%%
