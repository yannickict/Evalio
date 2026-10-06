<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\QuestionnaireTemplate;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CourseEditingTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string}> */
    public static function editingRoles(): array
    {
        return ['admin' => ['admin'], 'editor' => ['editor']];
    }

    #[DataProvider('editingRoles')]
    public function test_editing_updates_the_course_default_but_preserves_existing_session_templates(string $role): void
    {
        $this->withoutVite();
        $original = QuestionnaireTemplate::factory()->create();
        $replacement = QuestionnaireTemplate::factory()->create();
        $course = Course::factory()->for($original, 'questionnaireTemplate')->create();
        $session = CourseSession::factory()->for($course)->create();
        $viewer = User::factory()->approved()->create(['role_id' => Role::where('name', $role)->sole()->id]);

        $this->actingAs($viewer)->get(route('courses.edit', $course))->assertOk()
            ->assertSee('value="'.e($course->name).'"', false)
            ->assertSee(route('courses.update', $course), false);
        $this->patch(route('courses.update', $course), [
            'name' => 'Updated name', 'questionnaire_template_id' => $replacement->id, 'id' => 999999,
        ])->assertRedirect(route('courses.index'))->assertSessionHasNoErrors()->assertSessionHas('status', 'Course updated.');
        $this->assertDatabaseHas('courses', ['id' => $course->id, 'name' => 'Updated name', 'questionnaire_template_id' => $replacement->id]);
        $this->assertSame($original->id, $session->fresh()->questionnaire_template_id);

        $this->patch(route('courses.update', $course), [
            'name' => 'Updated name', 'questionnaire_template_id' => $replacement->id,
        ])->assertSessionHasNoErrors();
    }

    public function test_guests_and_instructors_cannot_edit_courses(): void
    {
        $course = Course::factory()->create();
        $this->get(route('courses.edit', $course))->assertRedirect(route('login'));
        $this->patch(route('courses.update', $course), [])->assertRedirect(route('login'));
        $this->actingAs(User::factory()->approved()->create())->get(route('courses.edit', $course))->assertForbidden();
        $this->patch(route('courses.update', $course), [])->assertForbidden();
    }

    public function test_duplicate_names_and_invalid_templates_are_rejected_without_changes(): void
    {
        $template = QuestionnaireTemplate::factory()->create();
        $course = Course::factory()->for($template, 'questionnaireTemplate')->create()->refresh();
        $other = Course::factory()->create();
        $this->actingAs(User::factory()->approved()->create(['role_id' => Role::where('name', 'editor')->sole()->id]));

        foreach ([
            ['name' => $other->name, 'questionnaire_template_id' => $template->id],
            ['name' => '', 'questionnaire_template_id' => $template->id],
            ['name' => $course->name, 'questionnaire_template_id' => 999999],
        ] as $data) {
            $this->from(route('courses.edit', $course))->patch(route('courses.update', $course), $data)
                ->assertRedirect(route('courses.edit', $course))->assertSessionHasErrors();
            $this->assertSame($course->getRawOriginal(), $course->fresh()->getRawOriginal());
        }
    }

    public function test_invalid_update_redisplays_errors_and_submitted_values(): void
    {
        $this->withoutVite();
        $course = Course::factory()->create();
        $template = QuestionnaireTemplate::factory()->create();
        $other = Course::factory()->create(['name' => '<script>Duplicate</script>']);
        $this->actingAs(User::factory()->approved()->create(['role_id' => Role::where('name', 'editor')->sole()->id]));
        $this->from(route('courses.edit', $course))->patch(route('courses.update', $course), [
            'name' => $other->name, 'questionnaire_template_id' => $template->id,
        ])->assertRedirect(route('courses.edit', $course));
        $this->get(route('courses.edit', $course))->assertOk()
            ->assertSee('The name has already been taken.')
            ->assertSee('value="'.e($other->name).'"', false)
            ->assertSee('value="'.$template->id.'" selected', false);
    }
}
