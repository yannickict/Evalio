@extends('layouts.app')

@section('title', __('Sessions - Evalio'))

@section('content')
<main class="container py-4 py-lg-5" id="session-overview">
    <header class="mb-4">
        <div class="d-flex align-items-center flex-wrap gap-3 mb-2">
            <h1 class="h3 fw-semibold mb-0">{{ __('Sessions') }}</h1>
            <span class="badge rounded-1 text-body-secondary bg-body-secondary">
                {{ $courseSessions->count() }} {{ $courseSessions->count() === 1 ? __('session') : __('sessions') }}
            </span>
            <a class="btn btn-primary rounded-2 ms-auto" href="{{ route('sessions.create') }}">{{ __('New session') }}</a>
        </div>
        <p class="text-body-secondary mb-0">{{ __('All your course sessions, in one place.') }}</p>
    </header>

    <x-ui.status-alert />
    <x-ui.validation-errors :errors="$errors" />

    <x-ui.setup-guide current="sessions" />

    @can('view-session-filters')
    <div class="row g-4 mb-4">
        @foreach (['course' => 'Courses', 'instructor' => 'Instructors'] as $field => $label)
        <div class="col-12 col-md-6 col-xl-4">
            <label for="{{ $field }}-filter" class="form-label">{{ __($label) }}</label>
            <select id="{{ $field }}-filter" name="{{ $field }}" class="form-select">
                <option value="">{{ __($field === 'course' ? 'All courses' : 'All instructors') }}</option>
                @foreach (($field === 'course' ? $courses : $instructors) as $option)
                <option value="{{ $option->id }}">{{ $option->name }}</option>
                @endforeach
            </select>
        </div>
        @endforeach
    </div>

    @endcan

    <div class="collection-list row g-0 border rounded-2 overflow-hidden">
        <div class="col-12 d-none d-lg-block border-bottom bg-body-tertiary px-4 py-2">
            <div class="row small text-body-secondary">
                <div class="col-lg-2">{{ __('Course') }}</div>
                <div class="col-lg-2">{{ __('Instructor') }}</div>
                <div class="col-lg-3">{{ __('Course dates') }} / {{ __('Questionnaire') }}</div>
                <div class="col-lg-3">{{ __('Evaluation phase') }}</div>
                <div class="col-lg-2 text-end">{{ __('Details') }}</div>
            </div>
        </div>
        @forelse ($courseSessions as $course_session)
        <x-sessions.card :course-session="$course_session" />
        @empty
        <div class="col-12">
            <div class="card border rounded-2 p-5 text-center">
                <h2 class="h5">{{ __('No course sessions yet') }}</h2>
                <p class="text-body-secondary mb-0">{{ __('Course sessions will appear here once they are created.') }}</p>
                @can('view-workflow-guide')
                <p class="text-body-secondary mt-3 mb-2">{{ __('Each session needs a course with an assigned questionnaire.') }}</p>
                <a class="link-primary fw-semibold" href="{{ route('courses.index') }}">{{ __('Choose or create a course →') }}</a>
                @endcan
            </div>
        </div>
        @endforelse
    </div>
</main>
@endsection
