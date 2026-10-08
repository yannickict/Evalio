<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\QuestionnaireTemplate;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CourseImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_skips_invalid_rows_and_links_valid_courses(): void
    {
        $this->withoutVite();
        $template = QuestionnaireTemplate::factory()->create(['name' => 'Feedback']);
        $this->actingAs(User::factory()->approved()->create(['role_id' => Role::where('name', 'admin')->sole()->id]));
        $csv = "\xEF\xBB\xBFcourse_name,questionnaire_name\n\" Web, basics \",Feedback\nUnknown,Missing\n\"Web, basics\",Feedback\n,Feedback\nToo,many,columns\nSQL,Feedback\n";

        $this->post(route('settings.courses-import.store'), [
            'file' => UploadedFile::fake()->createWithContent('courses.csv', $csv),
        ])->assertOk()->assertViewHas('imported', 2)
            ->assertViewHas('failures', fn ($failures) => array_column($failures, 'row') === [3, 4, 5, 6])
            ->assertSee('View courses');

        $this->assertDatabaseCount('courses', 2);
        $this->assertDatabaseHas('courses', ['name' => 'Web, basics', 'questionnaire_template_id' => $template->id]);
        $this->assertDatabaseHas('courses', ['name' => 'SQL', 'questionnaire_template_id' => $template->id]);
        $this->assertDatabaseCount('course_sessions', 0);
    }

    public function test_existing_course_is_preserved(): void
    {
        $this->withoutVite();
        $existing = Course::factory()->create(['name' => 'WEB']);
        $template = QuestionnaireTemplate::factory()->create(['name' => 'Replacement']);
        $this->actingAs(User::factory()->approved()->create(['role_id' => Role::where('name', 'admin')->sole()->id]));

        $this->post(route('settings.courses-import.store'), [
            'file' => UploadedFile::fake()->createWithContent('courses.csv', "course_name,questionnaire_name\nWEB,Replacement\n"),
        ])->assertOk()->assertViewHas('imported', 0);

        $this->assertDatabaseCount('courses', 1);
        $this->assertNotSame($template->id, $existing->fresh()->questionnaire_template_id);
    }

    public function test_reordered_headers_quoted_values_and_extra_columns_are_supported(): void
    {
        $this->withoutVite();
        $template = QuestionnaireTemplate::factory()->create(['name' => 'Feedback, standard']);
        $this->actingAs(User::factory()->approved()->create(['role_id' => Role::where('name', 'admin')->sole()->id]));
        $csv = "questionnaire_name,course_name,id\n\"Feedback, standard\",\"Python\nadvanced\",999999\n";

        $this->post(route('settings.courses-import.store'), [
            'file' => UploadedFile::fake()->createWithContent('courses.csv', $csv),
        ])->assertOk()->assertViewHas('imported', 1)->assertViewHas('failures', []);

        $course = Course::sole();
        $this->assertSame("Python\nadvanced", $course->name);
        $this->assertSame($template->id, $course->questionnaire_template_id);
        $this->assertNotSame(999999, $course->id);
    }

    public function test_invalid_file_or_headers_do_not_import_courses(): void
    {
        $this->actingAs(User::factory()->approved()->create(['role_id' => Role::where('name', 'admin')->sole()->id]));

        foreach (['', "wrong,headers\nWEB,Feedback\n", "course_name,questionnaire_name,course_name\n", "course_name,questionnaire_name,\n"] as $csv) {
            $this->from(route('settings.courses-import.create'))->post(route('settings.courses-import.store'), [
                'file' => UploadedFile::fake()->createWithContent('courses.csv', $csv),
            ])->assertRedirect(route('settings.courses-import.create'))->assertSessionHasErrors('file');
        }

        $this->post(route('settings.courses-import.store'), [])->assertSessionHasErrors('file');
        $this->post(route('settings.courses-import.store'), [
            'file' => UploadedFile::fake()->create('courses.csv', 2049),
        ])->assertSessionHasErrors('file');
        $this->post(route('settings.courses-import.store'), [
            'file' => UploadedFile::fake()->create('courses.txt'),
        ])->assertSessionHasErrors('file');
        $this->assertDatabaseCount('courses', 0);
    }

    public function test_only_admins_can_access_course_imports(): void
    {
        $this->get(route('settings.courses-import.create'))->assertRedirect(route('login'));
        $this->post(route('settings.courses-import.store'), [])->assertRedirect(route('login'));

        foreach (['editor', 'instructor'] as $role) {
            $this->actingAs(User::factory()->approved()->create(['role_id' => Role::where('name', $role)->sole()->id]));
            $this->get(route('settings.courses-import.create'))->assertForbidden();
            $this->post(route('settings.courses-import.store'), [])->assertForbidden();
        }
    }
}
