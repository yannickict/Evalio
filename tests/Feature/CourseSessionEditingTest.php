<?php

namespace Tests\Feature;

use App\Models\CourseSession;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CourseSessionEditingTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string}> */
    public static function editingRoles(): array
    {
        return ['admin' => ['admin'], 'editor' => ['editor']];
    }

    #[DataProvider('editingRoles')]
    public function test_admins_and_editors_can_edit_instructor_and_dates_only(string $role): void
    {
        $this->withoutVite();
        $session = CourseSession::factory()->create(['evaluation_status' => 'closed']);
        $instructor = User::factory()->approved()->create();
        $viewer = User::factory()->approved()->create(['role_id' => Role::where('name', $role)->sole()->id]);

        $this->actingAs($viewer)->get(route('sessions.edit', $session))->assertOk()
            ->assertSee(route('sessions.update', $session), false)
            ->assertSee('value="'.$session->start_date->toDateString().'"', false);
        $this->patch(route('sessions.update', $session), [
            'instructor_id' => $instructor->id,
            'start_date' => '2026-10-08', 'end_date' => '2026-10-10',
            'course_id' => 999999, 'questionnaire_template_id' => 999999,
            'course_session_number' => 'CHANGED', 'evaluation_status' => 'open',
        ])->assertRedirect(route('sessions.index'))->assertSessionHasNoErrors()->assertSessionHas('status', 'Session updated.');

        $updated = $session->fresh();
        $this->assertSame($instructor->id, $updated->instructor_id);
        $this->assertSame('2026-10-08', $updated->start_date->toDateString());
        $this->assertSame('2026-10-10', $updated->end_date->toDateString());
        $this->assertSame($session->course_id, $updated->course_id);
        $this->assertSame($session->questionnaire_template_id, $updated->questionnaire_template_id);
        $this->assertSame($session->course_session_number, $updated->course_session_number);
        $this->assertSame('closed', $updated->evaluation_status);
    }

    public function test_guests_and_instructors_cannot_edit_sessions(): void
    {
        $instructor = User::factory()->approved()->create();
        $session = CourseSession::factory()->for($instructor, 'instructor')->create();
        $this->get(route('sessions.edit', $session))->assertRedirect(route('login'));
        $this->patch(route('sessions.update', $session), [])->assertRedirect(route('login'));
        $this->actingAs($instructor)->get(route('sessions.edit', $session))->assertForbidden();
        $this->patch(route('sessions.update', $session), [])->assertForbidden();
    }

    public function test_invalid_instructors_and_dates_leave_the_session_unchanged(): void
    {
        $session = CourseSession::factory()->create()->refresh();
        $viewer = User::factory()->approved()->create(['role_id' => Role::where('name', 'admin')->sole()->id]);
        $approved = User::factory()->approved()->create();
        $pending = User::factory()->create();
        $this->actingAs($viewer);

        foreach ([
            ['instructor_id' => $pending->id, 'start_date' => '2026-10-08', 'end_date' => '2026-10-10'],
            ['instructor_id' => $viewer->id, 'start_date' => '2026-10-08', 'end_date' => '2026-10-10'],
            ['instructor_id' => $approved->id, 'start_date' => '2026-10-10', 'end_date' => '2026-10-08'],
        ] as $data) {
            $this->from(route('sessions.edit', $session))->patch(route('sessions.update', $session), $data)
                ->assertRedirect(route('sessions.edit', $session))->assertSessionHasErrors();
            $this->assertSame($session->getRawOriginal(), $session->fresh()->getRawOriginal());
        }
    }
}
