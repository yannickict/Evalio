@props(['template'])

<x-ui.modal id="questionnaire-{{ $template->id }}" labelledby="questionnaire-title-{{ $template->id }}" edit-permission="edit-questionnaires" :edit-label="__('Duplicate and edit')" :edit-url="route('questionnaires.duplicate', $template)" delete-permission="delete-questionnaires" :delete-url="route('questionnaires.delete', $template)" :delete-confirmation="__('Permanently delete this questionnaire and its unused questions and answer options? Assigned questionnaires cannot be deleted. This cannot be undone.')">
    <x-slot:header>
        <div class="pe-3">
            <p class="small text-success fw-semibold mb-1">{{ __('Questionnaire preview') }}</p>
            <h2 class="modal-title fs-4 fw-bold text-break mb-0" id="questionnaire-title-{{ $template->id }}">{{ $template->name }}</h2>
        </div>
    </x-slot:header>

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
        <button class="btn btn-outline-success rounded-3 d-flex align-items-center gap-2 collapsed" type="button"
            data-bs-toggle="collapse" data-bs-target="#questionnaire-questions-{{ $template->id }}"
            aria-expanded="false" aria-controls="questionnaire-questions-{{ $template->id }}">
            {{ __('Questions and answer options') }}
            <span class="badge rounded-pill bg-success-subtle text-success-emphasis">
                {{ $template->questions->count() }} {{ $template->questions->count() === 1 ? __('question') : __('questions') }}
            </span>
            <svg class="workflow-guide-chevron flex-shrink-0" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke-linecap="round" stroke-linejoin="round" /></svg>
        </button>
    </div>

    <div class="collapse" id="questionnaire-questions-{{ $template->id }}">
    <ol class="list-unstyled d-grid gap-3 mb-0">
        @forelse ($template->questions as $question)
        <x-questionnaires.preview-question :question="$question" :number="$loop->iteration" :template-id="$template->id" />
        @empty
        <li class="border rounded-4 bg-body-tertiary p-4 text-center">
            <h3 class="h5">{{ __('No questions yet') }}</h3>
            <p class="small text-body-secondary mb-0">{{ __('This questionnaire has no questions to preview.') }}</p>
        </li>
        @endforelse
    </ol>
    </div>

    <section class="border-top pt-4 mt-4" aria-labelledby="questionnaire-usage-{{ $template->id }}">
        <h3 class="h5 fw-semibold mb-1" id="questionnaire-usage-{{ $template->id }}">{{ __('Where it\'s used') }}</h3>
        <p class="small text-body-secondary mb-4">{{ auth()->user()->hasRole('instructor') ? __('Course assignments and feedback received for your sessions.') : __('Course assignments and feedback received for each session.') }}</p>

        <div class="row g-4">
            <div class="col-12 col-sm-4">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <h4 class="h6 mb-0">{{ __('Courses') }}</h4>
                    <span class="badge rounded-pill bg-body-tertiary text-body-secondary border">{{ $template->courses->count() }}</span>
                </div>
                <ul class="list-unstyled d-flex flex-wrap gap-2 mb-0">
                    @forelse ($template->courses->sortBy('name') as $course)
                    <li>
                        <a class="btn btn-outline-success rounded-3 fw-semibold small text-break"
                            href="{{ route('courses.index', ['course' => $course->id]) }}"
                            aria-label="{{ __('View course details for :value1', ['value1' => $course->name]) }}">{{ $course->name }} <span aria-hidden="true">&rarr;</span></a>
                    </li>
                    @empty
                    <li class="small text-body-secondary">{{ __('No courses assigned yet.') }}</li>
                    @endforelse
                </ul>
            </div>

            <div class="col-12 col-sm-8">
                <h4 class="h6 mb-3">
                    <button class="btn btn-outline-success rounded-3 d-flex align-items-center gap-2 collapsed" type="button"
                        data-bs-toggle="collapse" data-bs-target="#questionnaire-sessions-{{ $template->id }}"
                        aria-expanded="false" aria-controls="questionnaire-sessions-{{ $template->id }}">
                        {{ __('Sessions') }}
                        <span class="badge rounded-pill bg-success-subtle text-success-emphasis">{{ $template->sessions->count() }}</span>
                        <svg class="workflow-guide-chevron flex-shrink-0" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke-linecap="round" stroke-linejoin="round" /></svg>
                    </button>
                </h4>
                <div class="collapse" id="questionnaire-sessions-{{ $template->id }}">
                <ul class="list-unstyled d-grid gap-2 mb-0">
                    @forelse ($template->sessions->sortByDesc('start_date') as $session)
                    @php
                        $hasAnswers = $session->feedbackForm?->answers->isNotEmpty() ?? false;
                    @endphp
                    <li class="border rounded-3 p-3">
                        <div class="d-flex align-items-start justify-content-between flex-wrap gap-2">
                            @if (auth()->user()->hasRole('admin', 'editor') || (int) auth()->id() === (int) $session->instructor_id)
                            <a class="small font-monospace fw-semibold text-break link-success"
                                href="{{ route('sessions.index', ['session' => $session->id]) }}"
                                aria-label="{{ __('View session details for :value1', ['value1' => $session->session_identifier]) }}">{{ $session->session_identifier }} <span aria-hidden="true">&rarr;</span></a>
                            @else
                            <span class="small font-monospace fw-semibold text-break">{{ $session->session_identifier }}</span>
                            @endif
                            <span class="badge rounded-pill {{ $hasAnswers ? 'bg-success-subtle text-success-emphasis' : 'bg-body-tertiary text-body-secondary border' }}">
                                {{ $hasAnswers ? __('Feedback received') : __('No answers yet') }}
                            </span>
                        </div>
                        <p class="small text-body-secondary mb-0 mt-2">
                            <time datetime="{{ $session->start_date->toDateString() }}">{{ $session->start_date->locale(app()->getLocale())->translatedFormat(app()->getLocale() === 'de' ? 'd.m.Y' : 'd M Y') }}</time>
                            &ndash;
                            <time datetime="{{ $session->end_date->toDateString() }}">{{ $session->end_date->locale(app()->getLocale())->translatedFormat(app()->getLocale() === 'de' ? 'd.m.Y' : 'd M Y') }}</time>
                        </p>
                    </li>
                    @empty
                    <li class="border rounded-3 bg-body-tertiary p-3 small text-body-secondary">{{ auth()->user()->hasRole('instructor') ? __('No sessions are assigned to you for this questionnaire.') : __('No sessions use this questionnaire yet.') }}</li>
                    @endforelse
                </ul>
                </div>
            </div>
        </div>
    </section>
</x-ui.modal>
