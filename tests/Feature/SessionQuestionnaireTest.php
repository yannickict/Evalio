<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\FeedbackForm;
use App\Models\Question;
use App\Models\QuestionnaireTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionQuestionnaireTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_session_creation_copies_the_course_template_and_ignores_submitted_template_ids(): void
    {
        $template = QuestionnaireTemplate::factory()->create();
        $other = QuestionnaireTemplate::factory()->create();
        $course = Course::factory()->for($template, 'questionnaireTemplate')->create();
        $instructor = User::factory()->approved()->create();

        $this->actingAs($instructor)->post(route('sessions.store'), [
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'start_date' => '2026-10-06',
            'end_date' => '2026-10-08',
            'questionnaire_template_id' => $other->id,
        ])->assertRedirect(route('sessions.index'))->assertSessionHasNoErrors();

        $this->assertSame($template->id, CourseSession::sole()->questionnaire_template_id);
    }

    public function test_course_template_changes_only_affect_new_sessions_and_feedback_uses_the_original(): void
    {
        $original = QuestionnaireTemplate::factory()->create(['name' => 'Original questionnaire']);
        $replacement = QuestionnaireTemplate::factory()->create(['name' => 'Replacement questionnaire']);
        $oldQuestion = Question::factory()->create(['type' => 'free_text', 'question_text' => 'Original question']);
        $newQuestion = Question::factory()->create(['type' => 'free_text', 'question_text' => 'Replacement question']);
        $original->questions()->attach($oldQuestion, ['position' => 1]);
        $replacement->questions()->attach($newQuestion, ['position' => 1]);
        $course = Course::factory()->for($original, 'questionnaireTemplate')->create();
        $instructor = User::factory()->approved()->create();
        $session = CourseSession::factory()->for($course)->for($instructor, 'instructor')->create(['evaluation_status' => 'open']);
        $form = FeedbackForm::factory()->for($session, 'courseSession')->create(['code' => '012345']);

        $course->update(['questionnaire_template_id' => $replacement->id]);
        $newSession = CourseSession::factory()->for($course)->create();
        $this->assertSame($original->id, $session->fresh()->questionnaire_template_id);
        $this->assertSame($replacement->id, $newSession->questionnaire_template_id);

        $this->get(route('feedback.show', ['code' => '012345']))->assertOk()
            ->assertSee($oldQuestion->question_text)->assertDontSee($newQuestion->question_text);
        $this->post(route('feedback.store', ['code' => '012345']), [
            'answers' => [$oldQuestion->id => 'Original answer'],
        ])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertDatabaseHas('answers', [
            'feedback_form_id' => $form->id, 'question_id' => $oldQuestion->id,
        ]);
        $this->post(route('feedback.store', ['code' => '012345']), [
            'answers' => [$newQuestion->id => 'Wrong questionnaire'],
        ])->assertSessionHasErrors('answers.'.$newQuestion->id);
        $this->assertDatabaseCount('answers', 1);

        $this->actingAs($instructor)->get(route('sessions.index'))->assertOk()
            ->assertSee($original->name)->assertDontSee($replacement->name);
    }

    public function test_session_without_a_template_does_not_fall_back_to_the_courses_new_template(): void
    {
        $course = Course::factory()->create(['questionnaire_template_id' => null]);
        $session = CourseSession::factory()->for($course)->create(['evaluation_status' => 'open']);
        FeedbackForm::factory()->for($session, 'courseSession')->create(['code' => '012345']);
        $course->update(['questionnaire_template_id' => QuestionnaireTemplate::factory()->create()->id]);

        $this->assertNull($session->fresh()->questionnaire_template_id);
        $this->get(route('feedback.show', ['code' => '012345']))->assertNotFound();
        $this->post(route('feedback.store', ['code' => '012345']), [])->assertNotFound();
    }
}
