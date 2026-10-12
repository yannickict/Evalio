@props(['course'])

@php
$isInstructor = auth()->user()?->role?->name === 'instructor';
@endphp

<div class="col-12">
    <article class="course-card border-bottom bg-white px-3 px-lg-4 py-3">
        <div class="row align-items-center g-3">
            <div class="col-12 col-md-3">
                <h2 class="h6 fw-semibold mb-1 text-break">{{ $course->name }}</h2>
                <span class="small text-body-secondary">{{ $course->sessions_count }} {{ $isInstructor ? ($course->sessions_count === 1 ? __('session assigned to you') : __('sessions assigned to you')) : ($course->sessions_count === 1 ? __('session') : __('sessions')) }}</span>
            </div>
            <div class="col-12 col-md-5">
                <p class="small text-body-secondary mb-1">{{ __('Assigned questionnaire') }}</p>
                <p class="small text-break mb-0">{{ $course->questionnaireTemplate?->name ?? __('No questionnaire assigned') }}</p>
            </div>
            <div class="col-12 col-md-4 d-flex justify-content-md-end flex-wrap gap-2">
                <button class="btn btn-link btn-sm text-decoration-none" type="button" data-bs-toggle="modal" data-bs-target="#course-{{ $course->id }}" aria-label="{{ __('View course details for :value1', ['value1' => $course->name]) }}">{{ __('View course') }}</button>
                <a class="btn btn-outline-secondary btn-sm" href="{{ route('sessions.create', ['course_id' => $course->id]) }}" aria-label="{{ __('Create session for :value1', ['value1' => $course->name]) }}">{{ __('Create session') }}</a>
            </div>
        </div>
    </article>
</div>
<x-ui.modal id="course-{{ $course->id }}" labelledby="course-title-{{ $course->id }}" edit-permission="edit-courses" :edit-url="route('courses.edit', $course)" delete-permission="delete-courses" :delete-url="route('courses.delete', $course)" :delete-confirmation="__('Permanently delete this course, all its sessions, their feedback forms and all submitted answers? This cannot be undone.')">
    <x-slot:header>
        <div>
            <p class="small text-primary fw-semibold mb-1">{{ __('Course details') }}</p>
            <h2 class="modal-title fs-4 fw-bold text-break" id="course-title-{{ $course->id }}">{{ $course->name }}</h2>
        </div>
    </x-slot:header>
    <p class="small text-body-secondary mb-1">{{ __('Assigned questionnaire') }}</p>
    <p class="fw-medium text-break mb-4">{{ $course->questionnaireTemplate?->name ?? __('No questionnaire assigned') }}</p>
    <div class="d-flex align-items-center gap-2 mb-3">
        <h3 class="h5 mb-0">{{ $isInstructor ? __('Your sessions') : __('Sessions') }}</h3>
        <span class="badge rounded-1 bg-primary-subtle text-primary-emphasis">{{ $course->sessions_count }}</span>
        <a class="btn btn-outline-secondary btn-sm ms-auto"
           href="{{ route('sessions.create', ['course_id' => $course->id]) }}">{{ __('Create session') }}</a>
    </div>
    <ul class="list-group list-group-flush">
        @forelse ($course->sessions->sortBy('start_date') as $courseSession)
        @php
        $canOpenSession = in_array(auth()->user()?->role?->name, ['admin', 'editor'], true)
            || (auth()->user()?->role?->name === 'instructor' && (int) auth()->id() === (int) $courseSession->instructor_id);
        @endphp
        <li class="list-group-item px-0 py-1">
            @if ($canOpenSession)
            <a class="course-session-link d-block rounded-2 p-3 text-body text-decoration-none"
               href="{{ route('sessions.index', ['session' => $courseSession->id]) }}"
               aria-label="{{ __('View session :value1', ['value1' => $courseSession->session_identifier]) }}">
            @else
            <div class="p-3">
            @endif
            <p class="small font-monospace text-break mb-1">{{ $courseSession->session_identifier }}</p>
            <p class="fw-medium text-break mb-1">{{ $courseSession->instructor?->name ?? __('No instructor assigned') }}</p>
            <p class="small text-body-secondary mb-0">
                <time datetime="{{ $courseSession->start_date->toDateString() }}">{{ $courseSession->start_date->locale(app()->getLocale())->translatedFormat(app()->getLocale() === 'de' ? 'd.m.Y' : 'd M Y') }}</time>
                &ndash;
                <time datetime="{{ $courseSession->end_date->toDateString() }}">{{ $courseSession->end_date->locale(app()->getLocale())->translatedFormat(app()->getLocale() === 'de' ? 'd.m.Y' : 'd M Y') }}</time>
            </p>
            @if ($canOpenSession)
            <span class="text-primary small fw-semibold d-inline-block mt-2">{{ __('View session') }} <span aria-hidden="true">&rarr;</span></span>
            </a>
            @else
            </div>
            @endif
        </li>
        @empty
        <li class="list-group-item px-0 text-body-secondary">{{ $isInstructor ? __('No sessions are assigned to you for this course.') : __('No sessions for this course yet.') }}</li>
        @endforelse
    </ul>
</x-ui.modal>
