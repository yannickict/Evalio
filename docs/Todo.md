---

kanban-plugin: board

---

## Backlog

- [ ] [Can · O-01] Complete questionnaire management: configurable comments, editing/deleting saved questions/templates and administrator authorization.
- [ ] [Can · O-02] Add editing/deletion and management of existing saved templates; creation and library listing are implemented.
- [ ] [Can · O-03] Add course UI for assigning questionnaire templates.
- [ ] Preserve historical feedback through questionnaire snapshots/versioning or an explicit immutable-content policy.
- [ ] Document production configuration and deployment, including database choice and runtime requirements.


## Todo

- [ ] [Must] Enforce editor/admin authorization on course/session writes and administrative endpoints; consistently apply role permissions.
- [ ] [Must] Restrict instructor overview and evaluation queries to their own course sessions; test access to other instructors' records.
- [ ] [Must] Implement forgot-password request/reset pages, routes, token validation and mail delivery.
- [ ] [Should] Detect potential duplicate courses and show a useful warning before saving.
- [ ] [Must] Implement course-session editing, including course, instructor, dates and unique session number validation.
- [ ] Make generated session numbers safe under concurrent creation through locking/retry or another collision-safe strategy.
- [ ] Implement feedback-form/code creation and distribution for newly created sessions; define code reuse and expiry behavior.
- [ ] [Must] Calculate automatic evaluation window: opens at session start, closes 14 days after session end; verify date/time boundaries.
- [ ] [Must] Add editor/admin controls to manually open/close evaluation phases and define return to automatic mode.
- [ ] [Must] Enforce evaluation phase on questionnaire access and submission; display effective status in overview.
- [ ] [Must · T-02/T-03] Add explicit cancellation without saving and verify returning to earlier questions preserves draft answers.
- [ ] Define and validate abstention counting and comment-only answer behavior for evaluations.
- [ ] [Must · T-05] Validate optional comment types/lengths and allows_comment; define comment-only answer handling.
- [ ] [Must · T-06/T-07] Add pre-submission overview of answers and editing from that overview.
- [ ] [Must · T-08] Define completed-feedback lifecycle separately from pre-created coded forms; add submission state/time and handle empty submissions.
- [ ] [Must · T-08] Save final submission atomically in a database transaction; prevent duplicate/replayed submissions according to the chosen lifecycle.
- [x] [Must] Validate submission code, payload arrays, answer types and lengths on the server.
- [x] [Must] Validate questions belong to the form's assigned template and selected options belong to their questions.
- [ ] [Must] Build evaluation results for individual sessions: option totals/distributions, free-text answers and comments; define how abstentions are counted.
- [ ] [Should] Add session and instructor result filters, including combinations; filter actual evaluation results with server authorization.
- [ ] [Must] Implement a compact printable evaluation on one A4 page.
- [ ] [Must] Implement administrator backups and a documented restore procedure; support a complete SQL dump of structure and data.
- [ ] [Must] Confirm specified CSV columns/data and implement validated CSV import with useful error reporting.
- [ ] [Must] Add administrator course, session and individual feedback-form deletion workflows with related-record handling.
- [ ] [Must] Review anonymity across application storage, logs and feedback-code distribution; avoid participant identity linkage.
- [ ] Add meaningful feature tests for authorization, evaluation windows, optional answers, review, atomic submission, deletion, reset, CSV import and backups as these features are implemented.
- [ ] Re-run PHP tests, formatting, static analysis and frontend build with a compatible runtime; available XAMPP PHP 8.2.4 cannot load installed dependencies requiring PHP 8.4+.
- [ ] Run overview and questionnaire-editor JavaScript tests and include npm run test:js in CI.
- [ ] Verify the full participant and administrator flows in the browser, including all ten standard questions and one-page A4 output.
- [ ] Update implementation-status and architecture notes after the remaining features are completed.


- [ ] [Should] Implement question/option management through a separate configuration file; standard content is still defined in the PHP seeder.
- [ ] Add a forward migration and reconcile existing records for the one-feedback-form-per-session constraint; the original creation migration was changed.

## Doing

- [ ] [Must] Implement course creation and editing with server validation.


## Testing



## Done

**Complete**
- [x] Build database-backed course overview with session counts, instructor/session details and template display; course creation remains unfinished.
- [x] Implement dynamic questionnaire draft editor with add/remove questions/options, type refresh and scroll/focus restoration.
- [x] Validate and save new questionnaire templates, ordered questions and options atomically; list persisted templates with question counts.
- [x] Allow skipped participant answers and blank free-text responses; update feature coverage and remove incomplete-answer warning.
- [x] Enforce at most one feedback form/code per session in the fresh-schema migration and expose the singular feedbackForm relation.
- [x] Add course-page, questionnaire-creation and JavaScript questionnaire-editor tests; execution remains unverified in this documentation review.
- [x] Set up Laravel, Blade, Bootstrap, JavaScript and Vite/Vite Plus application structure and dependency manifests.
- [x] Define relational domain schema and Eloquent models for roles/users, courses/sessions, templates/questions/options, feedback forms and answers.
- [x] Store instructor/editor/admin roles and permission flags.
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
- [x] Add PHP feature tests covering existing authentication, users, navigation, sessions, overview, models, seeders and questionnaire persistence; execution still requires verification.
- [x] Add JavaScript overview-filter tests and GitHub Actions setup/build/PHP checks; test execution still requires verification.
- [x] Correct questionnaire spelling in controller/view/test filenames, route names, URLs, links and documentation.
- [x] Update stack and ER documentation; add architecture, setup, implementation-status and linked documentation index; order notes with numeric prefixes.




%% kanban:settings
```
{"kanban-plugin":"board","list-collapse":[false,false,false,false,true]}
```
%%