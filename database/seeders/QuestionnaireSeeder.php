<?php

namespace Database\Seeders;

use App\Models\Question;
use App\Models\QuestionnaireTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class QuestionnaireSeeder extends Seeder
{
    public function run(): void
    {
        // Wording and order supplied in the clarified project specification.
        $questions = [
            ['My prerequisites for this course were ...', ['Very good', 'Good', 'Satisfactory', 'Low']],
            ['The quality of the exercises in the course was ...', ['Very good', 'Good', 'Satisfactory', 'Low']],
            ['I perceived the course atmosphere as ...', ['Motivating', 'Pleasant', 'Good', 'Exhausting', 'Boring']],
            ['The instructor conducted the course ...', ['Very pleasantly', 'Pleasantly', 'Only partially pleasantly', 'Unpleasantly']],
            ['The instructor was prepared in terms of content and organization ...', ['Very well prepared', 'Well prepared', 'Only partially prepared', 'Unprepared']],
            ['Would you recommend the instructor?', ['Yes', 'No']],
            ["The instructor's professional competence was ...", ['Very competent', 'Competent', 'Only partially competent', 'Incompetent']],
            ['Would you recommend this course?', ['Yes', 'No']],
            ['Enter two or three short points that come to mind about this course.', []],
            ['If you had to give an overall grade, what grade would you give the training center?', ['Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5']],
        ];

        DB::transaction(function () use ($questions) {
            $template = QuestionnaireTemplate::firstOrCreate(['name' => 'Standard Course Evaluation']);
            $template->questions()->detach();

            foreach ($questions as $index => [$text, $options]) {
                $question = Question::updateOrCreate(['question_text' => $text], [
                    'type' => $options === [] ? 'free_text' : 'single_choice',
                    'allows_comment' => $options !== [],
                ]);

                $question->options()->whereNotIn('option_text', $options)->delete();
                foreach ($options as $option) {
                    $question->options()->firstOrCreate(['option_text' => $option]);
                }

                $template->questions()->attach($question, ['position' => $index + 1]);
            }
        });
    }
}
