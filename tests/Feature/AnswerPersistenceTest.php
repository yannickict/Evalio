<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\FeedbackForm;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnswerPersistenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_answers_resolve_their_form_question_and_optional_option(): void
    {
        $form = FeedbackForm::factory()->create();
        $choice = Question::factory()->create();
        $option = QuestionOption::factory()->for($choice)->create();
        $answer = Answer::create([
            'feedback_form_id' => $form->id,
            'question_id' => $choice->id,
            'question_option_id' => $option->id,
        ]);

        $this->assertTrue($answer->feedbackForm->is($form));
        $this->assertTrue($answer->question->is($choice));
        $this->assertTrue($answer->questionOption->is($option));

        $text = Question::factory()->create(['type' => 'free_text']);
        $textAnswer = Answer::create([
            'feedback_form_id' => $form->id,
            'question_id' => $text->id,
            'answer_text' => 'Helpful exercises',
        ]);
        $this->assertNull($textAnswer->fresh()->questionOption);
    }

    public function test_deleting_a_form_removes_only_its_answers_and_preserves_shared_questions(): void
    {
        $first = FeedbackForm::factory()->create();
        $second = FeedbackForm::factory()->create();
        $question = Question::factory()->create();
        $option = QuestionOption::factory()->for($question)->create();
        foreach ([$first, $first, $second] as $form) {
            Answer::create([
                'feedback_form_id' => $form->id,
                'question_id' => $question->id,
                'question_option_id' => $option->id,
            ]);
        }

        $first->delete();

        $this->assertDatabaseCount('answers', 1);
        $this->assertDatabaseHas('answers', ['feedback_form_id' => $second->id]);
        $this->assertModelExists($second);
        $this->assertModelExists($question);
        $this->assertModelExists($option);
    }

    public function test_an_option_with_existing_answers_cannot_be_deleted(): void
    {
        $form = FeedbackForm::factory()->create();
        $option = QuestionOption::factory()->create();
        Answer::create([
            'feedback_form_id' => $form->id,
            'question_id' => $option->question_id,
            'question_option_id' => $option->id,
        ]);

        $this->expectException(QueryException::class);
        $option->delete();
    }
}
