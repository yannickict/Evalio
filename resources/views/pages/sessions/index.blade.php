@extends('layouts.app')

@section('title', 'Sessions - Evalio')

@section('content')
<main class="container py-5" id="session-overview">
    <header class="mb-4">
        <p class="text-success small fw-semibold text-uppercase mb-2">Course evaluations</p>
        <div class="d-flex align-items-center flex-wrap gap-3 mb-2">
            <h1 class="h2 fw-bold mb-0">Sessions</h1>
            <span class="badge rounded-pill text-success-emphasis bg-success-subtle border border-success-subtle">
                {{ $courseSessions->count() }} {{ $courseSessions->count() === 1 ? 'session' : 'sessions' }}
            </span>
            <a class="btn btn-success rounded-3 ms-auto" href="{{ route('sessions.create') }}">New session</a>
        </div>
        <p class="text-body-secondary mb-0">All your course sessions, in one place.</p>
    </header>

    <x-ui.status-alert />

    <x-ui.setup-guide current="sessions" />

    @can('view-session-filters')
    <div class="row g-4 mb-4">
        @foreach (['course' => 'Courses', 'instructor' => 'Instructors'] as $field => $label)
        <div class="col-12 col-md-6 col-xl-4">
            <label for="{{ $field }}-filter" class="form-label">{{ $label }}</label>
            <select id="{{ $field }}-filter" name="{{ $field }}" class="form-select">
                <option value="">All {{ strtolower($label) }}</option>
                @foreach (($field === 'course' ? $courses : $instructors) as $option)
                <option value="{{ $option->id }}">{{ $option->name }}</option>
                @endforeach
            </select>
        </div>
        @endforeach
    </div>

    @endcan

    <div class="row g-4">
        @forelse ($courseSessions as $course_session)
        <x-sessions.card :course-session="$course_session" />
        @empty
        <div class="col-12">
            <div class="card border-0 rounded-4 shadow-sm p-5 text-center">
                <h2 class="h5">No course sessions yet</h2>
                <p class="text-body-secondary mb-0">Course sessions will appear here once they are created.</p>
                @can('view-workflow-guide')
                <p class="text-body-secondary mt-3 mb-2">Each session needs a course with an assigned questionnaire.</p>
                <a class="link-success fw-semibold" href="{{ route('courses.index') }}">Choose or create a course &rarr;</a>
                @endcan
            </div>
        </div>
        @endforelse
    </div>
</main>
@endsection
