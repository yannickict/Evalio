<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\FeedbackForm;
use App\Models\Question;
use App\Models\QuestionnaireTemplate;
use App\Models\QuestionOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FeedbackResponseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    private function questionnaire(): QuestionnaireTemplate
    {
        $template = QuestionnaireTemplate::factory()->create();
        $course = Course::factory()->for($template, 'questionnaireTemplate')->create();
        FeedbackForm::factory()->for(CourseSession::factory()->for($course)->state(['evaluation_status' => 'open']), 'courseSession')->create(['code' => '012345']);

        return $template;
    }

    public function test_guests_see_only_the_selected_templates_questions_in_position_order(): void
    {
        $template = $this->questionnaire();
        $last = Question::factory()->create(['question_text' => 'Last question']);
        $first = Question::factory()->create(['question_text' => 'First question']);
        $unrelated = Question::factory()->create(['question_text' => 'Unrelated question']);
        $template->questions()->attach([$last->id => ['position' => 2], $first->id => ['position' => 1]]);

        $this->get(route('feedback.show', ['code' => '012345']))->assertOk()->assertViewIs('pages.questionnaires.respond')
            ->assertSeeInOrder([$first->question_text, $last->question_text])->assertDontSee($unrelated->question_text);
    }

    public function test_answers_and_comments_have_unique_ids_linked_labels_and_restore_independently(): void
    {
        $template = $this->questionnaire();
        $choice = Question::factory()->create(['allows_comment' => true]);
        $text = Question::factory()->create(['type' => 'free_text', 'allows_comment' => true]);
        $noComment = Question::factory()->create(['allows_comment' => false]);
        $template->questions()->attach([$choice->id => ['position' => 1], $text->id => ['position' => 2], $noComment->id => ['position' => 3]]);
        $options = QuestionOption::factory()->count(2)->for($choice)->create();
        $response = $this->withSession(['_old_input' => [
            'answers' => [$choice->id => (string) $options[1]->id, $text->id => 'My answer'],
            'answers_comment' => [$text->id => 'My comment'],
        ]])->get(route('feedback.show', ['code' => '012345']))->assertOk();
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);

        $ids = [];
        foreach ($xpath->query('//*[@id]') as $element) {
            if (! $element instanceof \DOMElement) {
                $this->fail('Expected an element with an ID attribute.');
            }
            $ids[] = $element->getAttribute('id');
        }
        $this->assertSame($ids, array_values(array_unique($ids)));
        foreach ($options as $option) {
            $id = 'question-'.$choice->id.'-option-'.$option->id;
            $this->assertCount(1, $xpath->query('//label[@for="'.$id.'"]'));
            $this->assertCount(1, $xpath->query('//input[@id="'.$id.'" and @name="answers['.$choice->id.']" and @value="'.$option->id.'"]'));
        }
        $this->assertCount(1, $xpath->query('//input[@type="radio" and @checked and @value="'.$options[1]->id.'"]'));
        $this->assertSame('My answer', $xpath->query('//textarea[@name="answers['.$text->id.']"]')->item(0)->textContent);
        $this->assertSame('My comment', $xpath->query('//textarea[@name="answers_comment['.$text->id.']"]')->item(0)->textContent);
        $this->assertCount(0, $xpath->query('//textarea[@name="answers_comment['.$noComment->id.']"]'));
        $this->assertCount(0, $xpath->query('//input[@type="radio" and @required]'));
        $this->assertCount(0, $xpath->query('//textarea[@name="answers['.$text->id.']" and @required]'));
        $this->assertCount(0, $xpath->query('//textarea[starts-with(@name, "answers_comment[") and @required]'));
        $this->assertCount(0, $xpath->query('//*[@id="incomplete-answers"]'));
        $this->assertCount(1, $xpath->query('//form[@method="POST" and @action="'.route('feedback.store', ['code' => '012345']).'"]/input[@name="_token"]'));
    }

    public function test_submission_accepts_skipped_questions_and_blank_free_text(): void
    {
        $template = $this->questionnaire();
        $choice = Question::factory()->create();
        $text = Question::factory()->create(['type' => 'free_text']);
        $template->questions()->attach([$choice->id => ['position' => 1], $text->id => ['position' => 2]]);

        // Browsers omit unselected radio groups and send empty textareas.
        $this->post(route('feedback.store', ['code' => '012345']), [
            'answers' => [$text->id => ''],
        ])->assertRedirect(route('home'))->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Thank you! Your feedback has been submitted.');

        $this->assertDatabaseMissing('answers', ['question_id' => $choice->id]);

        $this->post(route('feedback.store', ['code' => '012345']))
            ->assertRedirect(route('home'))->assertSessionHasNoErrors();
    }

    public function test_submission_saves_answers_without_comments_and_shows_confirmation_once(): void
    {
        $template = $this->questionnaire();
        $form = FeedbackForm::where('code', '012345')->firstOrFail();
        $choice = Question::factory()->create();
        $text = Question::factory()->create(['type' => 'free_text']);
        $option = QuestionOption::factory()->for($choice)->create();
        $template->questions()->attach([$choice->id => ['position' => 1], $text->id => ['position' => 2]]);

        $this->post(route('feedback.store', ['code' => '012345']), [
            'answers' => [$choice->id => $option->id, $text->id => 'A useful course'],
        ])->assertRedirect(route('home'))->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Thank you! Your feedback has been submitted.');

        $this->assertDatabaseCount('answers', 2);
        $this->assertDatabaseHas('answers', [
            'feedback_form_id' => $form->id, 'question_id' => $choice->id,
            'question_option_id' => $option->id, 'answer_text' => null, 'comment' => null,
        ]);
        $this->assertDatabaseHas('answers', [
            'feedback_form_id' => $form->id, 'question_id' => $text->id,
            'question_option_id' => null, 'answer_text' => 'A useful course', 'comment' => null,
        ]);
        $this->get(route('home'))->assertOk()->assertSee('Thank you! Your feedback has been submitted.');
        $this->get(route('home'))->assertOk()->assertDontSee('Thank you! Your feedback has been submitted.');
    }

    public function test_submissions_use_the_selected_sessions_form(): void
    {
        $template = $this->questionnaire();
        $first = FeedbackForm::where('code', '012345')->firstOrFail();
        $secondSession = CourseSession::factory()->create(['course_id' => $first->courseSession->course_id, 'evaluation_status' => 'open']);
        $second = FeedbackForm::factory()->for($secondSession, 'courseSession')->create(['code' => '654321']);
        $question = Question::factory()->create(['type' => 'free_text']);
        $template->questions()->attach($question, ['position' => 1]);

        foreach ([$first, $second] as $form) {
            $this->get(route('feedback.show', ['code' => $form->code]))->assertOk()->assertSee($question->question_text);
            $this->post(route('feedback.store', ['code' => $form->code]), [
                'answers' => [$question->id => 'Response for '.$form->code],
            ])->assertRedirect(route('home'))->assertSessionHasNoErrors();
            $this->assertDatabaseHas('answers', [
                'feedback_form_id' => $form->id, 'question_id' => $question->id,
                'answer_text' => 'Response for '.$form->code,
            ]);
        }
        $this->assertDatabaseCount('answers', 2);
    }

    public function test_unknown_submission_code_does_not_save_answers(): void
    {
        $template = $this->questionnaire();
        $question = Question::factory()->create(['type' => 'free_text']);
        $template->questions()->attach($question, ['position' => 1]);

        $this->post(route('feedback.store', ['code' => '999999']), [
            'answers' => [$question->id => 'An answer'],
        ])->assertNotFound();
        $this->assertDatabaseCount('answers', 0);
    }

    public function test_question_option_and_restored_answer_content_is_escaped(): void
    {
        $template = $this->questionnaire();
        $unsafe = '<script>alert("question")</script>';
        $choice = Question::factory()->create(['question_text' => $unsafe]);
        $text = Question::factory()->create(['type' => 'free_text']);
        QuestionOption::factory()->for($choice)->create(['option_text' => '<b>Option</b>']);
        $template->questions()->attach([$choice->id => ['position' => 1], $text->id => ['position' => 2]]);
        $answer = '</textarea><script>alert("answer")</script>';

        $this->withSession(['_old_input' => [
            'answers' => [$text->id => $answer],
            'answers_comment' => [$choice->id => $answer],
        ]])->get(route('feedback.show', ['code' => '012345']))->assertOk()
            ->assertSee($unsafe)->assertDontSee($unsafe, false)
            ->assertSee('<b>Option</b>')->assertDontSee('<b>Option</b>', false)
            ->assertSee($answer)->assertDontSee($answer, false);
    }

    public function test_multiple_submissions_preserve_all_answers_for_the_same_form_and_questions(): void
    {
        $template = $this->questionnaire();
        $form = FeedbackForm::where('code', '012345')->firstOrFail();
        $choice = Question::factory()->create(['type' => 'single_choice', 'allows_comment' => true]);
        $text = Question::factory()->create(['type' => 'free_text', 'allows_comment' => true]);
        $template->questions()->attach([$choice->id => ['position' => 1], $text->id => ['position' => 2]]);
        $options = QuestionOption::factory()->count(2)->for($choice)->create();
        $url = route('feedback.store', ['code' => '012345']);

        $this->post($url, [
            'answers' => [$choice->id => $options[0]->id, $text->id => 'Original answer'],
            'answers_comment' => [$choice->id => 'Original comment', $text->id => 'Text comment'],
        ])->assertRedirect(route('home'))->assertSessionHasNoErrors();
        $this->assertDatabaseCount('answers', 2);

        $updated = [
            'answers' => [$choice->id => $options[1]->id, $text->id => 'Updated answer'],
            'answers_comment' => [$choice->id => 'Updated comment'],
        ];
        foreach ([$updated, $updated] as $index => $payload) {
            $this->post($url, $payload)->assertRedirect(route('home'))->assertSessionHasNoErrors();
            $this->assertDatabaseCount('answers', 4 + $index * 2);
        }

        $this->assertDatabaseHas('answers', [
            'feedback_form_id' => $form->id,
            'question_id' => $choice->id,
            'question_option_id' => $options[0]->id,
            'comment' => 'Original comment',
        ]);
        $this->assertDatabaseHas('answers', [
            'feedback_form_id' => $form->id,
            'question_id' => $text->id,
            'answer_text' => 'Original answer',
            'comment' => 'Text comment',
        ]);
        $this->assertDatabaseHas('answers', [
            'feedback_form_id' => $form->id,
            'question_id' => $choice->id,
            'question_option_id' => $options[1]->id,
            'answer_text' => null,
            'comment' => 'Updated comment',
        ]);
        $this->assertDatabaseHas('answers', [
            'feedback_form_id' => $form->id,
            'question_id' => $text->id,
            'question_option_id' => null,
            'answer_text' => 'Updated answer',
            'comment' => null,
        ]);
    }

    public function test_submission_rejects_unrelated_questions_without_saving_partial_answers(): void
    {
        $template = $this->questionnaire();
        $assigned = Question::factory()->create(['type' => 'free_text']);
        $unrelated = Question::factory()->create(['type' => 'free_text']);
        $template->questions()->attach($assigned, ['position' => 1]);

        $this->from(route('feedback.show', ['code' => '012345']))
            ->post(route('feedback.store', ['code' => '012345']), [
                'answers' => [$assigned->id => 'Valid answer', $unrelated->id => 'Unrelated answer'],
            ])->assertRedirect(route('feedback.show', ['code' => '012345']))
            ->assertSessionHasErrors('answers.'.$unrelated->id);

        $this->assertDatabaseCount('answers', 0);
    }

    public function test_submission_rejects_an_option_from_another_question(): void
    {
        $template = $this->questionnaire();
        $question = Question::factory()->create();
        $template->questions()->attach($question, ['position' => 1]);
        QuestionOption::factory()->for($question)->create();
        $unrelatedOption = QuestionOption::factory()->create();

        $this->post(route('feedback.store', ['code' => '012345']), [
            'answers' => [$question->id => $unrelatedOption->id],
        ])->assertSessionHasErrors('answers.'.$question->id);

        $this->assertDatabaseCount('answers', 0);
    }

    public function test_submission_rejects_unrelated_or_disabled_comments(): void
    {
        $template = $this->questionnaire();
        $question = Question::factory()->create(['type' => 'free_text', 'allows_comment' => false]);
        $unrelated = Question::factory()->create(['allows_comment' => true]);
        $template->questions()->attach($question, ['position' => 1]);

        foreach ([$question, $unrelated] as $commentedQuestion) {
            $this->post(route('feedback.store', ['code' => '012345']), [
                'answers' => [$question->id => 'An answer'],
                'answers_comment' => [$commentedQuestion->id => 'A comment'],
            ])->assertSessionHasErrors('answers_comment.'.$commentedQuestion->id);
        }

        $this->assertDatabaseCount('answers', 0);
    }

    public function test_submission_rejects_malformed_answer_payloads(): void
    {
        $template = $this->questionnaire();
        $question = Question::factory()->create(['type' => 'free_text']);
        $template->questions()->attach($question, ['position' => 1]);

        foreach ([
            ['answers' => 'invalid'],
            ['answers' => [$question->id => ['nested']]],
            ['answers_comment' => 'invalid'],
        ] as $payload) {
            $this->post(route('feedback.store', ['code' => '012345']), $payload)->assertSessionHasErrors();
        }

        $this->assertDatabaseCount('answers', 0);
    }

    public function test_submission_validates_query_code_and_missing_template(): void
    {
        $this->post(route('feedback.store', ['code' => 'invalid']))->assertSessionHasErrors('code');
        FeedbackForm::factory()->for(CourseSession::factory()->state(['evaluation_status' => 'open']), 'courseSession')->create(['code' => '012345']);
        $this->post(route('feedback.store', ['code' => '012345']))->assertNotFound();
        $this->assertDatabaseCount('answers', 0);
    }

    public function test_a_failed_answer_write_rolls_back_the_entire_submission(): void
    {
        $template = $this->questionnaire();
        $questions = Question::factory()->count(2)->create(['type' => 'free_text']);
        $template->questions()->attach([
            $questions[0]->id => ['position' => 1],
            $questions[1]->id => ['position' => 2],
        ]);
        $writes = 0;
        Answer::creating(function () use (&$writes): void {
            if (++$writes === 2) {
                throw new \RuntimeException('Answer write failed.');
            }
        });
        $this->withoutExceptionHandling();

        try {
            $this->post(route('feedback.store', ['code' => '012345']), [
                'answers' => [$questions[0]->id => 'First answer', $questions[1]->id => 'Second answer'],
            ]);
            $this->fail('Expected the second answer write to fail.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Answer write failed.', $exception->getMessage());
        } finally {
            Answer::flushEventListeners();
        }

        $this->assertSame(2, $writes);
        $this->assertDatabaseCount('answers', 0);
    }

    public static function invalidCodes(): array
    {
        return ['missing' => [[]], 'short' => [['code' => '123']], 'letters' => [['code' => 'abcdef']], 'array' => [['code' => ['123456']]]];
    }

    #[DataProvider('invalidCodes')]
    public function test_invalid_codes_return_validation_errors(array $query): void
    {
        $this->from(route('home'))->get(route('feedback.show', $query))
            ->assertRedirect(route('home'))->assertSessionHasErrors('code');
    }

    public function test_unknown_code_and_missing_template_return_not_found(): void
    {
        $this->get(route('feedback.show', ['code' => '999999']))->assertNotFound();
        FeedbackForm::factory()->for(CourseSession::factory()->state(['evaluation_status' => 'open']), 'courseSession')->create(['code' => '012345']);
        $this->get(route('feedback.show', ['code' => '012345']))->assertNotFound();
    }

    public function test_empty_questionnaire_has_no_submit_button(): void
    {
        $this->questionnaire();
        $this->get(route('feedback.show', ['code' => '012345']))->assertOk()
            ->assertSee('No questions available')->assertDontSee('Submit feedback')
            ->assertDontSee('id="incomplete-answers"', false);
    }
}
