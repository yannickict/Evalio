<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\FeedbackForm;
use App\Models\QuestionOption;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CourseDeletionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_delete_an_empty_course(): void
    {
        $course = Course::factory()->create();
        $admin = User::factory()->approved()->create(['role_id' => Role::where('name', 'admin')->sole()->id]);

        $this->actingAs($admin)->delete(route('courses.delete', $course))
            ->assertRedirect(route('courses.index'))->assertSessionHas('status', 'Course deleted.');

        $this->assertModelMissing($course);
    }

    public function test_course_deletion_removes_only_its_sessions_feedback_and_answers(): void
    {
        $course = Course::factory()->create();
        $sessions = CourseSession::factory()->count(2)->for($course)->create();
        $emptySession = CourseSession::factory()->for($course)->create();
        $forms = $sessions->map(fn ($session) => FeedbackForm::factory()->create(['course_session_id' => $session->id]));
        $otherForm = FeedbackForm::factory()->create();
        $option = QuestionOption::factory()->create();
        $answers = $forms->push($otherForm)->map(fn ($form) => Answer::create([
            'feedback_form_id' => $form->id,
            'question_id' => $option->question_id,
            'question_option_id' => $option->id,
        ]));
        $admin = User::factory()->approved()->create(['role_id' => Role::where('name', 'admin')->sole()->id]);

        $this->actingAs($admin)->delete(route('courses.delete', $course))
            ->assertRedirect(route('courses.index'))->assertSessionHasNoErrors();

        $this->assertModelMissing($course);
        $this->assertModelMissing($emptySession);
        foreach ($sessions as $index => $session) {
            $this->assertModelMissing($session);
            $this->assertModelMissing($forms[$index]);
            $this->assertModelMissing($answers[$index]);
            $this->assertModelExists($session->instructor);
        }
        $this->assertModelExists($otherForm);
        $this->assertModelExists($otherForm->courseSession);
        $this->assertModelExists($otherForm->courseSession->course);
        $this->assertModelExists($answers->last());
        $this->assertModelExists($option);
        $this->assertModelExists($option->question);
    }

    public static function nonAdminRoles(): array
    {
        return ['editor' => ['editor'], 'instructor' => ['instructor']];
    }

    #[DataProvider('nonAdminRoles')]
    public function test_non_admins_cannot_delete_courses(string $role): void
    {
        $course = Course::factory()->create();
        $user = User::factory()->approved()->create(['role_id' => Role::where('name', $role)->sole()->id]);

        $this->actingAs($user)->delete(route('courses.delete', $course))->assertForbidden();

        $this->assertModelExists($course);
    }

    public function test_guests_cannot_delete_courses(): void
    {
        $course = Course::factory()->create();

        $this->delete(route('courses.delete', $course))->assertRedirect(route('login'));

        $this->assertModelExists($course);
    }
}
