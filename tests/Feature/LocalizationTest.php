<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\CourseSession;
use App\Models\FeedbackForm;
use App\Models\Question;
use App\Models\QuestionnaireTemplate;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\QuestionnaireSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->app->setLocale('de');
    }

    public function test_guest_pages_render_in_german_and_english_remains_available(): void
    {
        $this->get(route('home'))->assertOk()->assertSee('lang="de"', false)
            ->assertSee('Geben Sie uns Feedback.')->assertSee('Fragebogen öffnen');
        $this->get(route('login'))->assertOk()->assertSee('Willkommen zurück')->assertSee('Passwort vergessen?');
        $this->get(route('register'))->assertOk()->assertSee('Vorname')->assertSee('Benutzerkonto erstellen');
        $this->get(route('password.request'))->assertOk()->assertSee('Link zum Zurücksetzen senden');

        $this->app->setLocale('en');
        $this->get(route('home'))->assertOk()->assertSee('lang="en"', false)
            ->assertSee('Share your feedback.')->assertSee('Open questionnaire');
    }

    public function test_validation_and_login_errors_are_german(): void
    {
        $validator = Validator::make(['email' => 'invalid'], ['email' => ['required', 'email'], 'password' => ['required']]);
        $this->assertSame('Das Feld E-Mail-Adresse muss eine gültige E-Mail-Adresse enthalten.', $validator->errors()->first('email'));
        $this->assertSame('Das Feld Passwort ist erforderlich.', $validator->errors()->first('password'));
        $this->post(route('login.store'), ['email' => 'unknown@example.com', 'password' => 'wrong-password'])
            ->assertSessionHasErrors(['email' => 'Ungültige E-Mail-Adresse oder ungültiges Passwort.']);
        $this->assertSame('Dieser Link zum Zurücksetzen des Passworts ist ungültig.', __('passwords.token'));
    }

    public function test_standard_questionnaire_keeps_its_original_language_with_german_interface(): void
    {
        $this->seed(QuestionnaireSeeder::class);
        $template = QuestionnaireTemplate::where('name', 'Standard Course Evaluation')->sole();
        $session = CourseSession::factory()->create(['questionnaire_template_id' => $template->id, 'evaluation_status' => 'open']);
        $form = FeedbackForm::factory()->for($session)->create(['code' => '123456']);

        $this->get(route('feedback.show', ['code' => $form->code]))->assertOk()
            ->assertSee('My prerequisites for this course were ...')
            ->assertSee('Very good')->assertSee('Would you recommend this course?')
            ->assertSee('Grade 5')->assertSee('Feedback absenden')
            ->assertDontSee('Meine Vorkenntnisse für diesen Kurs waren ...');
        $this->assertDatabaseHas('questions', ['question_text' => 'My prerequisites for this course were ...']);
        $this->assertDatabaseHas('question_options', ['option_text' => 'Very good']);

        $this->actingAs($session->instructor)->get(route('questionnaires.index'))->assertOk()
            ->assertSee('Standard Course Evaluation')->assertSee('My prerequisites for this course were ...');
        $this->app->setLocale('en');
        $this->get(route('feedback.show', ['code' => $form->code]))->assertOk()
            ->assertSee('My prerequisites for this course were ...')->assertSee('Very good');
    }

    public function test_custom_content_and_participant_comments_are_preserved_in_german_results(): void
    {
        $template = QuestionnaireTemplate::factory()->create(['name' => 'Custom feedback']);
        $question = Question::factory()->create(['question_text' => 'Home', 'type' => 'single_choice']);
        $option = $question->options()->create(['option_text' => 'Settings']);
        $template->questions()->attach($question->id, ['position' => 1]);
        $session = CourseSession::factory()->create([
            'questionnaire_template_id' => $template->id,
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-07',
        ]);
        $form = FeedbackForm::factory()->for($session)->create();
        Answer::create(['feedback_form_id' => $form->id, 'question_id' => $question->id, 'question_option_id' => $option->id, 'comment' => '<script>Home</script>']);

        $this->actingAs($session->instructor)->get(route('sessions.results', $session))->assertOk()
            ->assertSee('Bewertungsergebnisse')->assertSee('Drucken / Als PDF speichern')
            ->assertSee('05.10.2026')->assertSee('07.10.2026')
            ->assertSee('Home')->assertSee('Settings')
            ->assertSee('&lt;script&gt;Home&lt;/script&gt;', false)->assertDontSee('<script>Home</script>', false);
    }

    public function test_admin_pages_and_workflow_descriptions_are_translated(): void
    {
        $admin = User::factory()->approved()->create(['role_id' => Role::where('name', 'admin')->sole()->id]);
        $this->actingAs($admin);

        $this->get(route('settings.index'))->assertOk()->assertSee('Sicherung erstellen')->assertSee('SQL-Export erstellen');
        $this->get(route('settings.imports'))->assertOk()->assertSee('Kurse importieren')->assertSee('Kursdurchführungen importieren');
        $this->get(route('settings.courses-import.create'))->assertOk()->assertSee('CSV-Datei vorbereiten');
        $this->get(route('settings.sessions-import.create'))->assertOk()->assertSee('Datei hochladen');
        $this->get(route('users.index'))->assertOk()->assertSee('Registrierungen freigeben')->assertSee('Redakteur');
        $this->get(route('courses.index'))->assertOk()->assertSee('Fragebogen erstellen')->assertSee('Kursdurchführung planen');
        $this->get(route('sessions.index'))->assertOk()->assertSee('Alle Kursleitungen');
        $this->get(route('profile.show'))->assertOk()->assertSee('Benutzerprofil')->assertSee('Administrator');
        $this->get(route('questionnaires.create'))->assertOk()->assertSee('Fragebogen speichern')->assertSee('Antworttyp');
    }

    public function test_password_reset_email_is_translated(): void
    {
        $user = User::factory()->approved()->create();
        $mail = (new ResetPassword('example-token'))->toMail($user);

        $this->assertSame('Setzen Sie Ihr Passwort zurück', $mail->subject);
        $this->assertSame('Passwort zurücksetzen', $mail->actionText);
        $this->assertStringContainsString('Sie erhalten diese E-Mail', $mail->introLines[0]);
        $this->assertStringContainsString('Minuten', $mail->outroLines[0]);
        $html = (string) $mail->render();
        $this->assertStringContainsString('Guten Tag!', $html);
        $this->assertStringContainsString('Freundliche Grüsse', $html);
        $this->assertStringContainsString('Falls Sie die Schaltfläche', $html);
    }

    public function test_csv_import_errors_and_counts_are_german_and_escape_uploaded_names(): void
    {
        $admin = User::factory()->approved()->create(['role_id' => Role::where('name', 'admin')->sole()->id]);
        $this->actingAs($admin);
        $file = UploadedFile::fake()->createWithContent('courses.csv', "course_name,questionnaire_name\nNew course,<script>Missing</script>\n");

        $this->post(route('settings.courses-import.store'), ['file' => $file])->assertOk()
            ->assertSee('Importierte Kurse: 0. Übersprungene Zeilen: 1.')
            ->assertSee('Zeile 2: Fragebogen nicht gefunden: &lt;script&gt;Missing&lt;/script&gt;.', false)
            ->assertDontSee('<script>Missing</script>', false);
    }

    public function test_german_feedback_submission_keeps_question_ids_and_translates_confirmation(): void
    {
        $this->seed(QuestionnaireSeeder::class);
        $template = QuestionnaireTemplate::where('name', 'Standard Course Evaluation')->sole();
        $session = CourseSession::factory()->create(['questionnaire_template_id' => $template->id, 'evaluation_status' => 'open']);
        $form = FeedbackForm::factory()->for($session)->create(['code' => '654321']);
        $question = $template->questions->first();
        $option = $question->options->first();

        $this->post(route('feedback.store', ['code' => $form->code]), [
            'answers' => [$question->id => $option->id],
            'answers_comment' => [$question->id => 'Good examples'],
        ])->assertRedirect(route('home'))
            ->assertSessionHas('status', 'Vielen Dank! Ihr Feedback wurde übermittelt.');
        $this->assertDatabaseHas('answers', [
            'feedback_form_id' => $form->id,
            'question_id' => $question->id,
            'question_option_id' => $option->id,
            'comment' => 'Good examples',
        ]);
    }

    public function test_duplicating_a_questionnaire_preserves_its_content_language(): void
    {
        $this->seed(QuestionnaireSeeder::class);
        $template = QuestionnaireTemplate::where('name', 'Standard Course Evaluation')->sole();
        $editor = User::factory()->approved()->create(['role_id' => Role::where('name', 'editor')->sole()->id]);

        $response = $this->actingAs($editor)->get(route('questionnaires.duplicate', $template))->assertOk();
        $draft = $response->viewData('draft');
        $this->assertSame('Standard Course Evaluation (Kopie)', $draft['name']);
        $this->assertSame('My prerequisites for this course were ...', $draft['questions'][0]['text']);
        $this->assertSame(['Very good', 'Good', 'Satisfactory', 'Low'], $draft['questions'][0]['options']);
        $this->assertDatabaseCount('questionnaire_templates', 1);
    }
}
