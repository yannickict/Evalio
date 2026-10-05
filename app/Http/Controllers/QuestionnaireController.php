<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\FeedbackForm;
use App\Models\Question;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestionnaireController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $form = FeedbackForm::with('courseSession.course.questionnaireTemplate')->where('code', $data['code'])
            ->firstOrFail();

        $template = $form->courseSession->course->questionnaireTemplate;

        abort_if($template === null, 404, 'No questionnaire assigned.');

        return view('questionnaire', [
            'questions' => $template->questions()
                ->get(),
        ]);
    }

    public function submit(Request $request): RedirectResponse
    {
        $answers = $request->input('answers', []);
        $comments = $request->input('answers_comment', []);
        $code = $request->query('code');

        $form = FeedbackForm::with('courseSession.course.questionnaireTemplate')->where('code', $code)
            ->firstOrFail();
        foreach ($answers as $questionId => $answer) {
            $question = Question::query()->whereKey($questionId)->firstOrFail();
            $questionType = $question->type;
            if ($questionType == 'single_choice') {
                Answer::create([
                    'feedback_form_id' => $form->id,
                    'question_id' => $questionId,
                    'question_option_id' => $answer,
                    'answer_text' => null,
                    'comment' => $comments[$questionId] ?? null,
                ]);
            } elseif ($questionType == 'free_text') {
                Answer::create([
                    'feedback_form_id' => $form->id,
                    'question_id' => $questionId,
                    'question_option_id' => null,
                    'answer_text' => $answer,
                    'comment' => $comments[$questionId] ?? null,
                ]);
            }
        }

        return redirect()->route('home')
            ->with('status', 'Thank you! Your feedback has been submitted.');
    }
}
