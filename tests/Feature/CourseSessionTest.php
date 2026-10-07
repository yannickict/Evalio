<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\FeedbackForm;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_has_one_feedback_form_and_rejects_a_second(): void
    {
        $session = CourseSession::factory()->create();
        $form = FeedbackForm::factory()->for($session)->create(['code' => '123456']);

        $this->assertTrue($session->feedbackForm->is($form));
        $this->expectException(QueryException::class);

        FeedbackForm::factory()->for($session)->create(['code' => '654321']);
    }

    public function test_session_numbers_increment_by_default(): void
    {
        $course = Course::factory()->create(['name' => 'AID']);
        $first = CourseSession::factory()->for($course)->create();
        $second = CourseSession::factory()->for($course)->create();
        $other = CourseSession::factory()->for(Course::factory()->create(['name' => 'EPR']))->create();

        $this->assertSame('AID.001', $first->session_identifier);
        $this->assertSame('AID.002', $second->session_identifier);
        $this->assertSame('EPR.001', $other->session_identifier);
        $course->update(['name' => 'NEW']);
        $this->assertSame('NEW.002', $second->fresh()->session_identifier);
    }

    public function test_default_uses_highest_existing_number_and_preserves_explicit_numbers(): void
    {
        $course = Course::factory()->create(['name' => 'AID']);
        $explicit = CourseSession::factory()->for($course)->create(['session_number' => 999]);
        CourseSession::factory()->for($course)->create(['session_number' => 3]);
        $session = CourseSession::factory()->for($course)->create();

        $this->assertSame(999, $explicit->session_number);
        $this->assertSame('AID.1000', $session->session_identifier);
    }

    public function test_session_number_must_be_unique_within_a_course(): void
    {
        $session = CourseSession::factory()->create(['session_number' => 1]);
        $this->expectException(QueryException::class);
        CourseSession::factory()->for($session->course)->create(['session_number' => 1]);
    }
}
