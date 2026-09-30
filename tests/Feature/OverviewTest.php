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

class OverviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('overview'))->assertRedirect(route('login'));
    }

    public function test_user_without_a_role_cannot_view_overview(): void
    {
        $user = User::factory()->approved()->create();
        $user->setRelation('role', null);

        $this->actingAs($user)->get(route('overview'))->assertForbidden();
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

        $this->actingAs($user)->get(route('overview'))->assertOk()
            ->assertViewIs('overview')->assertSee('No closed evaluations yet');
    }

    public function test_only_explicitly_closed_sessions_are_shown_with_their_details(): void
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

        $this->actingAs($instructor)->get(route('overview'))->assertOk()
            ->assertSee('Laravel basics')->assertSee('Alex Example')
            ->assertSee('Training questionnaire')->assertSee('10 Jan 2026')->assertSee('12 Jan 2026')
            ->assertSee($closed->course_session_number)->assertSee('1 closed session')
            ->assertDontSee($open->course_session_number)->assertDontSee($automatic->course_session_number)
            ->assertDontSee('No closed evaluations yet');
    }

    public function test_session_without_questionnaire_shows_fallback(): void
    {
        $session = CourseSession::factory()->create(['evaluation_status' => 'closed']);

        $this->actingAs(User::factory()->approved()->create())->get(route('overview'))
            ->assertOk()->assertSee($session->course_session_number)->assertSee('No questionnaire assigned');
    }

    public function test_multiple_sessions_display_count_and_remain_in_registration_order(): void
    {
        $newer = CourseSession::factory()->create(['evaluation_status' => 'closed', 'created_at' => now()]);
        $older = CourseSession::factory()->create(['evaluation_status' => 'closed', 'created_at' => now()->subDay()]);

        $this->actingAs(User::factory()->approved()->create())->get(route('overview'))->assertOk()
            ->assertSee('2 closed sessions')
            ->assertSeeInOrder([$older->course_session_number, $newer->course_session_number]);
    }

    public function test_course_names_are_escaped(): void
    {
        $course = Course::factory()->create(['name' => '<script>alert("course")</script>']);
        CourseSession::factory()->for($course)->create(['evaluation_status' => 'closed']);

        $this->actingAs(User::factory()->approved()->create())->get(route('overview'))
            ->assertOk()->assertSee($course->name)->assertDontSee($course->name, false);
    }

    public function test_cards_target_unique_accessible_modals_and_only_available_questionnaires_are_linked(): void
    {
        $template = QuestionnaireTemplate::factory()->create();
        $course = Course::factory()->for($template, 'questionnaireTemplate')->create();
        $available = CourseSession::factory()->for($course)->create(['evaluation_status' => 'closed']);
        FeedbackForm::factory()->for($available, 'courseSession')->create(['code' => '012345']);
        FeedbackForm::factory()->for($available, 'courseSession')->create(['code' => '654321']);
        FeedbackForm::factory()->for($available, 'courseSession')->create(['code' => null]);
        $noCode = CourseSession::factory()->for($course)->create(['evaluation_status' => 'closed']);
        $noTemplate = CourseSession::factory()->create(['evaluation_status' => 'closed']);
        FeedbackForm::factory()->for($noTemplate, 'courseSession')->create(['code' => '111111']);
        $response = $this->actingAs(User::factory()->approved()->create())->get(route('overview'))->assertOk();
        $document = new \DOMDocument;
        @$document->loadHTML($response->getContent());
        $xpath = new \DOMXPath($document);

        foreach ([$available, $noCode, $noTemplate] as $session) {
            $id = 'session-'.$session->id;
            $this->assertCount(1, $xpath->query('//article//button[@type="button" and @data-bs-toggle="modal" and @data-bs-target="#'.$id.'"]'));
            $this->assertCount(1, $xpath->query('//div[@id="'.$id.'" and @aria-labelledby="session-title-'.$session->id.'"]'));
            $this->assertCount(1, $xpath->query('//*[@id="'.$id.'"]//h2[@id="session-title-'.$session->id.'"]'));
            $this->assertCount($session->is($available) ? 2 : 0, $xpath->query('//*[@id="'.$id.'"]//a'));
        }
        $response->assertSee(route('questionaire', ['code' => '012345']), false);
        $response->assertSee(route('questionaire', ['code' => '654321']), false)
            ->assertDontSee(route('questionaire', ['code' => '111111']), false);
    }
}
