<?php

namespace Tests\Feature;

use App\Actions\SetEvaluationStatus;
use App\Models\Answer;
use App\Models\CourseSession;
use App\Models\FeedbackForm;
use App\Models\Question;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EvaluationStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_opening_creates_a_form_and_repeated_opening_preserves_its_code(): void
    {
        $session = CourseSession::factory()->create();
        $action = app(SetEvaluationStatus::class);

        $this->assertTrue($action->handle($session, 'open'));
        $form = $session->feedbackForm()->sole();
        $this->assertMatchesRegularExpression('/^\d{6}$/', $form->code);
        $this->assertSame('open', $session->fresh()->evaluation_status);
        $this->assertFalse($action->handle($session, 'open'));
        $this->assertSame($form->code, $form->fresh()->code);
        $this->assertDatabaseCount('feedback_forms', 1);
    }

    public function test_closing_and_reopening_preserve_the_form_and_collected_answers(): void
    {
        $session = CourseSession::factory()->create(['evaluation_status' => 'open']);
        $form = FeedbackForm::factory()->for($session, 'courseSession')->create(['code' => '012345']);
        $answer = Answer::create([
            'feedback_form_id' => $form->id,
            'question_id' => Question::factory()->create(['type' => 'free_text'])->id,
            'answer_text' => 'Keep this feedback',
        ]);
        $action = app(SetEvaluationStatus::class);

        $this->assertTrue($action->handle($session, 'closed'));
        $this->assertNull($form->fresh()->code);
        $this->assertSame('closed', $session->fresh()->evaluation_status);
        $this->assertFalse($action->handle($session, 'closed'));
        $this->assertTrue($action->handle($session, 'open'));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $form->fresh()->code);
        $this->assertDatabaseCount('feedback_forms', 1);
        $this->assertModelExists($answer);
        $this->assertSame($form->id, $answer->fresh()->feedback_form_id);
    }

    public function test_code_collisions_retry_without_leaving_partial_changes(): void
    {
        FeedbackForm::factory()->create(['code' => '012345']);
        $session = CourseSession::factory()->create();
        $action = new class extends SetEvaluationStatus
        {
            private int $attempts = 0;

            protected function generateCode(): string
            {
                return ++$this->attempts === 1 ? '012345' : '654321';
            }
        };

        $this->assertTrue($action->handle($session, 'open'));
        $this->assertSame('654321', $session->feedbackForm()->sole()->code);
        $this->assertDatabaseCount('feedback_forms', 2);
    }

    /** @return array<string, array{string}> */
    public static function authorizedRoles(): array
    {
        return ['administrator' => ['admin'], 'editor' => ['editor']];
    }

    #[DataProvider('authorizedRoles')]
    public function test_authorized_users_can_open_and_close_evaluations(string $role): void
    {
        $actor = User::factory()->approved()->create(['role_id' => Role::where('name', $role)->sole()->id]);
        $session = CourseSession::factory()->create();
        $url = route('sessions.evaluation.update', $session);

        $this->actingAs($actor)->patch($url, ['evaluation_status' => 'open'])
            ->assertRedirect(route('sessions.index'))->assertSessionHasNoErrors();
        $this->assertSame('open', $session->fresh()->evaluation_status);
        $form = $session->feedbackForm()->sole();

        $this->patch($url, ['evaluation_status' => 'closed'])
            ->assertRedirect(route('sessions.index'))->assertSessionHasNoErrors();
        $this->assertNull($form->fresh()->code);
    }

    public function test_guests_and_instructors_cannot_change_status(): void
    {
        $session = CourseSession::factory()->create();
        $url = route('sessions.evaluation.update', $session);
        $this->patch($url, ['evaluation_status' => 'open'])->assertRedirect(route('login'));
        $this->actingAs(User::factory()->approved()->create())->patch($url, ['evaluation_status' => 'open'])->assertForbidden();
        $this->assertNull($session->fresh()->evaluation_status);
        $this->assertDatabaseCount('feedback_forms', 0);
    }

    public function test_invalid_status_does_not_change_the_session(): void
    {
        $session = CourseSession::factory()->create();
        $actor = User::factory()->approved()->create(['role_id' => Role::where('name', 'admin')->sole()->id]);
        $this->actingAs($actor)->patch(route('sessions.evaluation.update', $session), ['evaluation_status' => 'invalid'])
            ->assertSessionHasErrors('evaluation_status');
        $this->assertNull($session->fresh()->evaluation_status);
        $this->assertDatabaseCount('feedback_forms', 0);
    }

    public function test_closed_evaluations_reject_access_and_submission_even_with_a_stale_code(): void
    {
        $session = CourseSession::factory()->create(['evaluation_status' => 'closed']);
        FeedbackForm::factory()->for($session, 'courseSession')->create(['code' => '012345']);
        $this->withoutVite();

        $this->get(route('feedback.show', ['code' => '012345']))->assertForbidden();
        $this->post(route('feedback.store', ['code' => '012345']))->assertForbidden();
        $this->assertDatabaseCount('answers', 0);
    }

    public function test_clearing_the_code_invalidates_previously_opened_questionnaire_links(): void
    {
        $session = CourseSession::factory()->create(['evaluation_status' => 'open']);
        FeedbackForm::factory()->for($session, 'courseSession')->create(['code' => '012345']);
        app(SetEvaluationStatus::class)->handle($session, 'closed');

        $this->get(route('feedback.show', ['code' => '012345']))->assertNotFound();
        $this->post(route('feedback.store', ['code' => '012345']))->assertNotFound();
        $this->assertDatabaseCount('answers', 0);
    }
}
