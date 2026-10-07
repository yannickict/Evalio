@props(['courseSession'])

@php
$statusLabel = match ($courseSession->evaluation_status) {
'open' => 'Evaluation open',
'closed' => 'Evaluation closed',
default => 'Evaluation status not set',
};
$statusColor = $courseSession->evaluation_status === 'open' ? 'success' : 'secondary';
@endphp
<div class="col-12 col-md-6 col-xl-4"
    data-session
    data-course="{{ $courseSession->course_id }}"
    data-instructor="{{ $courseSession->instructor_id }}">
    <article class="session-card card h-100 border-0 rounded-4 shadow-sm">
        <div class="card-body p-4">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                <span class="small text-body-secondary font-monospace text-break">{{ $courseSession->course_session_number }}</span>
                <span class="badge rounded-pill bg-{{ $statusColor }}-subtle text-{{ $statusColor }}-emphasis">{{ $statusLabel }}</span>
            </div>
            <h2 class="h5 fw-semibold text-break mb-4">{{ $courseSession->course->name }}</h2>
            <dl class="mb-0">
                <dt class="small text-body-secondary fw-normal">Instructor</dt>
                <dd class="fw-medium text-break mb-3">{{ $courseSession->instructor->name }}</dd>
                <dt class="small text-body-secondary fw-normal">Course dates</dt>
                <dd class="fw-medium mb-0">
                    <time datetime="{{ $courseSession->start_date->toDateString() }}">{{ $courseSession->start_date->format('d M Y') }}</time>
                    <span class="text-body-secondary">&ndash;</span>
                    <time datetime="{{ $courseSession->end_date->toDateString() }}">{{ $courseSession->end_date->format('d M Y') }}</time>
                </dd>
            </dl>
        </div>
        <div class="card-footer bg-transparent border-top px-4 py-3">
            <p class="small text-body-secondary mb-1">Questionnaire</p>
            <p class="small fw-medium text-break mb-0">{{ $courseSession->questionnaireTemplate?->name ?? 'No questionnaire assigned' }}</p>
            <button class="btn btn-link link-success text-decoration-none fw-semibold small p-0 mt-3 stretched-link"
                type="button" data-bs-toggle="modal" data-bs-target="#session-{{ $courseSession->id }}"
                aria-label="View details for {{ $courseSession->course->name }}, {{ $courseSession->course_session_number }}">
                View details <span aria-hidden="true">&rarr;</span>
            </button>
        </div>
    </article>
</div>
<x-ui.modal id="session-{{ $courseSession->id }}" labelledby="session-title-{{ $courseSession->id }}" edit-permission="edit-sessions" :edit-url="route('sessions.edit', $courseSession)" delete-permission="delete-sessions" :delete-url="route('sessions.delete', $courseSession)" delete-confirmation="Permanently delete this session, its feedback form and all submitted answers? This cannot be undone.">
    <x-slot:header>
        <div>
            <p class="small text-success fw-semibold mb-1">Session details</p>
            <h2 class="modal-title fs-5 text-break" id="session-title-{{ $courseSession->id }}">{{ $courseSession->course->name }}</h2>
        </div>
    </x-slot:header>
    <span class="badge rounded-pill bg-{{ $statusColor }}-subtle text-{{ $statusColor }}-emphasis mb-4">{{ $statusLabel }}</span>
    <dl class="row g-3 mb-0">
        <div class="col-sm-6">
            <dt class="small text-body-secondary fw-normal">Session number</dt>
            <dd class="font-monospace text-break mb-0">{{ $courseSession->course_session_number }}</dd>
        </div>
        <div class="col-sm-6">
            <dt class="small text-body-secondary fw-normal">Instructor</dt>
            <dd class="text-break mb-0">{{ $courseSession->instructor->name }}</dd>
        </div>
        <div class="col-sm-6">
            <dt class="small text-body-secondary fw-normal">Course dates</dt>
            <dd class="mb-0">{{ $courseSession->start_date->format('d M Y') }} &ndash; {{ $courseSession->end_date->format('d M Y') }}</dd>
        </div>
        <div class="col-sm-6">
            <dt class="small text-body-secondary fw-normal">Feedback form code</dt>
            <dd class="font-monospace mb-0">{{ $courseSession->feedbackForm?->code ?? 'Not assigned' }}</dd>
        </div>
        <div class="col-12">
            <dt class="small text-body-secondary fw-normal">Questionnaire</dt>
            <dd class="text-break mb-0">{{ $courseSession->questionnaireTemplate?->name ?? 'No questionnaire assigned' }}</dd>
        </div>
    </dl>
    <div class="d-flex flex-column flex-sm-row flex-wrap gap-2 border-top pt-3 mt-4">
        @if ($courseSession->evaluation_status === 'open' && $courseSession->questionnaireTemplate && $courseSession->feedbackForm?->code !== null)
        <a class="btn btn-success rounded-3 text-break" href="{{ route('feedback.show', ['code' => $courseSession->feedbackForm->code]) }}">View questionnaire</a>
        @endif
        @can('manage-evaluations')
        <form method="POST"
            action="{{ route('sessions.evaluation.update', $courseSession) }}">
            @csrf
            @method('PATCH')

            <input type="hidden" name="evaluation_status"
                value="{{ $courseSession->evaluation_status === 'open' ? 'closed' : 'open' }}">

            <button type="submit" class="btn btn-outline-success rounded-3">
                {{ $courseSession->evaluation_status === 'open'
                ? 'Close evaluation'
                : 'Open evaluation' }}
            </button>
        </form>
        @endcan
    </div>
</x-ui.modal>
