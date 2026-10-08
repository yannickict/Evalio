@extends('layouts.app')

@section('title', __('Courses - Evalio'))

@section('content')
<main class="container py-5" id="course-overview">
    <header class="mb-4">
        <p class="text-success small fw-semibold text-uppercase mb-2">{{ __('Course evaluations') }}</p>
        <div class="d-flex align-items-center flex-wrap gap-3 mb-2">
            <h1 class="h2 fw-bold mb-0">{{ __('Courses') }}</h1>
            <span class="badge rounded-pill text-success-emphasis bg-success-subtle border border-success-subtle">
                {{ $courses->count() }} {{ $courses->count() === 1 ? 'course' : 'courses' }}
            </span>
            @can('create-courses')
            <a class="btn btn-success rounded-3 ms-auto" href="{{ route('courses.create') }}">{{ __('New course') }}</a>
            @endcan
        </div>
        <p class="text-body-secondary mb-0">{{ __('Your courses, session counts, and assigned questionnaires.') }}</p>
    </header>

    <x-ui.status-alert />

    <x-ui.setup-guide current="courses" />

    <div class="row g-4">
        @forelse ($courses as $course)
        <x-courses.card :course="$course" />
        @empty
        <div class="col-12">
            <div class="card border-0 rounded-4 shadow-sm p-4 p-md-5 text-center">
                <h2 class="h5">{{ __('No courses yet') }}</h2>
                <p class="text-body-secondary mb-3">{{ __('Create a questionnaire first, then assign it when you create a course.') }}</p>
                <a class="link-success fw-semibold" href="{{ route('questionnaires.index') }}">{{ __('Choose or create a questionnaire →') }}</a>
            </div>
        </div>
        @endforelse
    </div>
</main>
@endsection
