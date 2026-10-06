<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SessionController extends Controller
{
    public function index(): View
    {
        $courses = Course::orderBy('name')->get();
        $instructors = User::where('is_approved', true)
            ->whereHas('role', fn ($query) => $query->where('name', 'instructor'))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return view('pages.sessions.create', [
            'courses' => $courses,
            'instructors' => $instructors,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'instructor_id' => ['required', 'integer', 'exists:users,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $validInstructor = User::whereKey($data['instructor_id'])
            ->where('is_approved', true)
            ->whereHas('role', fn ($query) => $query->where('name', 'instructor'))
            ->exists();

        if (! $validInstructor) {
            throw ValidationException::withMessages([
                'instructor_id' => 'Choose an approved instructor.',
            ]);
        }

        CourseSession::create($data);

        return redirect()->route('overview')
            ->with('status', 'Session created.');
    }
}
