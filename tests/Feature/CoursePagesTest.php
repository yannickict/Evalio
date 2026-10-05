<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\QuestionnaireTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoursePagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_course_pages_require_authentication(): void
    {
        $this->get(route('courses'))->assertRedirect(route('login'));
        $this->get(route('courses.create'))->assertRedirect(route('login'));
    }

    public function test_user_without_a_role_cannot_list_courses(): void
    {
        $user = User::factory()->approved()->create();
        $user->setRelation('role', null);

        $this->actingAs($user)->get(route('courses'))->assertForbidden();
    }

    public function test_courses_show_counts_questionnaires_and_session_details(): void
    {
        $template = QuestionnaireTemplate::factory()->create();
        $course = Course::factory()->create(['questionnaire_template_id' => $template->id]);
        $emptyCourse = Course::factory()->create();
        $session = CourseSession::factory()->create(['course_id' => $course->id]);

        $this->actingAs(User::factory()->approved()->create())->get(route('courses'))
            ->assertOk()->assertViewIs('courses')
            ->assertViewHas('courses', fn ($courses) => $courses->firstWhere('id', $course->id)->sessions_count === 1
                && $courses->firstWhere('id', $emptyCourse->id)->sessions_count === 0)
            ->assertSee($course->name)->assertSee($template->name)
            ->assertSee($session->course_session_number)->assertSee($session->instructor->name)
            ->assertSee('data-bs-target="#course-'.$course->id.'"', false)
            ->assertSee('No sessions for this course yet.')
            ->assertSee('No questionnaire assigned')
            ->assertSee(route('courses.create'), false);
    }

    public function test_empty_library_and_creation_preview_are_available(): void
    {
        $this->actingAs(User::factory()->approved()->create());
        $this->get(route('courses'))->assertOk()->assertSee('No courses yet');
        $this->get(route('courses.create'))->assertOk()->assertViewIs('courses.create')
            ->assertSee(route('courses'), false)->assertSee('Course name')
            ->assertSee('Saving courses will be available soon.');
    }
}
