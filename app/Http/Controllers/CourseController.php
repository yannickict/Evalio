<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseRequest;
use App\Models\Course;
use App\Models\QuestionnaireTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(Request $request): View
    {
        $visibleSessions = function ($query) use ($request): void {
            if ($request->user()->role?->name === 'instructor') {
                $query->where('instructor_id', $request->user()->id);
            }
        };

        $courses = Course::with([
            'questionnaireTemplate',
            'sessions' => function ($query) use ($visibleSessions): void {
                $visibleSessions($query);
                $query->with('instructor');
            },
        ])
            ->withCount(['sessions' => $visibleSessions])
            ->orderBy('created_at')
            ->get();

        return view('pages.courses.index', ['courses' => $courses]);
    }

    public function create(): View
    {
        $templates = QuestionnaireTemplate::orderBy('name')
            ->get();

        return view('pages.courses.create', ['templates' => $templates]);
    }

    public function store(StoreCourseRequest $request): RedirectResponse
    {
        Course::create($request->validated());

        return redirect()->route('courses.index')
            ->with('status', 'Course created.');
    }
}
