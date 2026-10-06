<?php

namespace App\Http\Controllers;

use App\Models\Answer;
use App\Models\FeedbackForm;
use App\Models\Question;
use App\Models\QuestionnaireTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class QuestionnaireController extends Controller
{
    public function library(): View
    {
        return view('pages.questionnaires.index', [
            'templates' => QuestionnaireTemplate::withCount('questions')->latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'questions' => ['required', 'array', 'min:1', 'max:50'],
            'questions.*' => ['array:text,type,options'],
            'questions.*.text' => ['required', 'string', 'max:5000'],
            'questions.*.type' => ['required', 'in:single_choice,free_text'],
            'questions.*.options' => ['sometimes', 'array', 'max:20'],
            'questions.*.options.*' => ['nullable', 'string', 'max:1000'],
        ]);

        foreach ($data['questions'] as $index => $question) {
            if ($question['type'] === 'single_choice') {
                $request->validate([
                    "questions.$index.options" => ['required', 'array', 'min:2', 'max:20'],
                    "questions.$index.options.*" => ['required', 'string', 'max:1000'],
                ]);
            }
        }

        DB::transaction(function () use ($data) {
            $template = QuestionnaireTemplate::create(['name' => trim($data['name'])]);

            foreach (array_values($data['questions']) as $index => $questionData) {
                $question = Question::create([
                    'question_text' => trim($questionData['text']),
                    'type' => $questionData['type'],
                    'allows_comment' => false,
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

    public function index(Request $request): View
    {
        $data = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $form = FeedbackForm::with('courseSession.course.questionnaireTemplate')->where('code', $data['code'])
            ->firstOrFail();

        $template = $form->courseSession->course->questionnaireTemplate;

        abort_if($template === null, 404, 'No questionnaire assigned.');

        return view('pages.questionnaires.respond', [
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

    public function create(): View
    {
        return view('pages.questionnaires.create', [
            'draft' => [
                'name' => '',
                'questions' => [
                    [
                        'text' => '',
                        'type' => 'single_choice',
                        'options' => ['', ''],
                    ],
                ],
            ],
        ]);
    }

    public function preview(Request $request): View
    {
        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'questions' => ['required', 'array', 'min:1', 'max:50'],
            'questions.*' => ['array:text,type,options'],
            'questions.*.text' => ['nullable', 'string', 'max:5000'],
            'questions.*.type' => ['required', 'in:single_choice,free_text'],
            'questions.*.options' => ['sometimes', 'array', 'max:20'],
            'questions.*.options.*' => ['nullable', 'string', 'max:1000'],
            'action' => ['required', 'string', 'regex:/\A(?:refresh|add_question|add_option:[0-9]+|remove_question:[0-9]+|remove_option:[0-9]+:[0-9]+)\z/'],
        ]);

        $draft = [
            'name' => $data['name'] ?? '',
            'questions' => array_map(
                fn (array $question) => [
                    'text' => $question['text'] ?? '',
                    'type' => $question['type'],
                    'options' => array_values(
                        $question['options'] ?? ['', '']
                    ),
                ],
                array_values($data['questions'])
            ),
        ];

        if ($data['action'] === 'add_question') {
            if (count($draft['questions']) >= 50) {
                throw ValidationException::withMessages([
                    'questions' => 'You can add up to 50 questions.',
                ]);
            }

            $draft['questions'][] = [
                'text' => '',
                'type' => 'single_choice',
                'options' => ['', ''],
            ];
        } elseif (str_starts_with($data['action'], 'add_option:')) {
            $index = (int) substr($data['action'], strlen('add_option:'));

            if (! isset($draft['questions'][$index])) {
                throw ValidationException::withMessages([
                    'questions' => 'This question does not exist.',
                ]);
            }

            if ($draft['questions'][$index]['type'] !== 'single_choice') {
                throw ValidationException::withMessages([
                    'questions' => 'Only single-choice questions have answer options.',
                ]);
            }

            if (count($draft['questions'][$index]['options']) >= 20) {
                throw ValidationException::withMessages([
                    'questions' => 'A question can have up to 20 options.',
                ]);
            }

            $draft['questions'][$index]['options'][] = '';
        } elseif (str_starts_with($data['action'], 'remove_question:')) {
            $index = (int) substr($data['action'], strlen('remove_question:'));

            if (! isset($draft['questions'][$index]) || count($draft['questions']) <= 1) {
                throw ValidationException::withMessages([
                    'questions' => 'Keep at least one question and choose an existing question to remove.',
                ]);
            }

            unset($draft['questions'][$index]);
            $draft['questions'] = array_values($draft['questions']);
        } elseif (str_starts_with($data['action'], 'remove_option:')) {
            [, $questionIndex, $optionIndex] = explode(':', $data['action']);
            $questionIndex = (int) $questionIndex;
            $optionIndex = (int) $optionIndex;

            if (! isset($draft['questions'][$questionIndex])
                || $draft['questions'][$questionIndex]['type'] !== 'single_choice'
                || ! array_key_exists($optionIndex, $draft['questions'][$questionIndex]['options'])
                || count($draft['questions'][$questionIndex]['options']) <= 2) {
                throw ValidationException::withMessages([
                    'questions' => 'Choose an existing single-choice option to remove and keep at least two options.',
                ]);
            }

            unset($draft['questions'][$questionIndex]['options'][$optionIndex]);
            $draft['questions'][$questionIndex]['options'] = array_values($draft['questions'][$questionIndex]['options']);
        }

        return view('pages.questionnaires.create', ['draft' => $draft]);
    }
}
