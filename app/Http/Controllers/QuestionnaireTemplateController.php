<?php

namespace App\Http\Controllers;

use App\Http\Requests\PreviewQuestionnaireRequest;
use App\Http\Requests\StoreQuestionnaireRequest;
use App\Models\Answer;
use App\Models\CourseSession;
use App\Models\Question;
use App\Models\QuestionnaireTemplate;
use App\Support\QuestionnaireDraft;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class QuestionnaireTemplateController extends Controller
{
    public function index(Request $request): View
    {
        $templates = QuestionnaireTemplate::with([
            'questions.options',
            'courses',
            'sessions' => function ($query) use ($request): void {
                if ($request->user()->hasRole('instructor')) {
                    $query->where('instructor_id', $request->user()->id);
                }

                $query->with('course', 'feedbackForm.answers');
            },
        ])
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

    public function duplicate(QuestionnaireTemplate $template): View
    {
        $template->load('questions.options');

        return view('pages.questionnaires.create', ['draft' => [
            'name' => $template->name.__(' (copy)'),
            'questions' => $template->questions->map(fn (Question $question): array => [
                'text' => $question->question_text,
                'type' => $question->type,
                'allows_comment' => $question->allows_comment,
                'options' => $question->type === 'single_choice'
                    ? $question->options->pluck('option_text')->all()
                    : ['', ''],
            ])->values()->all() ?: [QuestionnaireDraft::emptyQuestion()],
        ]]);
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

        return redirect()->route('questionnaires.index')->with('status', __('Questionnaire saved.'));
    }

    public function preview(PreviewQuestionnaireRequest $request, QuestionnaireDraft $draft): View
    {
        return view('pages.questionnaires.create', ['draft' => $draft->apply($request->validated())]);
    }

    public function delete(QuestionnaireTemplate $template): RedirectResponse
    {
        Gate::authorize('delete-questionnaires');

        return DB::transaction(function () use ($template): RedirectResponse {
            if (
                $template->courses()->exists()
                || CourseSession::where('questionnaire_template_id', $template->id)->exists()
            ) {
                return redirect()->route('questionnaires.index')->withErrors([
                    'questionnaire' => __('This questionnaire is assigned to a course or session and cannot be deleted.'),
                ]);
            }

            $questions = $template->questions()->get();
            $template->delete();

            foreach ($questions as $question) {
                if (
                    ! $question->questionnaireTemplates()->exists()
                    && ! Answer::where('question_id', $question->id)->exists()
                    && ! Answer::whereIn('question_option_id', $question->options()->select('id'))->exists()
                ) {
                    $question->delete();
                }
            }

            return redirect()->route('questionnaires.index')->with('status', __('Questionnaire deleted.'));
        });
    }
}
