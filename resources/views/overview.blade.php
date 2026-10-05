@extends('layouts.app')

@section('title', 'Overview - Feedback')

@section('content')
<style>
    .overview-filter-clear {
        position: absolute;
        top: 50%;
        right: .5rem;
        transform: translateY(-50%);
        width: 1.5rem;
        height: 1.5rem;
        padding: 0;
        background-size: .625rem;
    }

    .session-card {
        transition: transform .15s ease, box-shadow .15s ease;
    }

    .session-card:hover,
    .session-card:focus-within {
        transform: translateY(-3px);
        box-shadow: 0 .5rem 1.5rem rgba(0, 0, 0, .12) !important;
    }

    .session-card:focus-within {
        outline: 2px solid var(--bs-success);
        outline-offset: 3px;
    }

    @media (prefers-reduced-motion: reduce) {
        .session-card {
            transition: none;
        }

        .session-card:hover,
        .session-card:focus-within {
            transform: none;
        }
    }
</style>
<main class="container py-5">
    <header class="mb-4">
        <p class="text-success small fw-semibold text-uppercase mb-2">Course evaluations</p>
        <div class="d-flex align-items-center flex-wrap gap-3 mb-2">
            <h1 class="h2 fw-bold mb-0">Overview</h1>
            <span class="badge rounded-pill text-success-emphasis bg-success-subtle border border-success-subtle">
                {{ $course_sessions->count() }} {{ $course_sessions->count() === 1 ? 'session' : 'sessions' }}
            </span>
        </div>
        <p class="text-body-secondary mb-0">All your course sessions, in one place.</p>
    </header>

    <div class="row g-3 mb-4">
        @foreach (['course' => 'Courses', 'instructor' => 'Instructors'] as $field => $label)
        <div class="col-12 col-md-6 col-lg-4">
            <label for="{{ $field }}-filter" class="form-label">{{ $label }}</label>
            <div class="position-relative" data-searchable-dropdown>
                <input id="{{ $field }}-filter" name="{{ $field }}" type="text"
                    class="form-control pe-5" placeholder="All {{ strtolower($label) }}" autocomplete="off"
                    role="combobox" aria-autocomplete="list" aria-expanded="false"
                    aria-controls="{{ $field }}-options">
                <button type="button" class="btn-close overview-filter-clear"
                    data-clear-search aria-label="Clear {{ strtolower($label) }} search" hidden></button>
                <div id="{{ $field }}-options" class="dropdown-menu w-100 overflow-auto"
                    role="listbox" aria-label="{{ $label }}" style="max-height: 16rem;">
                    @foreach (($field === 'course' ? $courses : $instructors) as $option)
                    <button id="{{ $field }}-option-{{ $option->id }}" type="button"
                        class="dropdown-item text-wrap" role="option" aria-selected="false"
                        tabindex="-1">{{ $option->name }}</button>
                    @endforeach
                    <span class="dropdown-item-text text-body-secondary" data-no-results hidden>No matches found</span>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="row g-4">
        @forelse ($course_sessions as $course_session)
        @php
        $statusLabel = match ($course_session->evaluation_status) {
        'open' => 'Evaluation open',
        'closed' => 'Evaluation closed',
        default => 'Evaluation status not set',
        };
        $statusColor = $course_session->evaluation_status === 'open' ? 'success' : 'secondary';
        @endphp
        <div class="col-12 col-md-6 col-xl-4"
            data-session
            data-course="{{ $course_session->course->name }}"
            data-instructor="{{ $course_session->instructor->name }}">
            <article class="session-card card h-100 border-0 rounded-4 shadow-sm">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                        <span class="small text-body-secondary font-monospace text-break">{{ $course_session->course_session_number }}</span>
                        <span class="badge rounded-pill bg-{{ $statusColor }}-subtle text-{{ $statusColor }}-emphasis">{{ $statusLabel }}</span>
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
                    <button class="btn btn-link link-success text-decoration-none fw-semibold small p-0 mt-3 stretched-link"
                        type="button" data-bs-toggle="modal" data-bs-target="#session-{{ $course_session->id }}"
                        aria-label="View details for {{ $course_session->course->name }}, {{ $course_session->course_session_number }}">
                        View details <span aria-hidden="true">&rarr;</span>
                    </button>
                </div>
            </article>
        </div>
        <div class="modal fade" id="session-{{ $course_session->id }}" tabindex="-1"
            aria-labelledby="session-title-{{ $course_session->id }}" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
                <div class="modal-content border-0 rounded-4 shadow">
                    <div class="modal-header px-4 py-3">
                        <div>
                            <p class="small text-success fw-semibold mb-1">Session details</p>
                            <h2 class="modal-title fs-5 text-break" id="session-title-{{ $course_session->id }}">{{ $course_session->course->name }}</h2>
                        </div>
                        <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <span class="badge rounded-pill bg-{{ $statusColor }}-subtle text-{{ $statusColor }}-emphasis mb-4">{{ $statusLabel }}</span>
                        <dl class="row g-3 mb-0">
                            <div class="col-sm-6">
                                <dt class="small text-body-secondary fw-normal">Session number</dt>
                                <dd class="font-monospace text-break mb-0">{{ $course_session->course_session_number }}</dd>
                            </div>
                            <div class="col-sm-6">
                                <dt class="small text-body-secondary fw-normal">Instructor</dt>
                                <dd class="text-break mb-0">{{ $course_session->instructor->name }}</dd>
                            </div>
                            <div class="col-sm-6">
                                <dt class="small text-body-secondary fw-normal">Course dates</dt>
                                <dd class="mb-0">{{ $course_session->start_date->format('d M Y') }} &ndash; {{ $course_session->end_date->format('d M Y') }}</dd>
                            </div>
                            <div class="col-sm-6">
                                <dt class="small text-body-secondary fw-normal">Feedback form codes</dt>
                                <dd class="font-monospace mb-0">{{ $course_session->feedbackForms->whereNotNull('code')->pluck('code')->join(', ') ?: 'Not assigned' }}</dd>
                            </div>
                            <div class="col-12">
                                <dt class="small text-body-secondary fw-normal">Questionnaire</dt>
                                <dd class="text-break mb-0">{{ $course_session->course->questionnaireTemplate?->name ?? 'No questionnaire assigned' }}</dd>
                            </div>
                        </dl>
                    </div>
                    <div class="modal-footer px-4">
                        <button class="btn btn-outline-secondary rounded-3" type="button" data-bs-dismiss="modal">Close</button>
                        @if ($course_session->course->questionnaireTemplate)
                        @foreach ($course_session->feedbackForms->whereNotNull('code') as $form)
                        <a class="btn btn-success rounded-3" href="{{ route('questionaire', ['code' => $form->code]) }}">View questionnaire {{ $form->code }}</a>
                        @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @empty
        <div class="col-12">
            <div class="card border-0 rounded-4 shadow-sm p-5 text-center">
                <h2 class="h5">No course sessions yet</h2>
                <p class="text-body-secondary mb-0">Course sessions will appear here once they are created.</p>
            </div>
        </div>
        @endforelse
    </div>
</main>
@endsection
