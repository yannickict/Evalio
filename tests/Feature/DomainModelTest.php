<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\Question;
use App\Models\QuestionnaireTemplate;
use App\Models\QuestionOption;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DomainModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_course_and_template_relationships_resolve_both_directions(): void
    {
        $template = QuestionnaireTemplate::factory()->create();
        $course = Course::factory()->for($template, 'questionnaireTemplate')->create();
        $session = CourseSession::factory()->for($course)->create();

        $this->assertTrue($template->courses()->sole()->is($course));
        $this->assertTrue($course->questionnaireTemplate->is($template));
        $this->assertTrue($course->sessions()->sole()->is($session));
    }

    public function test_shared_questions_preserve_template_positions_and_option_order(): void
    {
        $first = QuestionnaireTemplate::factory()->create();
        $second = QuestionnaireTemplate::factory()->create();
        $question = Question::factory()->create(['allows_comment' => 1]);
        $other = Question::factory()->create(['allows_comment' => 0]);
        $first->questions()->attach($question, ['position' => 2]);
        $first->questions()->attach($other, ['position' => 1]);
        $second->questions()->attach($question, ['position' => 1]);
        $options = QuestionOption::factory()->for($question)->count(2)->create();

        $this->assertSame([$other->id, $question->id], $first->questions->modelKeys());
        $this->assertEqualsCanonicalizing([$first->id, $second->id], $question->questionnaireTemplates->modelKeys());
        $this->assertSame(2, $question->questionnaireTemplates->firstWhere('id', $first->id)->pivot->position);
        $this->assertSame($options->modelKeys(), $question->options->modelKeys());
        $this->assertTrue($options->first()->question->is($question));
        $this->assertTrue($question->allows_comment);
        $this->assertFalse($other->allows_comment);
    }

    public function test_role_permissions_are_boolean_and_membership_tracks_users(): void
    {
        $role = Role::factory()->create([
            'name' => 'custom', 'see_overview' => 1, 'update' => 0,
            'delete' => 1, 'assign_roles' => 0, 'approve_registrations' => 1,
        ]);
        $user = User::factory()->create(['role_id' => $role->id]);

        $this->assertTrue($role->users()->sole()->is($user));
        $this->assertTrue($user->role->is($role));
        foreach (['see_overview' => true, 'update' => false, 'delete' => true, 'assign_roles' => false, 'approve_registrations' => true] as $permission => $expected) {
            $this->assertSame($expected, $role->$permission);
        }
    }

    public function test_user_name_normalizes_whitespace_and_hides_credentials(): void
    {
        $user = User::factory()->create();
        $user->name = '  Ada   Lovelace Byron  ';
        $user->save();
        $user->refresh();

        $this->assertSame('Ada', $user->first_name);
        $this->assertSame('Lovelace Byron', $user->last_name);
        $this->assertSame('Ada Lovelace Byron', $user->name);
        $this->assertArrayNotHasKey('password', $user->toArray());
        $this->assertArrayNotHasKey('remember_token', $user->toArray());
        $user->name = 'Prince';
        $this->assertSame('', $user->last_name);
        $this->assertSame('Prince', $user->name);
        $user->name = '  ';
        $this->assertSame('', $user->name);
    }
}
