<?php

namespace Database\Seeders;

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\FeedbackForm;
use App\Models\QuestionnaireTemplate;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            User::updateOrCreate(['email' => 'admin@example.com'], [
                'first_name' => 'Demo',
                'last_name' => 'Admin',
                'role_id' => Role::where('name', 'admin')->firstOrFail()->id,
                'is_approved' => true,
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
            ]);

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
                            'evaluation_status' => $days < 0 ? 'closed' : null,
                        ]);

                    for ($i = 0; $i < 3; $i++) {
                        do {
                            $code = (string) random_int(100000, 999999);
                        } while (FeedbackForm::where('code', $code)->exists());
                        FeedbackForm::factory()->for($session)->create(['code' => $code]);
                    }
                }
            }
        });
    }
}
