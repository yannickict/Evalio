@extends('layouts.app')

@section('title', 'Courses - Evalio')

@section('content')
<style>
    .course-card {
        border-top: .25rem solid var(--bs-success) !important;
        transition: transform .15s ease, box-shadow .15s ease;
    }

    .course-card:hover,
    .course-card:focus-within {
        transform: translateY(-3px);
        box-shadow: 0 .5rem 1.5rem rgba(0, 0, 0, .12) !important;
    }

    .course-card:focus-within {
        outline: 2px solid var(--bs-success);
        outline-offset: 3px;
    }

    @media (prefers-reduced-motion: reduce) {
        .course-card { transition: none; }
        .course-card:hover,
        .course-card:focus-within { transform: none; }
    }
</style>
<main class="container py-5">
    <header class="mb-4">
        <p class="text-success small fw-semibold text-uppercase mb-2">Course evaluations</p>
        <div class="d-flex align-items-center flex-wrap gap-3 mb-2">
            <h1 class="h2 fw-bold mb-0">Courses</h1>
            <span class="badge rounded-pill text-success-emphasis bg-success-subtle border border-success-subtle">
                {{ $courses->count() }} {{ $courses->count() === 1 ? 'course' : 'courses' }}
            </span>
            <a class="btn btn-success rounded-3 ms-auto" href="{{ route('courses.create') }}">New course</a>
        </div>
        <p class="text-body-secondary mb-0">Your courses, session counts, and assigned questionnaires.</p>
    </header>

    <div class="row g-4">
        @forelse ($courses as $course)
        <div class="col-12 col-md-6 col-xl-4">
            <article class="course-card card h-100 border-0 rounded-4 shadow-sm">
                <div class="card-body p-4">
                    <p class="small text-success fw-semibold text-uppercase mb-2">Course</p>
                    <h2 class="h4 fw-bold text-break mb-3">{{ $course->name }}</h2>
                    <span class="badge rounded-pill bg-success-subtle text-success-emphasis">
                        {{ $course->sessions_count }} {{ $course->sessions_count === 1 ? 'session' : 'sessions' }}
                    </span>
                </div>
                <div class="card-footer bg-transparent border-top px-4 py-3">
                    <p class="small text-body-secondary mb-1">Assigned questionnaire</p>
                    <p class="small fw-medium text-break mb-0">{{ $course->questionnaireTemplate?->name ?? 'No questionnaire assigned' }}</p>
                    <button class="btn btn-link link-success text-decoration-none fw-semibold small p-0 mt-3 stretched-link"
                        type="button" data-bs-toggle="modal" data-bs-target="#course-{{ $course->id }}"
                        aria-label="View course details for {{ $course->name }}">
                        View course <span aria-hidden="true">&rarr;</span>
                    </button>
                </div>
            </article>
        </div>
        <div class="modal fade" id="course-{{ $course->id }}" tabindex="-1"
            aria-labelledby="course-title-{{ $course->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content border-0 rounded-4 shadow">
                    <div class="modal-header px-4 py-3">
                        <div>
                            <p class="small text-success fw-semibold mb-1">Course details</p>
                            <h2 class="modal-title fs-4 fw-bold text-break" id="course-title-{{ $course->id }}">{{ $course->name }}</h2>
                        </div>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="small text-body-secondary mb-1">Assigned questionnaire</p>
                        <p class="fw-medium text-break mb-4">{{ $course->questionnaireTemplate?->name ?? 'No questionnaire assigned' }}</p>
                        <div class="d-flex align-items-center gap-2 mb-3">
                            <h3 class="h5 mb-0">Sessions</h3>
                            <span class="badge rounded-pill bg-success-subtle text-success-emphasis">{{ $course->sessions_count }}</span>
                        </div>
                        <ul class="list-group list-group-flush">
                            @forelse ($course->sessions->sortBy('start_date') as $courseSession)
                            <li class="list-group-item px-0 py-3">
                                <p class="small font-monospace text-break mb-1">{{ $courseSession->course_session_number }}</p>
                                <p class="fw-medium text-break mb-1">{{ $courseSession->instructor?->name ?? 'No instructor assigned' }}</p>
                                <p class="small text-body-secondary mb-0">
                                    <time datetime="{{ $courseSession->start_date->toDateString() }}">{{ $courseSession->start_date->format('d M Y') }}</time>
                                    &ndash;
                                    <time datetime="{{ $courseSession->end_date->toDateString() }}">{{ $courseSession->end_date->format('d M Y') }}</time>
                                </p>
                            </li>
                            @empty
                            <li class="list-group-item px-0 text-body-secondary">No sessions for this course yet.</li>
                            @endforelse
                        </ul>
                    </div>
                    <div class="modal-footer px-4 py-3">
                        <button class="btn btn-outline-secondary rounded-3" type="button" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="card border-0 rounded-4 shadow-sm p-4 p-md-5 text-center">
                <h2 class="h5">No courses yet</h2>
                <p class="text-body-secondary mb-0">Your courses will appear here once they are created.</p>
            </div>
        </div>
        @endforelse
    </div>
</main>
@endsection
