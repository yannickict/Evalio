<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\QuestionnaireTemplate;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CourseCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guests_cannot_create_courses(): void
    {
        $this->post(route('courses.store'), [])->assertRedirect(route('login'));
        $this->assertDatabaseCount('courses', 0);
    }

    public function test_form_lists_sorted_escaped_questionnaires_and_includes_csrf(): void
    {
        $zulu = QuestionnaireTemplate::factory()->create(['name' => 'Zulu']);
        $alpha = QuestionnaireTemplate::factory()->create(['name' => 'Alpha <script>alert(1)</script>']);

        $response = $this->actingAs(User::factory()->approved()->create(['role_id' => Role::where('name', 'editor')->sole()->id]))->get(route('courses.create'))
            ->assertOk()->assertViewHas('templates', fn ($templates) => $templates->modelKeys() === [$alpha->id, $zulu->id])
            ->assertSee($alpha->name)->assertDontSee('<script>alert(1)</script>', false);
        $xpath = $this->xpath($response->getContent());

        $this->assertCount(1, $xpath->query('//form[@method="POST" and @action="'.route('courses.store').'"]/input[@name="_token"]'));
        $this->assertCount(1, $xpath->query('//select[@name="questionnaire_template_id"]/option[@selected and @value=""]'));
        $this->assertCount(0, $xpath->query('//form[@action="'.route('courses.store').'"]//button[@type="submit" and @disabled]'));
    }

    public function test_empty_questionnaire_library_disables_creation_and_links_to_the_editor(): void
    {
        $response = $this->actingAs(User::factory()->approved()->create(['role_id' => Role::where('name', 'editor')->sole()->id]))->get(route('courses.create'))
            ->assertOk()->assertSee('No questionnaires available.')
            ->assertSee(route('questionnaires.create'), false);
        $xpath = $this->xpath($response->getContent());

        $this->assertCount(1, $xpath->query('//select[@name="questionnaire_template_id" and @disabled]'));
        $this->assertCount(1, $xpath->query('//form[@action="'.route('courses.store').'"]//button[@type="submit" and @disabled]'));
    }

    public function test_creation_assigns_the_questionnaire_and_ignores_unvalidated_fields(): void
    {
        $template = QuestionnaireTemplate::factory()->create();
        $this->actingAs(User::factory()->approved()->create(['role_id' => Role::where('name', 'editor')->sole()->id]))->post(route('courses.store'), [
            'name' => 'Web development',
            'questionnaire_template_id' => $template->id,
            'id' => 999999,
            'created_at' => '2000-01-01',
        ])->assertRedirect(route('courses.index'))->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Course created.');

        $course = Course::sole();
        $this->assertSame('Web development', $course->name);
        $this->assertTrue($course->questionnaireTemplate->is($template));
        $this->assertNotSame(999999, $course->id);
        $this->assertNotSame('2000-01-01', $course->created_at->toDateString());
        $this->assertDatabaseCount('course_sessions', 0);
        $this->get(route('courses.index'))->assertOk()->assertSee('Course created.')
            ->assertSee($course->name)->assertSee($template->name);
        $this->get(route('courses.index'))->assertOk()->assertDontSee('Course created.');
    }

    /** @return array<string, array{string, mixed}> */
    public static function invalidFields(): array
    {
        return [
            'missing name' => ['name', null],
            'blank name' => ['name', '   '],
            'long name' => ['name', str_repeat('a', 256)],
            'array name' => ['name', ['invalid']],
            'missing questionnaire' => ['questionnaire_template_id', null],
            'unknown questionnaire' => ['questionnaire_template_id', 999999],
            'invalid questionnaire' => ['questionnaire_template_id', 'invalid'],
            'array questionnaire' => ['questionnaire_template_id', [1]],
        ];
    }

    #[DataProvider('invalidFields')]
    public function test_invalid_input_does_not_create_a_course(string $field, mixed $value): void
    {
        $data = ['name' => 'Course', 'questionnaire_template_id' => QuestionnaireTemplate::factory()->create()->id];
        $data[$field] = $value;

        $this->actingAs(User::factory()->approved()->create(['role_id' => Role::where('name', 'editor')->sole()->id]))->from(route('courses.create'))
            ->post(route('courses.store'), $data)->assertRedirect(route('courses.create'))
            ->assertSessionHasErrors($field);

        $this->assertDatabaseCount('courses', 0);
    }

    public function test_duplicate_course_names_return_validation_errors(): void
    {
        $template = QuestionnaireTemplate::factory()->create();
        $existing = Course::factory()->create(['name' => 'Web development', 'questionnaire_template_id' => $template->id]);

        $this->actingAs(User::factory()->approved()->create(['role_id' => Role::where('name', 'editor')->sole()->id]))->from(route('courses.create'))
            ->post(route('courses.store'), ['name' => $existing->name, 'questionnaire_template_id' => $template->id])
            ->assertRedirect(route('courses.create'))->assertSessionHasErrors('name');

        $this->assertDatabaseCount('courses', 1);
    }

    public function test_validation_errors_restore_the_escaped_name_and_selected_questionnaire(): void
    {
        $template = QuestionnaireTemplate::factory()->create();
        QuestionnaireTemplate::factory()->create();
        $name = '<script>alert(1)</script>'.str_repeat('a', 256);
        $this->actingAs(User::factory()->approved()->create(['role_id' => Role::where('name', 'editor')->sole()->id]))->from(route('courses.create'))
            ->post(route('courses.store'), ['name' => $name, 'questionnaire_template_id' => $template->id])
            ->assertRedirect(route('courses.create'));

        $response = $this->get(route('courses.create'))->assertOk()
            ->assertSee('role="alert"', false)->assertSee($name)->assertDontSee($name, false);
        $xpath = $this->xpath($response->getContent());
        $this->assertCount(1, $xpath->query('//select[@name="questionnaire_template_id"]/option[@selected]'));
        $this->assertCount(1, $xpath->query('//select[@name="questionnaire_template_id"]/option[@selected and @value="'.$template->id.'"]'));
        $this->assertSame($name, $xpath->query('//input[@name="name"]/@value')->item(0)->nodeValue);
    }

    private function xpath(string $html): \DOMXPath
    {
        $document = new \DOMDocument;
        @$document->loadHTML($html);

        return new \DOMXPath($document);
    }
}
