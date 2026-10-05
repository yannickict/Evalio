<?php

namespace Tests\Feature;

use App\Models\CourseSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_session_numbers_increment_by_default(): void
    {
        $first = CourseSession::factory()->create();
        $second = CourseSession::factory()->create();

        $this->assertSame('COURSE.0001', $first->course_session_number);
        $this->assertSame('COURSE.0002', $second->course_session_number);
    }

    public function test_default_uses_highest_existing_number_and_preserves_explicit_numbers(): void
    {
        CourseSession::factory()->create(['course_session_number' => 'COURSE.9999']);
        $explicit = CourseSession::factory()->create(['course_session_number' => 'CUSTOM-1']);
        CourseSession::factory()->create(['course_session_number' => 'COURSE.0003']);

        $session = CourseSession::factory()->create(['course_session_number' => '']);

        $this->assertSame('CUSTOM-1', $explicit->course_session_number);
        $this->assertSame('COURSE.10000', $session->course_session_number);
    }
}
