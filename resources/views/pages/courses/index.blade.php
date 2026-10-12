@extends('layouts.app')

@section('title', __('Courses - Evalio'))

@section('content')
<main class="container py-4 py-lg-5" id="course-overview">
    <header class="mb-4">
        <div class="d-flex align-items-center flex-wrap gap-3 mb-2">
            <h1 class="h3 fw-semibold mb-0">{{ __('Courses') }}</h1>
            <span class="badge rounded-1 text-body-secondary bg-body-secondary">
                {{ $courses->count() }} {{ $courses->count() === 1 ? __('course') : __('courses') }}
            </span>
            @can('create-courses')
            <a class="btn btn-primary rounded-2 ms-auto" href="{{ route('courses.create') }}">{{ __('New course') }}</a>
            @endcan
        </div>
        <p class="text-body-secondary mb-0">{{ __('Your courses, session counts, and assigned questionnaires.') }}</p>
    </header>

    <x-ui.status-alert />

    <x-ui.setup-guide current="courses" />

    <div class="collection-list row g-0 border rounded-2 overflow-hidden">
        @forelse ($courses as $course)
        <x-courses.card :course="$course" />
        @empty
        <div class="col-12">
            <div class="card border rounded-2 p-4 p-md-5 text-center">
                <h2 class="h5">{{ __('No courses yet') }}</h2>
                <p class="text-body-secondary mb-3">{{ __('Create a questionnaire first, then assign it when you create a course.') }}</p>
                <a class="link-primary fw-semibold" href="{{ route('questionnaires.index') }}">{{ __('Choose or create a questionnaire →') }}</a>
            </div>
        </div>
        @endforelse
    </div>
</main>
@endsection
