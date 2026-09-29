@extends('layouts.app')

@section('title', 'Overview - Feedback')

@section('content')
    <main class="container py-5">
        <header class="mb-4">
            <p class="text-success small fw-semibold text-uppercase mb-2">Course evaluations</p>
            <div class="d-flex align-items-center flex-wrap gap-3 mb-2">
                <h1 class="h2 fw-bold mb-0">Overview</h1>
                <span class="badge rounded-pill text-success-emphasis bg-success-subtle border border-success-subtle">
                    {{ $course_sessions->count() }} closed {{ $course_sessions->count() === 1 ? 'session' : 'sessions' }}
                </span>
            </div>
            <p class="text-body-secondary mb-0">Your course sessions with closed evaluations, all in one place.</p>
        </header>

        <div class="row g-4">
            @forelse ($course_sessions as $course_session)
                <div class="col-12 col-md-6 col-xl-4">
                    <article class="card h-100 border-0 rounded-4 shadow-sm">
                        <div class="card-body p-4">
                            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                                <span class="small text-body-secondary font-monospace text-break">{{ $course_session->course_session_number }}</span>
                                <span class="badge rounded-pill bg-success-subtle text-success-emphasis">Evaluation closed</span>
                            </div>
                            <h2 class="h5 fw-semibold text-break mb-4">{{ $course_session->course->name }}</h2>
                            <dl class="mb-0">
                                <dt class="small text-body-secondary fw-normal">Instructor</dt>
                                <dd class="fw-medium text-break mb-3">{{ $course_session->instructor->name }}</dd>
                                <dt class="small text-body-secondary fw-normal">Course dates</dt>
                                <dd class="fw-medium mb-0">
                                    <time datetime="{{ $course_session->start_date->toDateString() }}">{{ $course_session->start_date->format('d M Y') }}</time>
                                    <span class="text-body-secondary">&ndash;</span>
                                    <time datetime="{{ $course_session->end_date->toDateString() }}">{{ $course_session->end_date->format('d M Y') }}</time>
                                </dd>
                            </dl>
                        </div>
                        <div class="card-footer bg-transparent border-top px-4 py-3">
                            <p class="small text-body-secondary mb-1">Questionnaire</p>
                            <p class="small fw-medium text-break mb-0">{{ $course_session->course->questionnaireTemplate?->name ?? 'No questionnaire assigned' }}</p>
                        </div>
                    </article>
                </div>
            @empty
                <div class="col-12">
                    <div class="card border-0 rounded-4 shadow-sm p-5 text-center">
                        <h2 class="h5">No closed evaluations yet</h2>
                        <p class="text-body-secondary mb-0">Course sessions will appear here once their evaluation is marked closed.</p>
                    </div>
                </div>
            @endforelse
        </div>
    </main>
@endsection
