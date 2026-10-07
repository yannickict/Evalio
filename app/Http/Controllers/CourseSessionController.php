<?php

namespace App\Http\Controllers;

use App\Actions\SetEvaluationStatus;
use App\Http\Requests\StoreCourseSessionRequest;
use App\Http\Requests\UpdateCourseSessionRequest;
use App\Http\Requests\UpdateEvaluationStatusRequest;
use App\Models\Course;
use App\Models\CourseSession;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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

    public function edit(CourseSession $courseSession): View
    {
        return view('pages.sessions.edit', [
            'courseSession' => $courseSession->load(['course', 'questionnaireTemplate', 'instructor']),
            'instructors' => User::approvedInstructors()->orderBy('first_name')->orderBy('last_name')->get(),
        ]);
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

    public function update(UpdateCourseSessionRequest $request, CourseSession $courseSession): RedirectResponse
    {
        $courseSession->update($request->validated());

        return redirect()->route('sessions.index')->with('status', 'Session updated.');
    }

    public function delete(CourseSession $courseSession): RedirectResponse
    {
        Gate::authorize('delete-sessions');

        DB::transaction(function () use ($courseSession): void {
            $courseSession->feedbackForm()->delete();
            $courseSession->delete();
        });

        return redirect()->route('sessions.index')->with('status', 'Session deleted.');
    }
}
