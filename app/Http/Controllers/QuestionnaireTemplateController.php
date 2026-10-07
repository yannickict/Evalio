<?php

namespace App\Http\Controllers;

use App\Http\Requests\PreviewQuestionnaireRequest;
use App\Http\Requests\StoreQuestionnaireRequest;
use App\Models\Question;
use App\Models\Answer;
use App\Models\CourseSession;
use App\Models\QuestionnaireTemplate;
use App\Support\QuestionnaireDraft;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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

    public function delete(QuestionnaireTemplate $template): RedirectResponse
    {
        Gate::authorize('delete-questionnaires');

        return DB::transaction(function () use ($template): RedirectResponse {
            if ($template->courses()->exists()
                || CourseSession::where('questionnaire_template_id', $template->id)->exists()) {
                return redirect()->route('questionnaires.index')->withErrors([
                    'questionnaire' => 'This questionnaire is assigned to a course or session and cannot be deleted.',
                ]);
            }

            $questions = $template->questions()->get();
            $template->delete();

            foreach ($questions as $question) {
                if (! $question->questionnaireTemplates()->exists()
                    && ! Answer::where('question_id', $question->id)->exists()
                    && ! Answer::whereIn('question_option_id', $question->options()->select('id'))->exists()) {
                    $question->delete();
                }
            }

            return redirect()->route('questionnaires.index')->with('status', 'Questionnaire deleted.');
        });
    }
}
