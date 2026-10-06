<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseRequest;
use App\Models\Course;
use App\Models\QuestionnaireTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(): View
    {
        $courses = Course::with(['questionnaireTemplate', 'sessions.instructor'])->withCount('sessions')->orderBy('created_at')
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
