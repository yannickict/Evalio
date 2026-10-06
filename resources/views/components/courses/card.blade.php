@props(['course'])

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
<x-ui.modal id="course-{{ $course->id }}" labelledby="course-title-{{ $course->id }}">
    <x-slot:header>
        <div>
            <p class="small text-success fw-semibold mb-1">Course details</p>
            <h2 class="modal-title fs-4 fw-bold text-break" id="course-title-{{ $course->id }}">{{ $course->name }}</h2>
        </div>
    </x-slot:header>
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
</x-ui.modal>
