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

        $course_sessions = CourseSession::with(['course.questionnaireTemplate', 'instructor', 'feedbackForms'])
            ->orderBy('created_at')
            ->get();

        $courses = $course_sessions->pluck('course')->unique('id')->sortBy('name')->values();
        $instructors = $course_sessions->pluck('instructor')->unique('id')->sortBy('name')->values();

        return view('overview', [
            'course_sessions' => $course_sessions,
            'courses' => $courses,
            'instructors' => $instructors,
        ]);
    }
}
