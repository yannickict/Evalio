# Localization

German is the default interface language. `config/app.php` defaults to `de`, and `.env.example` and the local `.env` use `APP_LOCALE=de`. The **DE / EN** controls appear in the main header and above authentication forms. Both guests and signed-in users can change language. An encrypted, HttpOnly cookie remembers the choice for one year; it survives login and logout. Without a valid preference cookie, the configured default applies.

`POST /language` validates the selected language and returns to a local application path, retaining query parameters such as questionnaire codes. External return URLs are rejected in favour of the home page. The form has CSRF protection and works without JavaScript. `SetLocale` runs after cookie decryption and applies the preference to web requests. `APP_LOCALE=en` can still change the installation default; clear or rebuild the configuration cache after changing environment settings.

## Interface and messages

`lang/de.json` and `lang/en.json` contain interface labels, status/error messages, accessibility labels, confirmation dialogs and password-reset email wording. Blade views and controllers use Laravel's translation helpers. `lang/de` also includes German validation, authentication, password-broker and pagination messages; `lang/en` supplies the English equivalents. Validation attributes use readable German field names.

German wording uses Swiss spelling and formal address. Displayed dates use `dd.mm.yyyy` in German, including session details and printed results. HTML language attributes reflect the active locale. CSV headers, identifiers, role names, database fields and HTML date-input values retain their existing technical formats.

German interface terminology uses **Kurse**, **Lehrgänge**, **Feedbackbögen** and **Bewertungsphasen**. Counts, navigation, forms, validation messages and phase controls use these terms consistently. English interface terminology and all stored content remain unchanged.

## Questionnaire content

Only hardcoded interface text is translated. Questionnaire names, questions, answer options, course names, participant answers and comments appear exactly as stored, in the language they were written in. This applies equally to the standard questionnaire and custom questionnaires, participant forms, library previews, result charts, printed reports and duplication. Duplicating a template preserves its content and adds only the localized hardcoded copy suffix. No stored content was rewritten and no data migration is needed.

With JavaScript enabled, switching language preserves the participant's current answers and comments across the reload using temporary browser session storage; they are removed from that storage after restoration. No answers are submitted by the switch. The questionnaire editor updates the language cookie and refreshes its complete draft through the existing preview action, preserving added questions and options. If browser storage is unavailable, participant draft restoration is unavailable; without JavaScript, switching reloads the page normally.

## Verification

The existing feature tests explicitly use English via `phpunit.xml`. `LocalizationTest` switches to German and covers guest/admin pages, validation and login errors, reset emails, original-language standard/custom content, dates, CSV errors/counts, feedback submission and duplication. `LanguageSwitchTest` covers both languages, remembered preferences, guest/authenticated layouts, invalid input and safe return paths. JavaScript tests verify participant/editor draft preservation and failure handling.

Use the compatible PHP 8.4 runtime documented in [[06 Development Setup|Development Setup]] to run `php artisan test`, Pint and PHPStan. JavaScript checks remain `npm run test:js` and `npm run build`.
