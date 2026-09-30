<?php

namespace App\Http\Controllers;

use App\Models\CourseSession;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QuestionaireController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $session = CourseSession::where('code', $data['code'])
            ->firstOrFail();

        $template = $session->course->questionnaireTemplate;

        abort_if($template === null, 404, 'No questionnaire assigned.');

        return view('questionaire', [
            'questions' => $template->questions()
                ->get(),
        ]);
    }

    public function submit(): void {}
}
