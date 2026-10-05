## 1. Project Overview

The goal of the project is to develop a web-based feedback system for course evaluations in a training center.

Participants can anonymously evaluate individual course sessions using a standardized feedback form.

Authenticated users can manage courses and course sessions, control evaluation phases and view evaluation results according to their assigned role.

The application is implemented as a web application using PHP and Laravel.

---

## 2. Terminology

### Course

A `course` represents a group of course sessions with the same topic.

Example:

```text
AID
```

### Course Session

A `course_session` represents one concrete scheduled occurrence of a course.

Example:

```text
AID.010
```

A course can therefore have multiple course sessions.

### Evaluation Phase

The evaluation phase defines the period during which participants are allowed to evaluate a course session.

Possible states:

```text
Open
Closed
```

### Feedback Form

A feedback form is an anonymous questionnaire used by a participant to evaluate a course session.

### Participant

A participant is a person who attended a course session and evaluates it anonymously.

Participants do not require a login.

---

## 3. Technical Requirements

The application must be implemented as a web-based application.

The following technical requirements apply:

- PHP is used as the server-side technology.
- The application uses a relational SQL database.
- A complete SQL dump containing structure and data must be possible.
- The application is accessed using a standard web browser.
- No additional client installation is required.

The selected implementation stack is:

```text
PHP ^8.3 (PHP 8.4 in CI)
Laravel ^13.17
Blade
Bootstrap ^5.3.8
JavaScript
Vite ^8.0.0 / Vite Plus 0.3.0
SQLite (local example and tests)
PHPUnit ^12.5.36
```

---

## 4. Roles and Permissions

The system distinguishes between:

```text
Participant
Instructor
Editor
Administrator
```

The principle of least privilege should be applied.

### Participant

Participants:

- Do not require a login
- Can evaluate individual course sessions
- Submit feedback anonymously
- Cannot access administration functionality

### Instructor

Instructors:

- Have a user account
- Can log in
- Can view evaluations of their own courses/course sessions
- Must not access evaluations belonging to other instructors

### Editor

Editors:

- Have a user account
- Can log in
- Can manage courses
- Can manage course sessions
- Can filter and evaluate course sessions
- Can manually control evaluation phases

### Administrator

Administrators have full access.

In addition to the editor functionality, administrators can:

- Approve registrations
- Assign user roles
- Create backups
- Delete course sessions
- Delete courses
- Delete instructors
- Delete individual submitted feedback forms
- Manage the optional questionnaire administration

---

## 5. Registration and Authentication

Instructors, editors and administrators can register themselves.

### Registration

Every new registration must be approved by an administrator before the user is allowed to log in.

A newly registered user receives the role:

```text
Instructor
```

by default.

During approval, an administrator can assign a different role.

### Login

Approved users can log into the application.

Participants do not log in.

### Forgot Password

The application must provide a **Forgot Password** function.

Users must therefore be able to reset a forgotten password.

Passwords must be stored securely using password hashing and must never be stored as plain text.

---

## 6. Course Management

The application distinguishes between courses and course sessions.

### Course

A course groups course sessions with the same topic.

Example:

```text
Course:
AID
```

Authorized users must be able to create and modify courses.

When creating courses, possible duplicates should be detected.

Duplicate detection is a **Should requirement**.

---

## 7. Course Sessions

A course session represents one specific scheduled occurrence of a course.

Each course session stores at least:

- Unique course session number
- Instructor
- Evaluation phase status
- Start date
- End date
- Anonymous submitted feedback forms

Example:

```text
Course:          AID
Course Session:  AID.010
Instructor:      Max Müller
Start Date:      05.10.2026
End Date:        07.10.2026
```

The course session number must uniquely identify the course session.

Examples:

```text
EXT.063
EPR.506
AID.010
```

Authorized users must be able to create and modify course sessions.

---

## 8. Evaluation Phase

A course session can only be evaluated while its evaluation phase is open.

### Automatic Evaluation Phase

The evaluation phase automatically:

1. Opens when the course session begins
2. Remains open during the course session
3. Closes 14 days after the course session ends

Example:

```text
Course starts:
05.10.2026

Course ends:
07.10.2026

Evaluation opens:
05.10.2026

Evaluation closes:
21.10.2026
```

### Manual Control

Editors and administrators can manually:

- Open an evaluation phase
- Close an evaluation phase

The status of every course session is stored as:

```text
Open
Closed
```

---

## 9. Participant Feedback

Participants evaluate course sessions anonymously and without logging in.

### T-01 – Anonymous Evaluation

A participant can evaluate one individual course session without logging in.

The evaluation must be completely anonymous.

### T-02 – Cancel Evaluation

The participant can cancel the questionnaire at any time.

### T-03 – Navigate Back

While completing the questionnaire, the participant can return to any previous question.

### T-04 – Optional Questions

Not every question has to be answered.

Participants are allowed to abstain from answering questions.

### T-05 – Additional Comments

For questions with predefined answer options, an additional short free-text comment can be entered.

Example:

```text
Question:
The quality of the exercises was...

Selected answer:
Good

Optional comment:
Some exercises could have been longer.
```

### T-06 – Review

Before submitting the questionnaire, an overview of all answers is displayed.

### T-07 – Edit Before Submission

Answers can still be corrected from the overview before the questionnaire is submitted.

### T-08 – Save Only on Final Submission

The feedback form is only permanently stored after the participant explicitly submits the completed questionnaire.

Cancelling an unfinished questionnaire must therefore not create a submitted feedback form.

---

## 10. Standard Feedback Form

The standard questionnaire contains ten questions.

### Question 1

```text
My prerequisites for this course were ...
```

Possible answers:

```text
Very good
Good
Satisfactory
Low
```

Optional comment: Yes

### Question 2

```text
The quality of the exercises in the course was ...
```

Possible answers:

```text
Very good
Good
Satisfactory
Low
```

Optional comment: Yes

### Question 3

```text
I perceived the course atmosphere as ...
```

Possible answers:

```text
Motivating
Pleasant
Good
Exhausting
Boring
```

Optional comment: Yes

### Question 4

```text
The instructor conducted the course ...
```

Possible answers:

```text
Very pleasantly
Pleasantly
Only partially pleasantly
Unpleasantly
```

Optional comment: Yes

### Question 5

```text
The instructor was prepared in terms of content and organization ...
```

Possible answers:

```text
Very well prepared
Well prepared
Only partially prepared
Unprepared
```

Optional comment: Yes

### Question 6

```text
Would you recommend the instructor?
```

Possible answers:

```text
Yes
No
```

Optional comment: Yes

### Question 7

```text
The instructor's professional competence was ...
```

Possible answers:

```text
Very competent
Competent
Only partially competent
Incompetent
```

Optional comment: Yes

### Question 8

```text
Would you recommend this course?
```

Possible answers:

```text
Yes
No
```

Optional comment: Yes

### Question 9

```text
Enter two or three short points that come to mind about this course.
```

Answer:

```text
Free text
```

No additional comment is required because the answer itself is free text.

### Question 10

```text
If you had to give an overall grade, what grade would you give the training center?
```

Possible answers:

```text
Grade 1
Grade 2
Grade 3
Grade 4
Grade 5
```

Optional comment: Yes

---

## 11. Answer Selection

Questions with predefined answers provide multiple **possible answer options**, but the participant can select a maximum of **one answer per question**.

For example:

```text
The quality of the exercises was:

( ) Very good
(x) Good
( ) Satisfactory
( ) Low
```

The system does **not** require multiple-choice questions where several options can be selected simultaneously.

Therefore:

```text
Single Choice → supported
Free Text     → supported
Multiple Selection → not required
```

A question can also remain unanswered.

---

## 12. Question Configuration

Questions and answer options must not have to be hardcoded directly into the application source code.

According to the standard requirements, questions and answers are managed through a **configuration file**.

The configuration must allow:

- Additional questions to be added
- Existing questions to be changed
- Answer options to be added
- Existing answer options to be changed

Management through the configuration file is a **Should requirement**.

---

## 13. Optional Questionnaire Management

A graphical questionnaire management tool is an optional extension.

### O-01 – Configurable Questions

Questions can be freely configured using an administration tool instead of only being maintained through the configuration file.

### O-02 – Questionnaire Templates

Configured questionnaires can be stored as reusable templates.

### O-03 – Assign Templates

Stored questionnaire templates can be assigned to individual courses.

These three features are **Can requirements** and are therefore not required for the basic version of the application.

---

## 14. Feedback Storage

Submitted feedback belongs to one specific course session.

A submitted feedback form contains the participant's answers but no participant identity.

Conceptually:

```text
Course
    ↓
Course Session
    ↓
Feedback Form
    ↓
Responses
```

There must not be a relationship such as:

```text
Participant/User
       ↓
Feedback Form
```

because feedback must remain anonymous.

---

## 15. Feedback Responses

Each submitted feedback form contains responses to individual questions.

A response can contain:

### Selected Answer

For questions with predefined options:

```text
Question:
The quality of the exercises was...

Answer:
Good
```

Only one option can be selected.

### Free-Text Answer

For a free-text question:

```text
Question:
Enter two or three short points...

Answer:
Good exercises and clear explanations.
```

### Optional Comment

Questions with predefined options can additionally contain a comment:

```text
Answer:
Good

Comment:
More examples would have been useful.
```

---

## 16. Evaluation

Authenticated users can view evaluations according to their role.

Instructors can only view evaluations belonging to their own courses/course sessions.

Editors and administrators can evaluate course sessions more broadly.

The evaluation must support displaying the results of individual course sessions.

---

## 17. Filtering

Editors and administrators should be able to filter evaluations by:

- Course session
- Instructor
- Combination of course session and instructor

The complete evaluation of the filtered result should then be displayed.

Filtering combinations are a **Should requirement**.

---

## 18. A4 Evaluation Output

The evaluation of a course session must be printable in a clear format on **one A4 page**.

The output should summarize the evaluation results in a compact and readable form.

This is a **Must requirement**.

---

## 19. Backup

Administrators must be able to create backups.

Backup functionality is a **Must requirement**.

The backup must allow the relevant application/database data to be secured so that it can be restored if required.

---

## 20. CSV Import

The application must provide the required CSV import functionality.

CSV import is listed as a **Must requirement**.

The exact imported data and CSV structure should follow the specification provided for the project.

---

## 21. Deletion

Administrators can delete:

- Course sessions
- Instructors
- Individual submitted feedback forms
- Courses

Deletion functionality must only be accessible to administrators.

Appropriate database relationships must be considered to prevent invalid references when related records are deleted.

---

## 22. Database

The application uses a relational SQL database.

The database structure must support:

```text
Users and roles
Courses
Course sessions
Questions
Answer options
Anonymous feedback forms
Responses
```

If the optional questionnaire management is implemented, the database additionally supports:

```text
Questionnaire templates
Questions assigned to templates
Templates assigned to courses
```

The database must allow a complete SQL dump containing:

- Database structure
- Database data

---

## 23. Validation

All relevant input must be validated on the server.

Examples include:

- Required user registration information
- Valid email addresses
- Unique course session numbers
- Valid dates
- Existing courses
- Existing instructors
- Valid question options
- Valid roles

Questions in the participant questionnaire are explicitly **not required**.

---

## 24. Security

The application must implement appropriate security measures.

At minimum:

- Passwords are hashed
- Authentication is required for protected functionality
- Authorization is based on roles
- Instructors cannot access other instructors' evaluations
- User input is validated
- CSRF protection is enabled
- Database access is protected against SQL injection
- Feedback remains anonymous
- Secrets are not stored in Git

Laravel's built-in security functionality should be used wherever possible.

---

## 25. Requirement Priorities

The requirements are divided into three priority levels.

### Must

The core application must provide:

- Web-based application
- Role and permission system
- Anonymous participant evaluations
- Evaluation phases
- Course management
- Course session management
- Evaluation functionality
- A4 evaluation output
- Registration with administrator approval
- Backup
- CSV import
- Forgot-password functionality

### Should

The application should provide:

- Duplicate detection when creating courses
- Filter combinations for evaluations
- Question management through a configuration file

### Can

Optional extensions include:

- Graphical tool for freely configuring questions
- Saving configured questionnaires as templates
- Assigning questionnaire templates to courses

---

## 26. Main Application Flow

The basic process is:

```text
Create / Manage Course
        │
        ▼
Create Course Session
        │
        ▼
Assign Instructor
        │
        ▼
Course Session Starts
        │
        ▼
Evaluation Automatically Opens
        │
        ▼
Participants Submit Anonymous Feedback
        │
        ▼
14 Days After Course Session Ends
        │
        ▼
Evaluation Automatically Closes
        │
        ▼
Instructor / Editor / Administrator
Views Evaluation
```

Editors and administrators can manually override the evaluation phase when required.