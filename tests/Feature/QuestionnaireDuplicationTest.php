<?php

namespace Tests\Feature;

use App\Models\Question;
use App\Models\QuestionnaireTemplate;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionnaireDuplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_opens_a_prefilled_editor_without_writing_and_saves_an_independent_copy(): void
    {
        $this->withoutVite();
        $template = QuestionnaireTemplate::factory()->create(['name' => 'Original']);
        $question = Question::factory()->create(['type' => 'single_choice', 'allows_comment' => true]);
        $question->options()->createMany([['option_text' => 'Yes'], ['option_text' => 'No']]);
        $template->questions()->attach($question->id, ['position' => 1]);
        $text = Question::factory()->create(['type' => 'free_text', 'allows_comment' => false]);
        $template->questions()->attach($text->id, ['position' => 2]);
        $this->actingAs(User::factory()->approved()->create(['role_id' => Role::where('name', 'editor')->sole()->id]));

        $response = $this->get(route('questionnaires.duplicate', $template))
            ->assertOk()->assertViewIs('pages.questionnaires.create')->assertSee('Original (copy)');
        $draft = $response->viewData('draft');
        $this->assertSame($question->question_text, $draft['questions'][0]['text']);
        $this->assertSame(['Yes', 'No'], $draft['questions'][0]['options']);
        $this->assertTrue($draft['questions'][0]['allows_comment']);
        $this->assertSame('free_text', $draft['questions'][1]['type']);
        $this->assertDatabaseCount('questionnaire_templates', 1);
        $this->assertDatabaseCount('questions', 2);

        $this->post(route('questionnaires.store'), $draft)->assertRedirect(route('questionnaires.index'))->assertSessionHasNoErrors();
        $copy = QuestionnaireTemplate::where('name', 'Original (copy)')->sole();
        $this->assertCount(2, $copy->questions);
        $this->assertFalse($copy->questions->contains('id', $question->id));
        $this->assertFalse($copy->questions->contains('id', $text->id));
        $this->assertSame('Original', $template->fresh()->name);
    }

    public function test_guests_and_instructors_cannot_duplicate(): void
    {
        $template = QuestionnaireTemplate::factory()->create();
        $this->get(route('questionnaires.duplicate', $template))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->approved()->create());
        $this->get(route('questionnaires.duplicate', $template))->assertForbidden();
        $this->assertDatabaseCount('questionnaire_templates', 1);
    }
}
