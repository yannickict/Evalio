<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CourseSession> */
class CourseSessionFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'instructor_id' => User::factory(),
            'start_date' => today(),
            'end_date' => today()->addDays(2),
            'evaluation_status' => null,
        ];
    }
}
