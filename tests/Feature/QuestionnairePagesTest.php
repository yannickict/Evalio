<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionnairePagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_questionnaire_pages_require_authentication(): void
    {
        $this->get(route('questionnaires.index'))->assertRedirect(route('login'));
        $this->get(route('questionnaires.create'))->assertRedirect(route('login'));
        $this->post(route('questionnaires.preview'), [])->assertRedirect(route('login'));
    }

    public function test_signed_in_user_can_navigate_library_and_editor_preview(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->approved()->create());
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
            ['text' => 'First question', 'type' => 'single_choice', 'options' => ['Yes', 'No']],
            ['text' => 'Second question', 'type' => 'single_choice', 'options' => ['Good', 'Bad']],
        ];
        $expected = $questions;
        $expected[1]['options'][] = '';

        $this->actingAs(User::factory()->approved()->create())
            ->post(route('questionnaires.preview'), [
                'name' => 'Course feedback', 'questions' => $questions, 'action' => 'add_option:1',
            ])->assertOk()->assertViewHas('draft', [
                'name' => 'Course feedback', 'questions' => $expected,
            ])->assertSee('name="questions[1][options][2]"', false);
    }

    public function test_adding_questions_and_refreshing_types_preserve_the_draft_without_saving(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->approved()->create());
        $question = ['text' => '<script>alert(1)</script>', 'type' => 'free_text', 'options' => ['Yes', 'No']];
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
                'name' => 'Draft', 'questions' => [$question, ['text' => '', 'type' => 'single_choice', 'options' => ['', '']]],
            ]);
        $this->assertDatabaseCount('questionnaire_templates', 0);
        $this->assertDatabaseCount('questions', 0);
    }

    public function test_adding_options_rejects_invalid_questions_types_and_option_limits(): void
    {
        $this->actingAs(User::factory()->approved()->create());

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
        $this->actingAs(User::factory()->approved()->create());
        $questions = [
            ['text' => 'First', 'type' => 'single_choice', 'options' => ['Yes', 'Maybe', 'No']],
            ['text' => 'Second', 'type' => 'free_text', 'options' => ['', '']],
            ['text' => 'Third', 'type' => 'single_choice', 'options' => ['Good', 'Bad']],
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
        $this->actingAs(User::factory()->approved()->create());
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
