@props(['course'])

@php
$isInstructor = auth()->user()?->role?->name === 'instructor';
@endphp

<div class="col-12 col-md-6 col-xl-4">
    <article class="course-card card h-100 border-0 rounded-4 shadow-sm">
        <div class="card-body p-4">
            <p class="small text-success fw-semibold text-uppercase mb-2">Course</p>
            <h2 class="h4 fw-bold text-break mb-3">{{ $course->name }}</h2>
            <span class="badge rounded-pill bg-success-subtle text-success-emphasis">
                @if ($isInstructor)
                {{ $course->sessions_count }} {{ $course->sessions_count === 1 ? 'session assigned to you' : 'sessions assigned to you' }}
                @else
                {{ $course->sessions_count }} {{ $course->sessions_count === 1 ? 'session' : 'sessions' }}
                @endif
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
            <a class="btn btn-outline-success btn-sm rounded-3 position-relative z-2 mt-3 ms-2"
               href="{{ route('sessions.create', ['course_id' => $course->id]) }}"
               aria-label="Create session for {{ $course->name }}">Create session</a>
        </div>
    </article>
</div>
<x-ui.modal id="course-{{ $course->id }}" labelledby="course-title-{{ $course->id }}" edit-permission="edit-courses" :edit-url="route('courses.edit', $course)" delete-permission="delete-courses" :delete-url="route('courses.delete', $course)" delete-confirmation="Permanently delete this course, all its sessions, their feedback forms and all submitted answers? This cannot be undone.">
    <x-slot:header>
        <div>
            <p class="small text-success fw-semibold mb-1">Course details</p>
            <h2 class="modal-title fs-4 fw-bold text-break" id="course-title-{{ $course->id }}">{{ $course->name }}</h2>
        </div>
    </x-slot:header>
    <p class="small text-body-secondary mb-1">Assigned questionnaire</p>
    <p class="fw-medium text-break mb-4">{{ $course->questionnaireTemplate?->name ?? 'No questionnaire assigned' }}</p>
    <div class="d-flex align-items-center gap-2 mb-3">
        <h3 class="h5 mb-0">{{ $isInstructor ? 'Your sessions' : 'Sessions' }}</h3>
        <span class="badge rounded-pill bg-success-subtle text-success-emphasis">{{ $course->sessions_count }}</span>
        <a class="btn btn-outline-success btn-sm rounded-3 ms-auto"
           href="{{ route('sessions.create', ['course_id' => $course->id]) }}">Create session</a>
    </div>
    <ul class="list-group list-group-flush">
        @forelse ($course->sessions->sortBy('start_date') as $courseSession)
        @php
        $canOpenSession = in_array(auth()->user()?->role?->name, ['admin', 'editor'], true)
            || (auth()->user()?->role?->name === 'instructor' && (int) auth()->id() === (int) $courseSession->instructor_id);
        @endphp
        <li class="list-group-item px-0 py-1">
            @if ($canOpenSession)
            <a class="course-session-link d-block rounded-3 p-3 text-body text-decoration-none"
               href="{{ route('sessions.index', ['session' => $courseSession->id]) }}"
               aria-label="View session {{ $courseSession->session_identifier }}">
            @else
            <div class="p-3">
            @endif
            <p class="small font-monospace text-break mb-1">{{ $courseSession->session_identifier }}</p>
            <p class="fw-medium text-break mb-1">{{ $courseSession->instructor?->name ?? 'No instructor assigned' }}</p>
            <p class="small text-body-secondary mb-0">
                <time datetime="{{ $courseSession->start_date->toDateString() }}">{{ $courseSession->start_date->format('d M Y') }}</time>
                &ndash;
                <time datetime="{{ $courseSession->end_date->toDateString() }}">{{ $courseSession->end_date->format('d M Y') }}</time>
            </p>
            @if ($canOpenSession)
            <span class="text-success small fw-semibold d-inline-block mt-2">View session <span aria-hidden="true">&rarr;</span></span>
            </a>
            @else
            </div>
            @endif
        </li>
        @empty
        <li class="list-group-item px-0 text-body-secondary">{{ $isInstructor ? 'No sessions are assigned to you for this course.' : 'No sessions for this course yet.' }}</li>
        @endforelse
    </ul>
</x-ui.modal>
