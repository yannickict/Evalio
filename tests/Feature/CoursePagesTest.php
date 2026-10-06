<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\QuestionnaireTemplate;
use App\Models\Role;
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

    public function test_course_modals_link_to_sessions_visible_to_each_role(): void
    {
        $owner = User::factory()->approved()->create();
        $own = CourseSession::factory()->for($owner, 'instructor')->create();
        $other = CourseSession::factory()->create();

        $response = $this->actingAs($owner)->get(route('courses.index'))->assertOk();
        $response->assertSee(route('sessions.index', ['session' => $own->id]), false)
            ->assertDontSee(route('sessions.index', ['session' => $other->id]), false)
            ->assertDontSee($other->course_session_number)
            ->assertSee('1 session assigned to you')
            ->assertSee('No sessions are assigned to you for this course.')
            ->assertViewHas('courses', fn ($courses) => $courses->firstWhere('id', $own->course_id)->sessions_count === 1
                && $courses->firstWhere('id', $other->course_id)->sessions_count === 0
                && $courses->firstWhere('id', $other->course_id)->sessions->isEmpty());

        foreach (['admin', 'editor'] as $role) {
            $viewer = User::factory()->approved()->create([
                'role_id' => Role::where('name', $role)->sole()->id,
            ]);
            $this->actingAs($viewer)->get(route('courses.index'))->assertOk()
                ->assertSee(route('sessions.index', ['session' => $own->id]), false)
                ->assertSee(route('sessions.index', ['session' => $other->id]), false);
        }
    }

    public function test_course_pages_require_authentication(): void
    {
        $this->get(route('courses.index'))->assertRedirect(route('login'));
        $this->get(route('courses.create'))->assertRedirect(route('login'));
    }

    public function test_user_without_a_role_cannot_list_courses(): void
    {
        $user = User::factory()->approved()->create();
        $user->setRelation('role', null);

        $this->actingAs($user)->get(route('courses.index'))->assertForbidden();
    }

    public function test_courses_show_counts_questionnaires_and_session_details(): void
    {
        $template = QuestionnaireTemplate::factory()->create();
        $course = Course::factory()->create(['questionnaire_template_id' => $template->id]);
        $emptyCourse = Course::factory()->create();
        $session = CourseSession::factory()->create(['course_id' => $course->id]);

        $this->actingAs(User::factory()->approved()->create([
            'role_id' => Role::where('name', 'editor')->sole()->id,
        ]))->get(route('courses.index'))
            ->assertOk()->assertViewIs('pages.courses.index')
            ->assertViewHas('courses', fn ($courses) => $courses->firstWhere('id', $course->id)->sessions_count === 1
                && $courses->firstWhere('id', $emptyCourse->id)->sessions_count === 0)
            ->assertSee($course->name)->assertSee($template->name)
            ->assertSee($session->course_session_number)->assertSee($session->instructor->name)
            ->assertSee('data-bs-target="#course-'.$course->id.'"', false)
            ->assertSee('No sessions for this course yet.')
            ->assertSee('No questionnaire assigned')
            ->assertSee(route('courses.create'), false);
    }

    public function test_empty_library_and_course_creation_form_are_available(): void
    {
        $this->actingAs(User::factory()->approved()->create([
            'role_id' => Role::where('name', 'editor')->sole()->id,
        ]));
        $this->get(route('courses.index'))->assertOk()->assertSee('No courses yet');
        $this->get(route('courses.create'))->assertOk()->assertViewIs('pages.courses.create')
            ->assertSee(route('courses.index'), false)->assertSee('Course name')
            ->assertSee('No questionnaires available.')
            ->assertSee(route('questionnaires.create'), false);
    }
}
