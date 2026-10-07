<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\CourseSession;
use App\Models\FeedbackForm;
use App\Models\Question;
use App\Models\QuestionnaireTemplate;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EvaluationResultsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_users_can_open_results_and_see_the_link(): void
    {
        $this->withoutVite();
        $session = CourseSession::factory()->create();
        foreach (['admin', 'editor', 'instructor'] as $role) {
            $user = $role === 'instructor' ? $session->instructor : User::factory()->approved()->create([
                'role_id' => Role::where('name', $role)->sole()->id,
            ]);
            $this->actingAs($user);
            $this->get(route('sessions.results', $session))->assertOk()
                ->assertSee('Evaluation results')->assertSee($session->session_identifier);
            $this->get(route('sessions.index'))->assertOk()->assertSee(route('sessions.results', $session), false);
        }
    }

    public function test_guests_and_other_instructors_cannot_open_results(): void
    {
        $session = CourseSession::factory()->create();
        $this->get(route('sessions.results', $session))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->approved()->create());
        $this->get(route('sessions.results', $session))->assertForbidden();
    }

    public function test_results_show_option_counts_text_and_comments_for_only_the_selected_session(): void
    {
        $this->withoutVite();
        $template = QuestionnaireTemplate::factory()->create();
        $choice = Question::factory()->create(['question_text' => 'Was it helpful?', 'type' => 'single_choice']);
        $yes = $choice->options()->create(['option_text' => 'Yes']);
        $choice->options()->create(['option_text' => 'No']);
        $text = Question::factory()->create(['question_text' => 'Your thoughts?', 'type' => 'free_text']);
        $template->questions()->attach($choice->id, ['position' => 1]);
        $template->questions()->attach($text->id, ['position' => 2]);
        $session = CourseSession::factory()->create(['questionnaire_template_id' => $template->id]);
        $form = FeedbackForm::factory()->for($session)->create();
        foreach (range(1, 2) as $number) {
            Answer::create(['feedback_form_id' => $form->id, 'question_id' => $choice->id, 'question_option_id' => $yes->id, 'comment' => $number === 1 ? '<script>unsafe</script>' : null]);
        }
        Answer::create(['feedback_form_id' => $form->id, 'question_id' => $text->id, 'answer_text' => 'Useful examples']);
        $otherForm = FeedbackForm::factory()->create();
        Answer::create(['feedback_form_id' => $otherForm->id, 'question_id' => $text->id, 'answer_text' => 'Other session feedback']);
        $this->actingAs($session->instructor);

        $response = $this->get(route('sessions.results', $session))->assertOk()
            ->assertSee('Was it helpful?')->assertSee('Useful examples')
            ->assertSee('Print / Save as PDF')->assertSee('(100%)')
            ->assertSee('<circle', false)->assertSee('results-grid')
            ->assertSee('&lt;script&gt;unsafe&lt;/script&gt;', false)
            ->assertDontSee('<script>unsafe</script>', false)->assertDontSee('Other session feedback');
        $this->assertSame([2, 0], $response->viewData('results')->first()['options']->pluck('count')->all());
    }
}
