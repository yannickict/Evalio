<?php

namespace App\Http\Controllers;

use App\Models\CourseSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class OverviewController extends Controller
{
    public function index(): View
    {
        abort_unless(Auth::user()?->role !== null, 403);

        $course_sessions = CourseSession::where('evaluation_status', 'closed')
            ->with(['course.questionnaireTemplate', 'instructor', 'feedbackForms'])
            ->orderBy('created_at')
            ->get();

        return view('overview', ['course_sessions' => $course_sessions]);
    }
}
