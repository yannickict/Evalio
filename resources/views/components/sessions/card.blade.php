@props(['courseSession'])

@php
$statusLabel = match ($courseSession->evaluation_status) {
'open' => __('Evaluation open'),
'closed' => __('Evaluation closed'),
default => __('Evaluation status not set'),
};
$statusColor = $courseSession->evaluation_status === 'open' ? 'success' : 'secondary';
@endphp
<div class="col-12" data-session data-course="{{ $courseSession->course_id }}" data-instructor="{{ $courseSession->instructor_id }}">
    <article class="session-card border-bottom bg-white px-3 px-lg-4 py-3">
        <div class="row g-3 align-items-center">
            <div class="col-6 col-lg-2">
                <h2 class="h6 fw-semibold mb-1 text-break">{{ $courseSession->course->name }}</h2>
                <span class="small text-body-secondary font-monospace">{{ $courseSession->session_identifier }}</span>
            </div>
            <div class="col-6 col-lg-2">
                <p class="small text-body-secondary d-lg-none mb-1">{{ __('Instructor') }}</p>
                <p class="small text-break mb-0">{{ $courseSession->instructor->name }}</p>
            </div>
            <div class="col-12 col-sm-6 col-lg-3">
                <p class="small mb-1">
                    <time datetime="{{ $courseSession->start_date->toDateString() }}">{{ $courseSession->start_date->locale(app()->getLocale())->translatedFormat(app()->getLocale() === 'de' ? 'd.m.Y' : 'd M Y') }}</time>
                    &ndash;
                    <time datetime="{{ $courseSession->end_date->toDateString() }}">{{ $courseSession->end_date->locale(app()->getLocale())->translatedFormat(app()->getLocale() === 'de' ? 'd.m.Y' : 'd M Y') }}</time>
                </p>
                <p class="small text-body-secondary text-break mb-0">{{ $courseSession->questionnaireTemplate?->name ?? __('No questionnaire assigned') }}</p>
            </div>
            <div class="col-8 col-sm-4 col-lg-3">
                <span class="badge rounded-1 bg-{{ $statusColor }}-subtle text-{{ $statusColor }}-emphasis text-wrap text-start">{{ $statusLabel }}</span>
            </div>
            <div class="col-4 col-sm-2 col-lg-2 text-end">
                <button class="btn btn-link btn-sm px-0 text-decoration-none" type="button" data-bs-toggle="modal" data-bs-target="#session-{{ $courseSession->id }}" aria-label="{{ __('View details for :value1, :value2', ['value1' => $courseSession->course->name, 'value2' => $courseSession->session_identifier]) }}">{{ __('View details') }}</button>
            </div>
        </div>
    </article>
</div>
<x-ui.modal id="session-{{ $courseSession->id }}" labelledby="session-title-{{ $courseSession->id }}" edit-permission="edit-sessions" :edit-url="route('sessions.edit', $courseSession)" delete-permission="delete-sessions" :delete-url="route('sessions.delete', $courseSession)" :delete-confirmation="__('Permanently delete this session, its feedback form and all submitted answers? This cannot be undone.')">
    <x-slot:header>
        <div>
            <p class="small text-primary fw-semibold mb-1">{{ __('Session details') }}</p>
            <h2 class="modal-title fs-5 text-break" id="session-title-{{ $courseSession->id }}">{{ $courseSession->course->name }}</h2>
        </div>
    </x-slot:header>
    <span class="badge rounded-1 bg-{{ $statusColor }}-subtle text-{{ $statusColor }}-emphasis mb-4">{{ $statusLabel }}</span>
    <dl class="row g-3 mb-0">
        <div class="col-sm-6">
            <dt class="small text-body-secondary fw-normal">{{ __('Session number') }}</dt>
            <dd class="font-monospace text-break mb-0">{{ $courseSession->session_identifier }}</dd>
        </div>
        <div class="col-sm-6">
            <dt class="small text-body-secondary fw-normal">{{ __('Instructor') }}</dt>
            <dd class="text-break mb-0">{{ $courseSession->instructor->name }}</dd>
        </div>
        <div class="col-sm-6">
            <dt class="small text-body-secondary fw-normal">{{ __('Course dates') }}</dt>
            <dd class="mb-0">{{ $courseSession->start_date->locale(app()->getLocale())->translatedFormat(app()->getLocale() === 'de' ? 'd.m.Y' : 'd M Y') }} &ndash; {{ $courseSession->end_date->locale(app()->getLocale())->translatedFormat(app()->getLocale() === 'de' ? 'd.m.Y' : 'd M Y') }}</dd>
        </div>
        <div class="col-sm-6">
            <dt class="small text-body-secondary fw-normal">{{ __('Feedback form code') }}</dt>
            <dd class="font-monospace mb-0">{{ $courseSession->feedbackForm?->code ?? __('Not assigned') }}</dd>
        </div>
        <div class="col-12">
            <dt class="small text-body-secondary fw-normal">{{ __('Questionnaire') }}</dt>
            <dd class="text-break mb-0">{{ $courseSession->questionnaireTemplate?->name ?? __('No questionnaire assigned') }}</dd>
        </div>
    </dl>
    <div class="d-flex align-items-start align-items-sm-center flex-column flex-sm-row flex-wrap gap-3 border-top pt-3 mt-4">
        @can('view-evaluation-results', $courseSession)
        <a class="btn btn-link btn-sm text-decoration-none px-0" href="{{ route('sessions.results', $courseSession) }}">{{ __('View evaluation results') }}</a>
        @endcan
        @if ($courseSession->evaluation_status === 'open' && $courseSession->questionnaireTemplate && $courseSession->feedbackForm?->code !== null)
        <a class="btn btn-link btn-sm text-decoration-none px-0 text-break" href="{{ route('feedback.show', ['code' => $courseSession->feedbackForm->code]) }}">{{ __('View questionnaire') }}</a>
        @endif
        @can('manage-evaluations')
        <form class="ms-sm-auto" method="POST" data-evaluation-status-form data-session-id="{{ $courseSession->id }}"
            action="{{ route('sessions.evaluation.update', $courseSession) }}">
            @csrf
            @method('PATCH')

            <input type="hidden" name="evaluation_status"
                value="{{ $courseSession->evaluation_status === 'open' ? 'closed' : 'open' }}">

            <button type="submit" class="btn btn-outline-secondary btn-sm">
                {{ $courseSession->evaluation_status === 'open'
                ? __('Close evaluation')
                : __('Open evaluation') }}
            </button>
        </form>
        @endcan
    </div>
</x-ui.modal>
