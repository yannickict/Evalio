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
                    do {
                        $code = (string) random_int(100000, 999999);
                    } while (CourseSession::where('code', $code)->exists());

                    $session = CourseSession::factory()
                        ->for($course)
                        ->for($instructors[$index], 'instructor')
                        ->create([
                            'code' => $code,
                            'start_date' => today()->addDays($days),
                            'end_date' => today()->addDays($days + 2),
                        ]);

                    FeedbackForm::factory()->count(3)->for($session)->create();
                }
            }
        });
    }
}
