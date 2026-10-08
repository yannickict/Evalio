<?php

namespace Tests\Feature;

use App\Models\QuestionnaireTemplate;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionnaireTemplateCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_save_templates(): void
    {
        $this->post(route('questionnaires.store'), [])->assertRedirect(route('login'));
        $this->assertDatabaseCount('questionnaire_templates', 0);
    }

    public function test_saving_persists_ordered_questions_and_only_single_choice_options(): void
    {
        $this->withoutVite();
        $this->actingAs(User::factory()->approved()->create(['role_id' => Role::where('name', 'editor')->sole()->id]));
        $this->post(route('questionnaires.store'), [
            'name' => 'Course feedback',
            'questions' => [
                ['text' => 'Your rating?', 'type' => 'single_choice', 'options' => ['Good', 'Bad'], 'allows_comment' => '1'],
                ['text' => 'Any suggestions?', 'type' => 'free_text', 'options' => ['', '']],
            ],
        ])->assertRedirect(route('questionnaires.index'))->assertSessionHas('status', 'Questionnaire saved.');

        $template = QuestionnaireTemplate::with('questions.options')->sole();
        $this->assertSame('Course feedback', $template->name);
        $this->assertSame(['Your rating?', 'Any suggestions?'], $template->questions->pluck('question_text')->all());
        $this->assertSame([1, 2], $template->questions->pluck('pivot.position')->all());
        $this->assertSame(['Good', 'Bad'], $template->questions[0]->options->pluck('option_text')->all());
        $this->assertCount(0, $template->questions[1]->options);
        $this->assertTrue($template->questions[0]->allows_comment);
        $this->assertFalse($template->questions[1]->allows_comment);
        $this->get(route('questionnaires.index'))->assertOk()->assertSee('Course feedback')->assertSee('2 questions');
    }

    public function test_invalid_templates_do_not_write_partial_records_and_preserve_input(): void
    {
        $this->actingAs(User::factory()->approved()->create(['role_id' => Role::where('name', 'editor')->sole()->id]));
        foreach ([
            ['name' => '', 'questions' => [['text' => 'Question', 'type' => 'free_text']]],
            ['name' => 'Draft', 'questions' => []],
            ['name' => 'Draft', 'questions' => [['text' => '', 'type' => 'free_text']]],
            ['name' => 'Draft', 'questions' => [['text' => 'Question', 'type' => 'unknown']]],
            ['name' => 'Draft', 'questions' => [['text' => 'Question', 'type' => 'single_choice', 'options' => ['Only one']]]],
            ['name' => 'Draft', 'questions' => [
                ['text' => 'Valid', 'type' => 'free_text'],
                ['text' => 'Invalid', 'type' => 'single_choice', 'options' => ['Yes', '']],
            ]],
        ] as $draft) {
            $this->from(route('questionnaires.create'))->post(route('questionnaires.store'), $draft)
                ->assertRedirect(route('questionnaires.create'))->assertSessionHasErrors()
                ->assertSessionHas('_old_input', fn ($input) => array_key_exists('name', $input)
                    && $input['name'] === ($draft['name'] === '' ? null : $draft['name']));
            $this->assertDatabaseCount('questionnaire_templates', 0);
            $this->assertDatabaseCount('questions', 0);
            $this->assertDatabaseCount('question_options', 0);
        }
    }

    public function test_duplicate_names_are_rejected_including_surrounding_whitespace(): void
    {
        QuestionnaireTemplate::factory()->create(['name' => 'Course feedback']);
        $this->actingAs(User::factory()->approved()->create(['role_id' => Role::where('name', 'editor')->sole()->id]));

        foreach (['Course feedback', '  Course feedback  '] as $name) {
            $this->from(route('questionnaires.create'))->post(route('questionnaires.store'), [
                'name' => $name,
                'questions' => [['text' => 'Any suggestions?', 'type' => 'free_text']],
            ])->assertRedirect(route('questionnaires.create'))->assertSessionHasErrors('name');
        }

        $this->assertDatabaseCount('questionnaire_templates', 1);
        $this->assertDatabaseCount('questions', 0);
    }

    public function test_database_rejects_duplicate_names(): void
    {
        QuestionnaireTemplate::factory()->create(['name' => 'Course feedback']);

        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        QuestionnaireTemplate::factory()->create(['name' => 'Course feedback']);
    }
}
