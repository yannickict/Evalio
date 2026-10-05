# Application Architecture

Source review: 5 October 2026. Related: [[03 Programmier-Stack|Programmier-Stack]], [[05 Entity Relationship Model|Entity Relationship Model]], [[07 Implementation Status|Implementation Status]].

## Structure

The application uses Laravel with server-rendered Blade pages. `routes/web.php` defines web endpoints; controllers in `app/Http/Controllers` process requests; Eloquent models in `app/Models` access the relational database. Migrations in `database/migrations` define constraints. Vite builds `resources/css/app.css` and `resources/js/app.js`.

```mermaid
flowchart LR
    Browser --> Routes[Web routes]
    Routes --> Controllers
    Controllers --> Models[Eloquent models]
    Models --> Database[SQL database]
    Controllers --> Blade[Blade views]
    Blade --> Browser
    Vite --> Assets[CSS and JavaScript]
    Assets --> Browser
```

## Route map

Paths and handler names below match the repository.

| Method | Path | Handler | Access in current code |
|---|---|---|---|
| GET | `/` | `home` view | Public |
| GET | `/register`, `/login` | Registration/login views | Public |
| POST | `/register` | `AuthController::register` | Public |
| POST | `/login` | `AuthController::login` | Public; throttle `5,1` |
| POST | `/logout` | `AuthController::logout` | Authenticated |
| GET | `/questionnaire?code=…` | `QuestionnaireController::index` | Public; six-digit code lookup |
| POST | `/questionnaire?code=…` | `QuestionnaireController::submit` | Public; existing form lookup |
| GET | `/overview` | `OverviewController::index` | Authenticated; user must have a role |
| GET | `/session/create` | `SessionController::index` | Authenticated |
| POST | `/session` | `SessionController::store` | Authenticated |
| GET | `/courses` | `CourseController::index` | Authenticated; user must have a role |
| GET | `/courses/create` | Static course creation placeholder | Authenticated |
| GET | `/questionnaires` | `QuestionnaireController::library` | Authenticated |
| GET | `/questionnaires/create` | `QuestionnaireController::create` | Authenticated |
| POST | `/questionnaires/preview` | `QuestionnaireController::preview` | Authenticated; draft changes only |
| POST | `/questionnaires` | `QuestionnaireController::store` | Authenticated; persistent template creation |
| GET | `/users` | `UserController::index` | Administrator |
| PATCH | `/users/{user}` | `UserController::update` | Administrator |
| DELETE | `/users/{user}` | `UserController::destroy` | Administrator; cannot delete self |

Web forms use CSRF tokens. Authorization checks are currently in controllers and navigation views; role permission columns do not constitute comprehensive endpoint authorization.

## Registration and administration

Registration validates names, unique email and a confirmed password of at least eight characters. The database supplies the default instructor role and unapproved state. Passwords use the model's hashed cast. Login requires approval, regenerates the session and redirects to the intended page. Logout invalidates the session and regenerates its CSRF token.

Administrators can approve a pending account while assigning its role, change approved users' roles, and delete users without assigned sessions. Deleting an assigned instructor returns a reassignment message. A self-demotion redirects the administrator to the home page.

## Participant feedback

1. The home page sends the entered code to `GET /questionnaire`.
2. The controller finds an existing feedback form and resolves its course's assigned template. Invalid code format produces validation errors; unknown codes or missing templates return 404.
3. Questions render in template position order, with radio options or free-text inputs and optional comments.
4. Submission sends `answers[question_id]` and `answers_comment[question_id]` to the same path using POST.
5. The controller creates answer records on the existing form and redirects home with a one-time confirmation.

Participant answers are now optional in the browser. Tests cover skipped questions and blank free-text submissions. The incomplete-answer JavaScript warning has been removed. There is no separate review step or draft storage. Opening the page does not create answer records; feedback forms and their codes already exist. Comments alone are not processed because submission iterates the answer array.

Repeated submissions append answers to the same form. The code is not consumed, and there is no submitted flag. Submission currently lacks structured payload validation, template/option membership validation, evaluation-window enforcement and a transaction around the full answer set. See [[07 Implementation Status|Implementation Status]] for the resulting requirement gaps.

## Sessions and overview

Session creation selects an existing course and approved instructor, validates dates and creates the session. The model generates `COURSE.0001`-style identifiers by scanning existing numbers. The unique database constraint prevents duplicate stored identifiers, but concurrent number generation has no locking/retry mechanism. Creation does not generate feedback forms or codes.

The overview loads all sessions, their instructors, course templates and each session's optional feedback form. The model exposes feedbackForm() as a has-one relation and the schema makes course_session_id unique in feedback_forms. Cards show dates and status; modals expose details and existing feedback codes. Course and instructor selectors combine filters in JavaScript using exact IDs. Filtering hides cards already sent to the browser and does not provide server-side access control. No aggregate answer results are displayed.

## Courses

The course overview loads every course with its questionnaire template, session count and sessions/instructors. It requires an authenticated user with a role but does not scope records by instructor. The course creation page remains a placeholder: template selection and save are disabled, and no course write endpoint exists.

## Questionnaire editor and library

The library lists persisted templates with question counts, newest first. The editor starts with one single-choice question and two options. Preview POST requests add/remove questions and options or refresh answer types without database writes. It retains at least one question and two single-choice options, with limits of 50 questions and 20 options per question. JavaScript automatically submits type changes and restores scroll/focus using sessionStorage; a refresh button remains available without JavaScript.

Saving validates the template name, question text/type and single-choice options, then creates the template, ordered question pivot records and options within a database transaction. Limits are 255 characters for the name, 5,000 for question text and 1,000 per option; single-choice questions require at least two options. New questions always have allows_comment=false. Save redirects to the database-backed library with confirmation.

All editor/library routes currently require authentication only. Administrator authorization, editing/deleting saved templates, configurable comments and course assignment UI remain unfinished. This editor preview concerns questionnaire design; it is not the participant answer-review step required before feedback submission.
