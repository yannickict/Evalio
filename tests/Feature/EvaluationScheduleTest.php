<?php

namespace Tests\Feature;

use App\Models\CourseSession;
use App\Models\FeedbackForm;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EvaluationScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['evaluation.timezone' => 'Europe/Zurich']);
        $this->travelTo(Carbon::parse('2026-10-06 12:00:00', 'Europe/Zurich'));
    }

    public function test_command_opens_sessions_on_their_start_date_and_keeps_codes_on_reruns(): void
    {
        $session = CourseSession::factory()->create(['start_date' => '2026-10-06', 'end_date' => '2026-10-09']);

        $this->artisan('evaluations:update-statuses')->expectsOutput('Updated 1 evaluation statuses.')->assertSuccessful();
        $form = $session->feedbackForm()->sole();
        $this->assertSame('open', $session->fresh()->evaluation_status);
        $this->assertMatchesRegularExpression('/^\d{6}$/', $form->code);
        $this->artisan('evaluations:update-statuses')->expectsOutput('Updated 0 evaluation statuses.')->assertSuccessful();
        $this->assertSame($form->code, $form->fresh()->code);
        $this->assertDatabaseCount('feedback_forms', 1);
    }

    public function test_command_closes_on_the_fourteenth_day_after_the_end_date(): void
    {
        $session = CourseSession::factory()->create([
            'start_date' => '2026-09-20', 'end_date' => '2026-09-22', 'evaluation_status' => 'open',
        ]);
        $form = FeedbackForm::factory()->for($session, 'courseSession')->create(['code' => '012345']);

        $this->travelTo(Carbon::parse('2026-10-05 12:00:00', 'Europe/Zurich'));
        $this->artisan('evaluations:update-statuses')->assertSuccessful();
        $this->assertSame('open', $session->fresh()->evaluation_status);
        $this->assertSame('012345', $form->fresh()->code);

        $this->travelTo(Carbon::parse('2026-10-06 00:00:00', 'Europe/Zurich'));
        $this->artisan('evaluations:update-statuses')->expectsOutput('Updated 1 evaluation statuses.')->assertSuccessful();
        $this->assertSame('closed', $session->fresh()->evaluation_status);
        $this->assertNull($form->fresh()->code);
        $this->artisan('evaluations:update-statuses')->expectsOutput('Updated 0 evaluation statuses.')->assertSuccessful();
    }

    public function test_sessions_on_other_dates_keep_their_manual_statuses(): void
    {
        $future = CourseSession::factory()->create(['start_date' => '2026-10-07', 'end_date' => '2026-10-09']);
        $manuallyClosed = CourseSession::factory()->create([
            'start_date' => '2026-10-05', 'end_date' => '2026-10-08', 'evaluation_status' => 'closed',
        ]);
        $manuallyReopened = CourseSession::factory()->create([
            'start_date' => '2026-09-19', 'end_date' => '2026-09-21', 'evaluation_status' => 'open',
        ]);

        $this->artisan('evaluations:update-statuses')->expectsOutput('Updated 0 evaluation statuses.')->assertSuccessful();
        $this->assertNull($future->fresh()->evaluation_status);
        $this->assertSame('closed', $manuallyClosed->fresh()->evaluation_status);
        $this->assertSame('open', $manuallyReopened->fresh()->evaluation_status);
        $this->assertDatabaseCount('feedback_forms', 0);
    }

    public function test_command_uses_the_swiss_calendar_date_even_when_utc_is_still_yesterday(): void
    {
        $this->travelTo(Carbon::parse('2026-10-05 22:30:00', 'UTC'));
        $session = CourseSession::factory()->create(['start_date' => '2026-10-06', 'end_date' => '2026-10-09']);

        $this->artisan('evaluations:update-statuses')->assertSuccessful();
        $this->assertSame('open', $session->fresh()->evaluation_status);
    }

    public function test_job_is_registered_hourly_with_overlap_prevention(): void
    {
        $events = collect(app(Schedule::class)->events())
            ->filter(fn ($event) => str_contains($event->command, 'evaluations:update-statuses'));
        $this->assertCount(1, $events);
        $event = $events->sole();
        $this->assertSame('0 * * * *', $event->expression);
        $this->assertSame('Europe/Zurich', $event->timezone);
        $this->assertTrue($event->withoutOverlapping);
    }
}
