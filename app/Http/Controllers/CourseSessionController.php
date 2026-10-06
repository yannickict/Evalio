<?php

namespace App\Http\Controllers;

use App\Actions\SetEvaluationStatus;
use App\Http\Requests\StoreCourseSessionRequest;
use App\Http\Requests\UpdateEvaluationStatusRequest;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseSessionController extends Controller
{
    public function index(Request $request): View
    {
        $query = CourseSession::with([
            'course',
            'questionnaireTemplate',
            'instructor',
            'feedbackForm',
        ]);

        if ($request->user()->role?->name === 'instructor') {
            $query->where('instructor_id', $request->user()->id);
        }

        $courseSessions = $query
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

    public function create(Request $request): View
    {
        $courses = Course::orderBy('name')->get();

        $query = User::approvedInstructors();

        if ($request->user()->role?->name === 'instructor') {
            $query->whereKey($request->user()->id);
        }

        $instructors = $query
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

    public function updateEvaluationStatus(
        UpdateEvaluationStatusRequest $request,
        CourseSession $courseSession,
        SetEvaluationStatus $setStatus
    ): RedirectResponse {
        $data = $request->validated();

        $setStatus->handle($courseSession, $data['evaluation_status']);

        return redirect()->route('sessions.index')
            ->with('status', $data['evaluation_status'] === 'open'
                ? 'Evaluation opened.'
                : 'Evaluation closed.');
    }
}
