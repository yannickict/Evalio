<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSession;
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
        CourseSession::factory()->for($course)->create(['code' => '012345']);

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
        $this->assertCount(1, $xpath->query('//form[@method="POST" and @action="'.route('questionaire.submit').'"]/input[@name="_token"]'));
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
        CourseSession::factory()->create(['code' => '012345']);
        $this->get(route('questionaire', ['code' => '012345']))->assertNotFound();
    }

    public function test_empty_questionnaire_has_no_submit_button(): void
    {
        $this->questionnaire();
        $this->get(route('questionaire', ['code' => '012345']))->assertOk()
            ->assertSee('No questions available')->assertDontSee('Submit feedback');
    }
}
