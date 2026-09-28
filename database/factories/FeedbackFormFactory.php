<?php

namespace Database\Factories;

use App\Models\CourseSession;
use App\Models\FeedbackForm;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FeedbackForm> */
class FeedbackFormFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'course_session_id' => CourseSession::factory(),
        ];
    }
}
