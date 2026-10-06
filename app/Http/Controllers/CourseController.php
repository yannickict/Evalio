<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(): View
    {
        $courses = Course::with(['questionnaireTemplate', 'sessions.instructor'])->withCount('sessions')->orderBy('created_at')
            ->get();

        return view('pages.courses.index', ['courses' => $courses]);
    }
}
