<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\FeedbackForm;
use App\Models\QuestionnaireTemplate;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CourseSessionPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('sessions.index'))->assertRedirect(route('login'));
    }

    public function test_user_without_a_role_cannot_view_overview(): void
    {
        $user = User::factory()->approved()->create();
        $user->setRelation('role', null);

        $this->actingAs($user)->get(route('sessions.index'))->assertForbidden();
    }

    /** @return array<string, array{string}> */
    public static function roles(): array
    {
        return [
            'instructor' => ['instructor'],
            'editor' => ['editor'],
            'admin' => ['admin'],
        ];
    }

    #[DataProvider('roles')]
    public function test_signed_in_users_with_a_role_can_view_overview(string $role): void
    {
        $user = User::factory()->approved()->create([
            'role_id' => Role::where('name', $role)->firstOrFail()->id,
        ]);

        $this->actingAs($user)->get(route('sessions.index'))->assertOk()
            ->assertViewIs('pages.sessions.index')->assertSee('No course sessions yet');
    }

    public function test_all_sessions_are_shown_with_their_details_and_evaluation_statuses(): void
    {
        $instructor = User::factory()->approved()->create([
            'first_name' => 'Alex', 'last_name' => 'Example',
        ]);
        $template = QuestionnaireTemplate::factory()->create(['name' => 'Training questionnaire']);
        $course = Course::factory()->for($template, 'questionnaireTemplate')->create(['name' => 'Laravel basics']);
        $closed = CourseSession::factory()->for($course)->for($instructor, 'instructor')->create([
            'evaluation_status' => 'closed',
            'start_date' => '2026-01-10',
            'end_date' => '2026-01-12',
        ]);
        $open = CourseSession::factory()->create(['evaluation_status' => 'open']);
        $automatic = CourseSession::factory()->create([
            'evaluation_status' => null,
            'start_date' => today()->subDays(10),
            'end_date' => today()->subDays(8),
        ]);

        $this->actingAs($instructor)->get(route('sessions.index'))->assertOk()
            ->assertSee('Laravel basics')->assertSee('Alex Example')
            ->assertSee('Training questionnaire')->assertSee('10 Jan 2026')->assertSee('12 Jan 2026')
            ->assertSee($closed->course_session_number)->assertSee('3 sessions')
            ->assertSee($open->course_session_number)->assertSee($automatic->course_session_number)
            ->assertSee('Evaluation closed')->assertSee('Evaluation open')->assertSee('Evaluation status not set')
            ->assertViewHas('courses', fn ($courses) => $courses->contains('id', $open->course_id)
                && $courses->contains('id', $automatic->course_id))
            ->assertViewHas('instructors', fn ($instructors) => $instructors->contains('id', $open->instructor_id)
                && $instructors->contains('id', $automatic->instructor_id))
            ->assertDontSee('No course sessions yet');
    }

    public function test_session_without_questionnaire_shows_fallback(): void
    {
        $session = CourseSession::factory()->create(['evaluation_status' => 'closed']);

        $this->actingAs(User::factory()->approved()->create())->get(route('sessions.index'))
            ->assertOk()->assertSee($session->course_session_number)->assertSee('No questionnaire assigned');
    }

    public function test_multiple_sessions_display_count_and_remain_in_registration_order(): void
    {
        $newer = CourseSession::factory()->create(['evaluation_status' => 'closed', 'created_at' => now()]);
        $older = CourseSession::factory()->create(['evaluation_status' => 'closed', 'created_at' => now()->subDay()]);

        $this->actingAs(User::factory()->approved()->create())->get(route('sessions.index'))->assertOk()
            ->assertSee('2 sessions')
            ->assertSeeInOrder([$older->course_session_number, $newer->course_session_number]);
    }

    public function test_course_names_are_escaped(): void
    {
        $course = Course::factory()->create(['name' => '<script>alert("course")</script>']);
        CourseSession::factory()->for($course)->create(['evaluation_status' => 'closed']);

        $this->actingAs(User::factory()->approved()->create())->get(route('sessions.index'))
            ->assertOk()->assertSee($course->name)->assertDontSee($course->name, false);
    }

    public function test_filter_options_are_unique_sorted_and_only_include_session_participants(): void
    {
        $alpha = Course::factory()->create(['name' => 'Alpha course']);
        $zulu = Course::factory()->create(['name' => 'Zulu course']);
        $alice = User::factory()->approved()->create(['first_name' => 'Alice', 'last_name' => 'Example']);
        $zoe = User::factory()->approved()->create(['first_name' => 'Zoe', 'last_name' => 'Example']);
        Course::factory()->create(['name' => 'Unused course']);
        $viewer = User::factory()->approved()->create();
        CourseSession::factory()->for($zulu)->for($zoe, 'instructor')->create(['evaluation_status' => 'open']);
        CourseSession::factory()->for($alpha)->for($alice, 'instructor')->count(2)->create();

        $response = $this->actingAs($viewer)->get(route('sessions.index'))->assertOk()
            ->assertViewHas('courses', fn ($courses) => $courses->pluck('id')->all() === [$alpha->id, $zulu->id])
            ->assertViewHas('instructors', fn ($instructors) => $instructors->pluck('id')->all() === [$alice->id, $zoe->id]);
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);

        foreach (['course' => [$alpha, $zulu], 'instructor' => [$alice, $zoe]] as $field => $expected) {
            $this->assertCount(1, $xpath->query('//select[@id="'.$field.'-filter"]'));
            $this->assertCount(1, $xpath->query('//label[@for="'.$field.'-filter"]'));
            $options = $xpath->query('//select[@id="'.$field.'-filter"]/option[@value!=""]');
            $this->assertCount(2, $options);
            foreach ($expected as $index => $model) {
                $this->assertSame($model->name, trim($options->item($index)->textContent));
                $this->assertSame((string) $model->id, $options->item($index)->getAttribute('value'));
            }
        }
        $this->assertCount(3, $xpath->query('//*[@data-session and @data-course and @data-instructor]'));
        $this->assertCount(2, $xpath->query('//*[@data-session and @data-course="'.$alpha->id.'" and @data-instructor="'.$alice->id.'"]'));
        $response->assertDontSee('Unused course');
    }

    public function test_empty_overview_has_empty_filters_and_single_session_uses_singular_count(): void
    {
        $user = User::factory()->approved()->create();
        $this->actingAs($user)->get(route('sessions.index'))->assertOk()
            ->assertSee('0 sessions')->assertSee('No course sessions yet')
            ->assertViewHas('courses', fn ($courses) => $courses->isEmpty())
            ->assertViewHas('instructors', fn ($instructors) => $instructors->isEmpty());

        CourseSession::factory()->create(['evaluation_status' => 'open']);
        $this->get(route('sessions.index'))->assertOk()->assertSee('1 session')
            ->assertDontSee('1 sessions')->assertDontSee('No course sessions yet');
    }

    public function test_cards_target_unique_accessible_modals_and_only_available_questionnaires_are_linked(): void
    {
        $template = QuestionnaireTemplate::factory()->create();
        $course = Course::factory()->for($template, 'questionnaireTemplate')->create();
        $available = CourseSession::factory()->for($course)->create(['evaluation_status' => 'closed']);
        FeedbackForm::factory()->for($available, 'courseSession')->create(['code' => '012345']);
        $noCode = CourseSession::factory()->for($course)->create(['evaluation_status' => 'closed']);
        FeedbackForm::factory()->for($noCode, 'courseSession')->create(['code' => null]);
        $noTemplate = CourseSession::factory()->create(['evaluation_status' => 'closed']);
        FeedbackForm::factory()->for($noTemplate, 'courseSession')->create(['code' => '111111']);
        $response = $this->actingAs(User::factory()->approved()->create())->get(route('sessions.index'))->assertOk();
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);

        foreach ([$available, $noCode, $noTemplate] as $session) {
            $id = 'session-'.$session->id;
            $this->assertCount(1, $xpath->query('//article//button[@type="button" and @data-bs-toggle="modal" and @data-bs-target="#'.$id.'"]'));
            $this->assertCount(1, $xpath->query('//div[@id="'.$id.'" and @aria-labelledby="session-title-'.$session->id.'"]'));
            $this->assertCount(1, $xpath->query('//*[@id="'.$id.'"]//h2[@id="session-title-'.$session->id.'"]'));
            $this->assertCount($session->is($available) ? 1 : 0, $xpath->query('//*[@id="'.$id.'"]//a'));
        }
        $response->assertSee(route('feedback.show', ['code' => '012345']), false);
        $response->assertDontSee(route('feedback.show', ['code' => '111111']), false);
    }
}
