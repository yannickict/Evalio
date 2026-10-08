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
        // German standard content, independent of the selected interface language.
        $questions = [
            ['Meine Vorkenntnisse für diesen Lehrgang waren ...', ['Sehr gut', 'Gut', 'Befriedigend', 'Gering']],
            ['Die Qualität der Übungen im Lehrgang war ...', ['Sehr gut', 'Gut', 'Befriedigend', 'Gering']],
            ['Die Lehrgangsatmosphäre empfand ich als ...', ['Motivierend', 'Angenehm', 'Gut', 'Anstrengend', 'Langweilig']],
            ['Der Dozent gestaltete den Lehrgang ...', ['Sehr angenehm', 'Angenehm', 'Nur teilweise angenehm', 'Unangenehm']],
            ['Der Dozent war inhaltlich und organisatorisch ...', ['Sehr gut vorbereitet', 'Gut vorbereitet', 'Nur teilweise vorbereitet', 'Unvorbereitet']],
            ['Würden Sie den Dozenten weiterempfehlen?', ['Ja', 'Nein']],
            ['Der Dozent war fachlich ...', ['Sehr kompetent', 'Kompetent', 'Nur teilweise kompetent', 'Inkompetent']],
            ['Würden Sie diesen Lehrgang weiterempfehlen?', ['Ja', 'Nein']],
            ['Geben Sie zwei, drei kurze Stichpunkte, die Ihnen zu diesem Lehrgang einfallen!', []],
            ['Wenn Sie eine Gesamtnote vergeben müssten - welche Note würden Sie dem Schulungszentrum geben?', ['Note 1', 'Note 2', 'Note 3', 'Note 4', 'Note 5']],
        ];

        DB::transaction(function () use ($questions) {
            $template = QuestionnaireTemplate::firstOrCreate(['name' => 'Standard-Feedbackbogen']);
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
