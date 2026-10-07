<?php

namespace App\Http\Controllers;

use App\Models\CourseSession;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\View\View;

class EvaluationResultsController extends Controller
{
    public function show(CourseSession $courseSession): View
    {
        $courseSession->load([
            'course',
            'questionnaireTemplate.questions.options',
            'feedbackForm.answers',
        ]);

        $answers = $courseSession->feedbackForm->answers ?? collect();
        $results = ($courseSession->questionnaireTemplate->questions ?? collect())->map(function (Question $question) use ($answers): array {
            $questionAnswers = $answers->where('question_id', $question->id);

            return [
                'question' => $question,
                'options' => $question->options->map(fn (QuestionOption $option): array => [
                    'text' => $option->option_text,
                    'count' => $questionAnswers->where('question_option_id', $option->id)->count(),
                ]),
                'responses' => $questionAnswers->pluck('answer_text')->filter(fn ($text): bool => $text !== null && trim($text) !== ''),
                'comments' => $questionAnswers->pluck('comment')->filter(fn ($text): bool => $text !== null && trim($text) !== ''),
            ];
        });

        return view('pages.sessions.results', [
            'courseSession' => $courseSession,
            'results' => $results,
            'hasAnswers' => $answers->isNotEmpty(),
        ]);
    }
}
