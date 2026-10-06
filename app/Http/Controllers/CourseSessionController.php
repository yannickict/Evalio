<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseSessionRequest;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CourseSessionController extends Controller
{
    public function index(): View
    {
        $courseSessions = CourseSession::with(['course.questionnaireTemplate', 'instructor', 'feedbackForm'])
            ->orderBy('created_at')
            ->get();

        $courses = $courseSessions->pluck('course')->unique('id')->sortBy('name')->values();
        $instructors = $courseSessions->pluck('instructor')->unique('id')->sortBy('name')->values();

        return view('pages.sessions.index', [
            'courseSessions' => $courseSessions,
            'courses' => $courses,
            'instructors' => $instructors,
        ]);
    }

    public function create(): View
    {
        $courses = Course::orderBy('name')->get();
        $instructors = User::approvedInstructors()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return view('pages.sessions.create', [
            'courses' => $courses,
            'instructors' => $instructors,
        ]);
    }

    public function store(StoreCourseSessionRequest $request): RedirectResponse
    {
        CourseSession::create($request->validated());

        return redirect()->route('sessions.index')->with('status', 'Session created.');
    }
}
