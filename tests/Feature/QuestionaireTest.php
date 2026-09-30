<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\FeedbackForm;
use App\Models\Question;
use App\Models\QuestionnaireTemplate;
use App\Models\QuestionOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class QuestionaireTest extends TestCase
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
        FeedbackForm::factory()->for(CourseSession::factory()->for($course), 'courseSession')->create(['code' => '012345']);

        return $template;
    }

    public function test_guests_see_only_the_selected_templates_questions_in_position_order(): void
    {
        $template = $this->questionnaire();
        $last = Question::factory()->create(['question_text' => 'Last question']);
        $first = Question::factory()->create(['question_text' => 'First question']);
        $unrelated = Question::factory()->create(['question_text' => 'Unrelated question']);
        $template->questions()->attach([$last->id => ['position' => 2], $first->id => ['position' => 1]]);

        $this->get(route('questionaire', ['code' => '012345']))->assertOk()->assertViewIs('questionaire')
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
        ]])->get(route('questionaire', ['code' => '012345']))->assertOk();
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
        $this->assertCount(2, $xpath->query('//input[@type="radio" and @required]'));
        $this->assertCount(1, $xpath->query('//textarea[@name="answers['.$text->id.']" and @required]'));
        $this->assertCount(0, $xpath->query('//textarea[starts-with(@name, "answers_comment[") and @required]'));
        $this->assertCount(1, $xpath->query('//form[@id="questionnaire-form"]//*[@id="incomplete-answers" and @role="alert" and @hidden]'));
        $this->assertCount(1, $xpath->query('//form[@method="POST" and @action="'.route('questionaire.submit', ['code' => '012345']).'"]/input[@name="_token"]'));
    }

    public function test_submission_saves_answers_without_comments_and_shows_confirmation_once(): void
    {
        $template = $this->questionnaire();
        $form = FeedbackForm::where('code', '012345')->firstOrFail();
        $choice = Question::factory()->create();
        $text = Question::factory()->create(['type' => 'free_text']);
        $option = QuestionOption::factory()->for($choice)->create();
        $template->questions()->attach([$choice->id => ['position' => 1], $text->id => ['position' => 2]]);

        $this->post(route('questionaire.submit', ['code' => '012345']), [
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

    public function test_submissions_use_the_selected_form_when_a_session_has_multiple_codes(): void
    {
        $template = $this->questionnaire();
        $first = FeedbackForm::where('code', '012345')->firstOrFail();
        $second = FeedbackForm::factory()->create(['course_session_id' => $first->course_session_id, 'code' => '654321']);
        $question = Question::factory()->create(['type' => 'free_text']);
        $template->questions()->attach($question, ['position' => 1]);

        foreach ([$first, $second] as $form) {
            $this->get(route('questionaire', ['code' => $form->code]))->assertOk()->assertSee($question->question_text);
            $this->post(route('questionaire.submit', ['code' => $form->code]), [
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

        $this->post(route('questionaire.submit', ['code' => '999999']), [
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
        ]])->get(route('questionaire', ['code' => '012345']))->assertOk()
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
        $url = route('questionaire.submit', ['code' => '012345']);

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

    public static function invalidCodes(): array
    {
        return ['missing' => [[]], 'short' => [['code' => '123']], 'letters' => [['code' => 'abcdef']], 'array' => [['code' => ['123456']]]];
    }

    #[DataProvider('invalidCodes')]
    public function test_invalid_codes_return_validation_errors(array $query): void
    {
        $this->from(route('home'))->get(route('questionaire', $query))
            ->assertRedirect(route('home'))->assertSessionHasErrors('code');
    }

    public function test_unknown_code_and_missing_template_return_not_found(): void
    {
        $this->get(route('questionaire', ['code' => '999999']))->assertNotFound();
        FeedbackForm::factory()->create(['code' => '012345']);
        $this->get(route('questionaire', ['code' => '012345']))->assertNotFound();
    }

    public function test_empty_questionnaire_has_no_submit_button(): void
    {
        $this->questionnaire();
        $this->get(route('questionaire', ['code' => '012345']))->assertOk()
            ->assertSee('No questions available')->assertDontSee('Submit feedback')
            ->assertDontSee('id="incomplete-answers"', false);
    }
}
