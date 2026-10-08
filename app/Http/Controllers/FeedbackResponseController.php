<?php

namespace App\Http\Controllers;

use App\Http\Requests\FeedbackCodeRequest;
use App\Http\Requests\StoreFeedbackResponseRequest;
use App\Models\Answer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FeedbackResponseController extends Controller
{
    public function show(FeedbackCodeRequest $request): View
    {
        return view('pages.questionnaires.respond', ['questions' => $request->template()->questions]);
    }

    public function store(StoreFeedbackResponseRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $form = $request->form();
        $questions = $request->template()->questions->keyBy('id');

        DB::transaction(function () use ($data, $form, $questions): void {
            foreach ($data['answers'] ?? [] as $questionId => $answer) {
                $question = $questions->get($questionId);

                if ($question === null) {
                    continue;
                }

                Answer::create([
                    'feedback_form_id' => $form->id,
                    'question_id' => $question->id,
                    'question_option_id' => $question->type === 'single_choice' ? $answer : null,
                    'answer_text' => $question->type === 'free_text' ? $answer : null,
                    'comment' => $data['answers_comment'][$questionId] ?? null,
                ]);
            }
        });

        return redirect()->route('home')->with('status', __('Thank you! Your feedback has been submitted.'));
    }
}
