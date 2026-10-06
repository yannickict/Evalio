<?php

namespace App\Http\Controllers;

use App\Models\Course;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(): View
    {
        abort_unless(Auth::user()?->role !== null, 403);

        $courses = Course::with(['questionnaireTemplate', 'sessions.instructor'])->withCount('sessions')->orderBy('created_at')
            ->get();

        return view('pages.courses.index', ['courses' => $courses]);
    }
}
