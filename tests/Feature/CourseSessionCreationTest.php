<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CourseSessionCreationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guests_cannot_view_or_create_sessions(): void
    {
        $this->get(route('sessions.create'))->assertRedirect(route('login'));
        $this->post(route('sessions.store'), [])->assertRedirect(route('login'));
        $this->assertDatabaseCount('course_sessions', 0);
    }

    public function test_form_lists_sorted_courses_and_only_approved_instructors(): void
    {
        $viewer = User::factory()->approved()->create(['first_name' => 'Zulu']);
        $zulu = Course::factory()->create(['name' => 'Zulu']);
        $alpha = Course::factory()->create(['name' => 'Alpha']);
        $pending = User::factory()->create();
        $editor = User::factory()->approved()->create(['role_id' => Role::where('name', 'editor')->sole()->id]);
        $instructor = User::factory()->approved()->create(['first_name' => 'Aaron', 'last_name' => '<script>alert(1)</script>']);

        $this->actingAs($viewer)->get(route('sessions.create'))
            ->assertOk()
            ->assertViewHas('courses', fn ($courses) => $courses->modelKeys() === [$alpha->id, $zulu->id])
            ->assertViewHas('instructors', fn ($users) => $users->modelKeys() === [$instructor->id, $viewer->id])
            ->assertDontSee($pending->email)
            ->assertDontSee($editor->name)
            ->assertSee(e($instructor->name), false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_empty_form_shows_missing_course_and_instructor_options(): void
    {
        $viewer = User::factory()->create();
        $this->actingAs($viewer)->get(route('sessions.create'))->assertOk()
            ->assertSee('No courses available')->assertSee('No instructors available');
    }

    public function test_form_displays_escaped_validation_errors_and_restores_dates(): void
    {
        $viewer = User::factory()->approved()->create();
        $this->actingAs($viewer)->withSession([
            '_old_input' => ['start_date' => '2026-10-05', 'end_date' => '2026-10-07'],
        ])->get(route('sessions.create'))->assertOk()
            ->assertSee('value="2026-10-05"', false)
            ->assertSee('value="2026-10-07"', false);
        $this->view('pages.sessions.create', [
            'courses' => collect(),
            'instructors' => collect(),
            'errors' => (new ViewErrorBag)->put('default', new MessageBag([
                'start_date' => '<script>invalid date</script>',
            ])),
        ])
            ->assertSee('role="alert"', false)
            ->assertSee('&lt;script&gt;invalid date&lt;/script&gt;', false)
            ->assertDontSee('<script>invalid date</script>', false);
    }

    public function test_creation_assigns_number_casts_dates_and_ignores_unvalidated_fields(): void
    {
        $instructor = User::factory()->approved()->create();
        $course = Course::factory()->create();
        $this->actingAs($instructor)->post(route('sessions.store'), [
            'course_id' => $course->id,
            'instructor_id' => $instructor->id,
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-05',
            'course_session_number' => 'INJECTED',
            'evaluation_status' => 'closed',
        ])->assertRedirect(route('sessions.index'))->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Session created.');

        $session = CourseSession::sole();
        $this->assertSame('COURSE.0001', $session->course_session_number);
        $this->assertNull($session->evaluation_status);
        $this->assertSame('2026-10-05', $session->start_date->toDateString());
        $this->assertSame('2026-10-05', $session->end_date->toDateString());
        $this->assertTrue($session->course->is($course));
        $this->assertTrue($session->instructor->is($instructor));
        $this->assertTrue($instructor->courseSessions()->sole()->is($session));
    }

    #[DataProvider('invalidFields')]
    public function test_invalid_input_does_not_create_session(string $field, mixed $value): void
    {
        $instructor = User::factory()->approved()->create();
        $data = [
            'course_id' => Course::factory()->create()->id,
            'instructor_id' => $instructor->id,
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-07',
        ];
        $data[$field] = $value;

        $this->actingAs($instructor)->from(route('sessions.create'))
            ->post(route('sessions.store'), $data)
            ->assertRedirect(route('sessions.create'))->assertSessionHasErrors($field);
        $this->assertDatabaseCount('course_sessions', 0);
    }

    public static function invalidFields(): array
    {
        return [
            'missing course' => ['course_id', null],
            'invalid course' => ['course_id', 'invalid'],
            'unknown course' => ['course_id', 999999],
            'missing instructor' => ['instructor_id', null],
            'invalid instructor' => ['instructor_id', 'invalid'],
            'unknown instructor' => ['instructor_id', 999999],
            'missing start' => ['start_date', null],
            'invalid start' => ['start_date', 'invalid'],
            'missing end' => ['end_date', null],
            'invalid end' => ['end_date', 'invalid'],
            'end before start' => ['end_date', '2026-10-04'],
        ];
    }

    public function test_pending_instructors_and_other_roles_cannot_be_assigned(): void
    {
        $viewer = User::factory()->approved()->create();
        $course = Course::factory()->create();
        $invalidInstructors = [User::factory()->create()];
        foreach (['admin', 'editor'] as $role) {
            $invalidInstructors[] = User::factory()->approved()->create(['role_id' => Role::where('name', $role)->sole()->id]);
        }

        foreach ($invalidInstructors as $user) {
            $this->actingAs($viewer)->post(route('sessions.store'), [
                'course_id' => $course->id,
                'instructor_id' => $user->id,
                'start_date' => '2026-10-05',
                'end_date' => '2026-10-07',
            ])->assertSessionHasErrors('instructor_id');
        }
        $this->assertDatabaseCount('course_sessions', 0);
    }
}
