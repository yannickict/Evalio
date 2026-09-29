<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\FeedbackForm;
use App\Models\QuestionnaireTemplate;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $template = QuestionnaireTemplate::where('name', 'Standard Course Evaluation')->firstOrFail();
            $instructors = User::factory()->count(5)->approved()->create();

            User::factory()->count(3)->unverified()->create();

            $courses = Course::factory()->count(5)->for($template, 'questionnaireTemplate')->create();

            foreach ($courses as $index => $course) {
                foreach ([-14, 0, 14] as $days) {
                    $session = CourseSession::factory()
                        ->for($course)
                        ->for($instructors[$index], 'instructor')
                        ->create([
                            'start_date' => today()->addDays($days),
                            'end_date' => today()->addDays($days + 2),
                        ]);

                    FeedbackForm::factory()->count(3)->for($session)->create();
                }
            }
        });
    }
}
