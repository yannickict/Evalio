<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SessionImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_rows_inherit_templates_and_continue_numbering_after_failures(): void
    {
        $this->withoutVite();
        $course = Course::factory()->create(['name' => 'WEB']);
        $instructor = User::factory()->approved()->create(['email' => 'teacher@example.com']);
        CourseSession::factory()->for($course)->create(['session_number' => 7]);
        $this->actingAs(User::factory()->approved()->create(['role_id' => Role::where('name', 'admin')->sole()->id]));
        $csv = "\xEF\xBB\xBFend_date,instructor_email,course_name,start_date,session_number\r\n"
            ."2026-10-16,teacher@example.com, WEB ,2026-10-12,999\r\n"
            ."2026-10-16,teacher@example.com,Missing,2026-10-12,999\r\n"
            ."2026-10-16,missing@example.com,WEB,2026-10-12,999\r\n"
            ."2026-10-11,teacher@example.com,WEB,2026-10-12,999\r\n"
            ."2026-02-30,teacher@example.com,WEB,2026-02-30,999\r\n"
            ."too,few\r\n\r\n"
            ."2026-10-23,teacher@example.com,WEB,2026-10-19,999\r\n";

        $this->post(route('settings.sessions-import.store'), [
            'file' => UploadedFile::fake()->createWithContent('sessions.csv', $csv),
        ])->assertOk()->assertViewHas('imported', 2)
            ->assertViewHas('failures', fn ($failures) => array_column($failures, 'row') === [3, 4, 5, 6, 7]);

        $this->assertDatabaseCount('course_sessions', 3);
        foreach ([8, 9] as $number) {
            $this->assertDatabaseHas('course_sessions', [
                'course_id' => $course->id, 'instructor_id' => $instructor->id,
                'questionnaire_template_id' => $course->questionnaire_template_id, 'session_number' => $number,
            ]);
        }
        $this->assertDatabaseCount('feedback_forms', 0);
    }

    public function test_pending_instructors_and_other_roles_are_skipped_and_errors_are_escaped(): void
    {
        $this->withoutVite();
        Course::factory()->create(['name' => 'WEB']);
        $pending = User::factory()->create();
        $editor = User::factory()->approved()->create(['role_id' => Role::where('name', 'editor')->sole()->id]);
        $this->actingAs(User::factory()->approved()->create(['role_id' => Role::where('name', 'admin')->sole()->id]));
        $csv = "course_name,instructor_email,start_date,end_date\n"
            ."WEB,{$pending->email},2026-10-12,2026-10-16\n"
            ."WEB,{$editor->email},2026-10-12,2026-10-16\n"
            ."<script>alert(1)</script>,{$editor->email},2026-10-12,2026-10-16\n";

        $this->post(route('settings.sessions-import.store'), [
            'file' => UploadedFile::fake()->createWithContent('sessions.csv', $csv),
        ])->assertOk()->assertViewHas('imported', 0)
            ->assertViewHas('failures', fn ($failures) => count($failures) === 3)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
        $this->assertDatabaseCount('course_sessions', 0);
    }

    public function test_headers_and_uploads_are_validated_before_import(): void
    {
        $this->actingAs(User::factory()->approved()->create(['role_id' => Role::where('name', 'admin')->sole()->id]));
        foreach (['', "course_name,instructor_email\n", "course_name,instructor_email,start_date,end_date,course_name\n"] as $csv) {
            $this->from(route('settings.sessions-import.create'))->post(route('settings.sessions-import.store'), [
                'file' => UploadedFile::fake()->createWithContent('sessions.csv', $csv),
            ])->assertRedirect(route('settings.sessions-import.create'))->assertSessionHasErrors('file');
        }
        $this->post(route('settings.sessions-import.store'), [])->assertSessionHasErrors('file');
        $this->post(route('settings.sessions-import.store'), [
            'file' => UploadedFile::fake()->create('sessions.csv', 2049),
        ])->assertSessionHasErrors('file');
        $this->post(route('settings.sessions-import.store'), [
            'file' => UploadedFile::fake()->create('sessions.txt'),
        ])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('course_sessions', 0);
    }

    public function test_guests_and_non_admins_cannot_import_sessions(): void
    {
        $this->get(route('settings.sessions-import.create'))->assertRedirect(route('login'));
        $this->post(route('settings.sessions-import.store'), [])->assertRedirect(route('login'));
        foreach (['instructor', 'editor'] as $role) {
            $this->actingAs(User::factory()->approved()->create(['role_id' => Role::where('name', $role)->sole()->id]));
            $this->get(route('settings.sessions-import.create'))->assertForbidden();
            $this->post(route('settings.sessions-import.store'), [])->assertForbidden();
        }
    }
}
