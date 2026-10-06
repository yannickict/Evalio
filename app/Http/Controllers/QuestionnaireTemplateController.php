<?php

namespace App\Http\Controllers;

use App\Http\Requests\PreviewQuestionnaireRequest;
use App\Http\Requests\StoreQuestionnaireRequest;
use App\Models\Question;
use App\Models\QuestionnaireTemplate;
use App\Support\QuestionnaireDraft;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class QuestionnaireTemplateController extends Controller
{
    public function index(): View
    {
        $templates = QuestionnaireTemplate::with('questions.options')
            ->withCount('questions')
            ->latest()
            ->get();

        return view('pages.questionnaires.index', [
            'templates' => $templates,
        ]);
    }

    public function create(): View
    {
        return view('pages.questionnaires.create', ['draft' => QuestionnaireDraft::empty()]);
    }

    public function store(StoreQuestionnaireRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            $template = QuestionnaireTemplate::create(['name' => trim($data['name'])]);

            foreach (array_values($data['questions']) as $index => $questionData) {
                $question = Question::create([
                    'question_text' => trim($questionData['text']),
                    'type' => $questionData['type'],
                    'allows_comment' => (bool) ($questionData['allows_comment'] ?? false),
                ]);
                $template->questions()->attach($question->id, ['position' => $index + 1]);

                if ($questionData['type'] === 'single_choice') {
                    foreach ($questionData['options'] as $option) {
                        $question->options()->create(['option_text' => trim($option)]);
                    }
                }
            }
        });

        return redirect()->route('questionnaires.index')->with('status', 'Questionnaire saved.');
    }

    public function preview(PreviewQuestionnaireRequest $request, QuestionnaireDraft $draft): View
    {
        return view('pages.questionnaires.create', ['draft' => $draft->apply($request->validated())]);
    }
}
