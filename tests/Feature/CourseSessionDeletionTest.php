<?php

namespace Tests\Feature;

use App\Models\CourseSession;
use App\Models\Answer;
use App\Models\FeedbackForm;
use App\Models\QuestionOption;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseSessionDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_a_session_without_a_feedback_form(): void
    {
        $session = CourseSession::factory()->create();
        $admin = User::factory()->approved()->create(['role_id' => Role::where('name', 'admin')->sole()->id]);

        $this->actingAs($admin)->delete(route('sessions.delete', $session))
            ->assertRedirect(route('sessions.index'))->assertSessionHasNoErrors();

        $this->assertModelMissing($session);
    }

    public function test_deletion_removes_its_feedback_and_answers_only(): void
    {
        $form = FeedbackForm::factory()->create();
        $session = CourseSession::findOrFail($form->course_session_id);
        $otherForm = FeedbackForm::factory()->create();
        $option = QuestionOption::factory()->create();
        $answers = [];
        foreach ([$form, $otherForm] as $answerForm) {
            $answers[] = Answer::create([
                'feedback_form_id' => $answerForm->id,
                'question_id' => $option->question_id,
                'question_option_id' => $option->id,
            ]);
        }
        $admin = User::factory()->approved()->create(['role_id' => Role::where('name', 'admin')->sole()->id]);

        $this->actingAs($admin)->delete(route('sessions.delete', $session))
            ->assertRedirect(route('sessions.index'))->assertSessionHasNoErrors();

        $this->assertModelMissing($session);
        $this->assertModelMissing($form);
        $this->assertModelMissing($answers[0]);
        $this->assertModelExists($otherForm);
        $this->assertModelExists($otherForm->courseSession);
        $this->assertModelExists($answers[1]);
        $this->assertModelExists($option);
        $this->assertModelExists($option->question);
        $this->assertModelExists($session->course);
    }

    public function test_editor_cannot_delete_a_session(): void
    {
        $session = CourseSession::factory()->create();
        $editor = User::factory()->approved()->create(['role_id' => Role::where('name', 'editor')->sole()->id]);

        $this->actingAs($editor)->delete(route('sessions.delete', $session))->assertForbidden();

        $this->assertModelExists($session);
    }
}
