<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\FeedbackForm;
use App\Models\QuestionnaireTemplate;
use App\Models\QuestionOption;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class QuestionnaireDeletionTest extends TestCase
{
    use RefreshDatabase;

    private function signInAdmin(): void
    {
        $this->actingAs(User::factory()->approved()->create([
            'role_id' => Role::where('name', 'admin')->sole()->id,
        ]));
    }

    public function test_deletion_removes_unused_questions_and_preserves_shared_and_answered_questions(): void
    {
        $template = QuestionnaireTemplate::factory()->create();
        $other = QuestionnaireTemplate::factory()->create();
        $unused = QuestionOption::factory()->create();
        $shared = QuestionOption::factory()->create();
        $answered = QuestionOption::factory()->create();
        foreach ([$unused, $shared, $answered] as $index => $option) {
            $template->questions()->attach($option->question_id, ['position' => $index + 1]);
        }
        $other->questions()->attach($shared->question_id, ['position' => 1]);
        $form = FeedbackForm::factory()->create();
        $answer = Answer::create([
            'feedback_form_id' => $form->id,
            'question_id' => $answered->question_id,
            'question_option_id' => $answered->id,
        ]);
        $unusedQuestion = $unused->question;
        $this->signInAdmin();

        $this->delete(route('questionnaires.delete', $template))
            ->assertRedirect(route('questionnaires.index'))->assertSessionHasNoErrors();

        $this->assertModelMissing($template);
        $this->assertModelMissing($unusedQuestion);
        $this->assertModelMissing($unused);
        foreach ([$other, $shared, $shared->question, $answered, $answered->question, $answer, $form] as $record) {
            $this->assertModelExists($record);
        }
        $this->assertDatabaseMissing('questionnaire_template_question', ['questionnaire_template_id' => $template->id]);
        $this->assertDatabaseHas('questionnaire_template_question', ['questionnaire_template_id' => $other->id, 'question_id' => $shared->question_id]);
    }

    public static function assignments(): array
    {
        return ['course' => ['course'], 'session' => ['session']];
    }

    #[DataProvider('assignments')]
    public function test_assigned_questionnaires_cannot_be_deleted(string $assignment): void
    {
        $this->withoutVite();
        $template = QuestionnaireTemplate::factory()->create();
        $option = QuestionOption::factory()->create();
        $template->questions()->attach($option->question_id, ['position' => 1]);
        $record = $assignment === 'course'
            ? Course::factory()->create(['questionnaire_template_id' => $template->id])
            : CourseSession::factory()->create(['questionnaire_template_id' => $template->id]);
        $this->signInAdmin();

        $this->followingRedirects()->delete(route('questionnaires.delete', $template))
            ->assertOk()
            ->assertSee('This questionnaire is assigned to a course or session and cannot be deleted.');

        foreach ([$template, $option, $option->question, $record] as $model) {
            $this->assertModelExists($model);
        }
    }

    public function test_editor_and_instructor_and_guest_cannot_delete(): void
    {
        $template = QuestionnaireTemplate::factory()->create();
        $this->delete(route('questionnaires.delete', $template))->assertRedirect(route('login'));

        foreach (['editor', 'instructor'] as $role) {
            $this->actingAs(User::factory()->approved()->create(['role_id' => Role::where('name', $role)->sole()->id]));
            $this->delete(route('questionnaires.delete', $template))->assertForbidden();
        }

        $this->assertModelExists($template);
    }
}
