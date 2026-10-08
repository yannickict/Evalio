# CSV Imports

Administrators can open **Settings → Import CSV**, then choose **Sessions** or **Courses**. Instructor and questionnaire imports are not implemented yet.

## File format

- Upload a UTF-8, comma-separated `.csv` file, up to 2 MB. A UTF-8 BOM is accepted.
- The first record must contain unique, non-empty column headers. Required columns may appear in any order. Extra columns are ignored when creating records.
- Quote values containing commas or line breaks with double quotes. Escape quotes inside values by doubling them.
- Headers and values are trimmed. Blank records are ignored.
- Empty files and invalid headers reject the file before any writes. Invalid data records are skipped; valid records are imported. Results show counts and errors with CSV record numbers, counting the header as record 1. Quoted multiline values count as one record.
- Reference matching follows the database's equality comparison. Case sensitivity can differ between database engines.

## Courses

```csv
course_name,questionnaire_name
Python basics,Standard-Feedbackbogen
Data analysis,Standard-Feedbackbogen
```

Each row creates a course linked to an existing questionnaire by name. Both names are required and limited to 255 characters. Course and questionnaire names have database uniqueness constraints. Missing or ambiguous questionnaires and existing course names fail the row, including duplicate course names earlier in the same file. The import never updates existing courses.

## Sessions

```csv
course_name,instructor_email,start_date,end_date
Python basics,teacher@example.com,2026-10-12,2026-10-16
```

The course must exist, and the email must belong to an approved instructor. Dates must be valid calendar dates in `YYYY-MM-DD` format, with the end on or after the start. Missing references fail the row without creating a course or instructor.

Each session receives the next number within its course and inherits the course's current questionnaire. Extra CSV columns cannot override IDs, numbering, questionnaire or evaluation status. The import does not create feedback forms or open evaluations directly.

Every valid session row creates a new session, including repeated uploads. Retry only failed rows to avoid additional sessions. Imports lock the course row in a transaction on databases supporting row locks; manual creation still uses the existing model numbering logic.

## Import order

Create questionnaires and approve instructors first, import courses next, then import sessions. Resolve failed course rows before importing sessions referencing those courses.

## Implementation and verification

`ImportCoursesRequest` and `ImportSessionsRequest` authorize administrators and validate uploads. `CsvImportReader` shares header handling, trimming, quoted-value parsing, record numbering and stream cleanup. Controllers validate individual records and resolve relationships.

Schema changes stay in the original domain migration for fresh local databases. `php artisan migrate:fresh --seed` deletes existing data; use a disposable database. Ordinary `migrate` does not apply edits to a creation migration that has already run.

Feature coverage: `CourseImportTest`, `SessionImportTest`, `SettingsPageTest` and `QuestionnaireTemplateCreationTest`.

```powershell
php artisan test --filter='CourseImportTest|SessionImportTest|SettingsPageTest|QuestionnaireTemplateCreationTest'
composer lint:check
php vendor/bin/phpstan analyse --memory-limit=512M
```

See [[06 Development Setup|Development Setup]] for full project checks.
