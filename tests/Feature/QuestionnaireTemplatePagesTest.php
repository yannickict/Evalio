<?php

namespace Tests\Feature;

use App\Models\CourseSession;
use App\Models\FeedbackForm;
use App\Models\QuestionnaireTemplate;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionnaireTemplatePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_questionnaire_usage_only_loads_an_instructors_own_sessions(): void
    {
        $this->withoutVite();
        $owner = User::factory()->approved()->create();
        $template = QuestionnaireTemplate::factory()->create();
        $otherTemplate = QuestionnaireTemplate::factory()->create();
        $own = CourseSession::factory()->for($owner, 'instructor')->create(['questionnaire_template_id' => $template->id]);
        $other = CourseSession::factory()->create(['questionnaire_template_id' => $template->id]);
        $otherOnly = CourseSession::factory()->create(['questionnaire_template_id' => $otherTemplate->id]);
        FeedbackForm::factory()->create(['course_session_id' => $other->id]);

        $response = $this->actingAs($owner)->get(route('questionnaires.index'))->assertOk();
        $response->assertSee($own->session_identifier)
            ->assertSee(route('sessions.index', ['session' => $own->id]), false)
            ->assertDontSee($other->session_identifier)
            ->assertDontSee($otherOnly->session_identifier)
            ->assertDontSee(route('sessions.index', ['session' => $other->id]), false)
            ->assertSee('No sessions are assigned to you for this questionnaire.')
            ->assertViewHas('templates', fn ($templates) => $templates->firstWhere('id', $template->id)->sessions->modelKeys() === [$own->id]
                && $templates->firstWhere('id', $otherTemplate->id)->sessions->isEmpty());

        foreach (['admin', 'editor'] as $role) {
            $viewer = User::factory()->approved()->create(['role_id' => Role::where('name', $role)->sole()->id]);
            $this->actingAs($viewer)->get(route('questionnaires.index'))->assertOk()
                ->assertSee($own->session_identifier)
                ->assertSee($other->session_identifier)
                ->assertSee($otherOnly->session_identifier)
                ->assertViewHas('templates', fn ($templates) => $templates->firstWhere('id', $template->id)->sessions->count() === 2
                    && $templates->firstWhere('id', $template->id)->sessions->firstWhere('id', $other->id)->feedbackForm !== null);
        }
    }

    public function test_user_without_a_role_cannot_list_questionnaires(): void
    {
        $user = User::factory()->approved()->create();
        $user->setRelation('role', null);

        $this->actingAs($user)->get(route('questionnaires.index'))->assertForbidden();
    }

    public function test_questionnaire_pages_require_authentication(): void
    {
        $this->get(route('questionnaires.index'))->assertRedirect(route('login'));
        $this->get(route('questionnaires.create'))->assertRedirect(route('login'));
        $this->post(route('questionnaires.preview'), [])->assertRedirect(route('login'));
    }

    public function test_signed_in_user_can_navigate_library_and_editor_preview(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->approved()->create(['role_id' => Role::where('name', 'editor')->sole()->id]));
        $this->get(route('questionnaires.index'))->assertOk()
            ->assertSee(route('questionnaires.create'), false)->assertSee('Questionnaire library');
        $this->get(route('questionnaires.create'))->assertOk()
            ->assertSee(route('questionnaires.preview'), false)
            ->assertSee('Questionnaire name')->assertSee('Add question')->assertSee('Add option');
    }

    public function test_adding_an_option_only_changes_the_selected_question_and_preserves_input(): void
    {
        $this->withoutVite();
        $questions = [
            ['text' => 'First question', 'type' => 'single_choice', 'options' => ['Yes', 'No'], 'allows_comment' => true],
            ['text' => 'Second question', 'type' => 'single_choice', 'options' => ['Good', 'Bad'], 'allows_comment' => false],
        ];
        $expected = $questions;
        $expected[1]['options'][] = '';

        $this->actingAs(User::factory()->approved()->create(['role_id' => Role::where('name', 'editor')->sole()->id]))
            ->post(route('questionnaires.preview'), [
                'name' => 'Course feedback', 'questions' => $questions, 'action' => 'add_option:1',
            ])->assertOk()->assertViewHas('draft', [
                'name' => 'Course feedback', 'questions' => $expected,
            ])->assertSee('name="questions[1][options][2]"', false);
    }

    public function test_adding_questions_and_refreshing_types_preserve_the_draft_without_saving(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->approved()->create(['role_id' => Role::where('name', 'editor')->sole()->id]));
        $question = ['text' => '<script>alert(1)</script>', 'type' => 'free_text', 'options' => ['Yes', 'No'], 'allows_comment' => true];
        $draft = ['name' => 'Draft', 'questions' => [$question]];

        $response = $this->post(route('questionnaires.preview'), $draft + ['action' => 'refresh'])
            ->assertOk()->assertViewHas('draft', $draft)
            ->assertSee('id="question-0-preview"', false)
            ->assertSee('type="hidden"', false)
            ->assertSee($question['text'])->assertDontSee($question['text'], false);
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);
        $this->assertCount(1, $xpath->query('//button[@value="remove_question:0" and @disabled]'));
        $this->assertCount(1, $xpath->query('//textarea[@id="question-0-preview" and @disabled]'));

        $this->post(route('questionnaires.preview'), $draft + ['action' => 'add_question'])
            ->assertOk()->assertViewHas('draft', [
                'name' => 'Draft', 'questions' => [$question, ['text' => '', 'type' => 'single_choice', 'options' => ['', ''], 'allows_comment' => false]],
            ]);
        $this->assertDatabaseCount('questionnaire_templates', 0);
        $this->assertDatabaseCount('questions', 0);
    }

    public function test_adding_options_rejects_invalid_questions_types_and_option_limits(): void
    {
        $this->actingAs(User::factory()->approved()->create(['role_id' => Role::where('name', 'editor')->sole()->id]));

        foreach ([
            ['type' => 'single_choice', 'options' => ['', ''], 'action' => 'add_option:99'],
            ['type' => 'free_text', 'options' => ['', ''], 'action' => 'add_option:0'],
            ['type' => 'single_choice', 'options' => array_fill(0, 20, ''), 'action' => 'add_option:0'],
        ] as $case) {
            $this->from(route('questionnaires.create'))->post(route('questionnaires.preview'), [
                'name' => 'Draft',
                'questions' => [['text' => '', 'type' => $case['type'], 'options' => $case['options']]],
                'action' => $case['action'],
            ])->assertRedirect(route('questionnaires.create'))->assertSessionHasErrors('questions');
        }
    }

    public function test_removing_questions_and_options_preserves_and_reindexes_the_remaining_draft(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->approved()->create(['role_id' => Role::where('name', 'editor')->sole()->id]));
        $questions = [
            ['text' => 'First', 'type' => 'single_choice', 'options' => ['Yes', 'Maybe', 'No'], 'allows_comment' => true],
            ['text' => 'Second', 'type' => 'free_text', 'options' => ['', ''], 'allows_comment' => false],
            ['text' => 'Third', 'type' => 'single_choice', 'options' => ['Good', 'Bad'], 'allows_comment' => false],
        ];

        $this->post(route('questionnaires.preview'), [
            'name' => 'Draft', 'questions' => $questions, 'action' => 'remove_question:1',
        ])->assertOk()->assertViewHas('draft', [
            'name' => 'Draft', 'questions' => [$questions[0], $questions[2]],
        ]);

        $expected = $questions;
        $expected[0]['options'] = ['Yes', 'No'];
        $this->post(route('questionnaires.preview'), [
            'name' => 'Draft', 'questions' => $questions, 'action' => 'remove_option:0:1',
        ])->assertOk()->assertViewHas('draft', ['name' => 'Draft', 'questions' => $expected]);
    }

    public function test_removal_rejects_invalid_targets_and_preserves_minimum_questions_and_options(): void
    {
        $this->actingAs(User::factory()->approved()->create(['role_id' => Role::where('name', 'editor')->sole()->id]));
        foreach ([
            ['single_choice', ['Yes', 'No'], 'remove_question:0'],
            ['single_choice', ['Yes', 'Maybe', 'No'], 'remove_question:99'],
            ['single_choice', ['Yes', 'No'], 'remove_option:0:0'],
            ['single_choice', ['Yes', 'Maybe', 'No'], 'remove_option:0:99'],
            ['single_choice', ['Yes', 'Maybe', 'No'], 'remove_option:99:0'],
            ['free_text', ['Yes', 'Maybe', 'No'], 'remove_option:0:0'],
        ] as [$type, $options, $action]) {
            $this->from(route('questionnaires.create'))->post(route('questionnaires.preview'), [
                'name' => 'Draft',
                'questions' => [['text' => '', 'type' => $type, 'options' => $options]],
                'action' => $action,
            ])->assertRedirect(route('questionnaires.create'))->assertSessionHasErrors('questions');
        }
    }
}
