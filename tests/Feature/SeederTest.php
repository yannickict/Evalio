<?php

namespace Tests\Feature;

use App\Models\CourseSession;
use App\Models\QuestionnaireTemplate;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\QuestionnaireSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_data_has_approved_admin_and_unique_session_codes(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'admin@example.com')->sole();
        $this->assertTrue($admin->is_approved);
        $this->assertSame('admin', $admin->role->name);
        $this->assertTrue(Hash::check('password', $admin->password));

        $codes = CourseSession::pluck('code');
        $this->assertCount(15, $codes);
        $this->assertCount(15, $codes->unique());
        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^[1-9][0-9]{5}$/', $code);
        }

        $this->assertDatabaseCount('feedback_forms', 45);
        $this->assertSame(3, User::where('is_approved', false)->count());
    }

    public function test_demo_data_includes_closed_sessions_with_questionnaires(): void
    {
        $this->seed(DatabaseSeeder::class);

        $sessions = CourseSession::with('course.questionnaireTemplate.questions.options', 'feedbackForms')
            ->where('evaluation_status', 'closed')->get();

        $this->assertCount(5, $sessions);
        $this->assertCount(5, $sessions->pluck('course_id')->unique());

        foreach ($sessions as $session) {
            $this->assertTrue($session->end_date->lt(today()));
            $this->assertCount(3, $session->feedbackForms);
            $questions = $session->course->questionnaireTemplate->questions;
            $this->assertCount(10, $questions);
            $this->assertTrue($questions->contains('type', 'free_text'));
            foreach ($questions->where('type', 'single_choice') as $question) {
                $this->assertNotEmpty($question->options);
            }
        }
    }

    public function test_database_rejects_duplicate_session_codes(): void
    {
        CourseSession::factory()->create(['code' => '123456']);

        $this->expectException(QueryException::class);
        CourseSession::factory()->create(['code' => '123456']);
    }

    public function test_cleared_codes_can_be_reused_without_deleting_sessions(): void
    {
        $session = CourseSession::factory()->create(['code' => '123456']);
        $session->code = null;
        $session->save();
        CourseSession::factory()->create(['code' => null]);
        CourseSession::factory()->create(['code' => '123456']);

        $this->assertDatabaseCount('course_sessions', 3);
        $this->assertDatabaseHas('course_sessions', ['id' => $session->id, 'code' => null]);
    }

    public function test_role_seeding_is_repeatable_and_preserves_permissions(): void
    {
        $this->seed(RoleSeeder::class);
        $this->seed(RoleSeeder::class);
        $this->assertDatabaseCount('roles', 3);

        foreach (Role::all() as $role) {
            $this->assertSame($role->name === 'admin', $role->approve_registrations);
            $this->assertSame($role->name === 'admin', $role->assign_roles);
            $this->assertSame($role->name !== 'instructor', $role->see_overview);
        }
    }

    public function test_questionnaire_seeding_is_repeatable_and_preserves_question_order(): void
    {
        $this->seed(QuestionnaireSeeder::class);
        $this->seed(QuestionnaireSeeder::class);

        $this->assertDatabaseCount('questionnaire_templates', 1);
        $this->assertDatabaseCount('questions', 10);
        $template = QuestionnaireTemplate::sole();
        $questions = $template->questions;
        $this->assertSame(range(1, 10), $questions->pluck('pivot.position')->all());

        foreach ($questions as $question) {
            $this->assertSame($question->type === 'single_choice', $question->allows_comment);
            $this->assertSame($question->type === 'single_choice', $question->options->isNotEmpty());
        }
    }
}
