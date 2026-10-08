@props(['current' => null, 'home' => false])

@can('view-workflow-guide')
<div @class(['workflow-guide text-start', 'border rounded-3 mb-4' => ! $home, 'border-top mt-4 pt-3' => $home])>
    <button class="workflow-guide-toggle btn w-100 d-flex align-items-center gap-3 text-start border-0 px-3 py-2"
            type="button" data-bs-toggle="collapse" data-bs-target="#workflow-guide-{{ $current ?? 'home' }}"
            aria-expanded="false" aria-controls="workflow-guide-{{ $current ?? 'home' }}">
        <span class="workflow-guide-icon rounded-circle bg-success-subtle text-success d-inline-flex align-items-center justify-content-center flex-shrink-0" aria-hidden="true">?</span>
        <span class="flex-grow-1">
            <span class="small fw-semibold d-block">{{ $home ? __('Using Evalio for your courses?') : __('Workflow guide') }}</span>
            <span class="small text-body-secondary d-block">{{ $home ? __('A quick guide to setup and sharing feedback.') : __('Questionnaire → Course → Session') }}</span>
        </span>
        <svg class="workflow-guide-chevron text-body-secondary flex-shrink-0" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6" stroke-linecap="round" stroke-linejoin="round" /></svg>
    </button>
    <div class="collapse" id="workflow-guide-{{ $current ?? 'home' }}">
    <div @class(['p-3', 'p-md-4 border-top' => ! $home])>
        @if (auth()->user()->role?->name === 'instructor')
        <h2 class="h5 fw-semibold mb-2">{{ __('Collect feedback for your sessions') }}</h2>
        <ol class="small text-body-secondary ps-3 mb-3">
            <li class="mb-2">{{ __('Open') }} <a class="link-success" href="{{ route('sessions.index') }}">{{ __('Sessions') }}</a> {{ __('to find the sessions assigned to you.') }}</li>
            <li class="mb-2">{{ __('Select “View details” for a session to check its evaluation status and feedback code.') }}</li>
            <li>{{ __('When the evaluation is open, share the six-digit code with participants. They enter it on the') }} <a class="link-success" href="{{ route('home') }}">{{ __('home page') }}</a> {{ __('without needing an account.') }}</li>
        </ol>
        <p class="small text-body-secondary border-top pt-3 mb-3">{{ __('Evaluations open automatically on the session start date and close 14 days after it ends. Contact an admin or editor if an evaluation needs to be opened or closed manually.') }}</p>
        <h3 class="h6 fw-semibold">{{ __('How setup works') }}</h3>
        @else
        <h2 class="h5 fw-semibold mb-2">{{ __('Set up a course evaluation') }}</h2>
        @endif
        <p class="text-body-secondary small mb-4">{{ __('Start with a questionnaire, assign it to a course, then schedule a session. You can reuse questionnaires and courses.') }}</p>
        <ol class="row g-3 list-unstyled mb-0">
            @foreach ([
                ['key' => 'questionnaires', 'title' => 'Build a questionnaire', 'description' => 'Choose the questions participants will answer.', 'route' => 'questionnaires.index'],
                ['key' => 'courses', 'title' => 'Create a course', 'description' => 'Name the course and assign its questionnaire.', 'route' => 'courses.index'],
                ['key' => 'sessions', 'title' => 'Schedule a session', 'description' => 'Choose the course, instructor, and dates.', 'route' => 'sessions.index'],
            ] as $step)
            <li @class(['col-12', 'col-lg-4' => ! $home])>
                <a href="{{ route($step['route']) }}"
                   @class(['d-block h-100 rounded-3 border p-3 text-decoration-none', 'border-success bg-success-subtle' => $current === $step['key'], 'border-light-subtle bg-body-tertiary' => $current !== $step['key']])
                   @if ($current === $step['key']) aria-current="step" @endif>
                    <span class="small fw-semibold text-success d-block mb-2">{{ __('Step') }} {{ $loop->iteration }}{{ $current === $step['key'] ? __(' · You are here') : '' }}</span>
                    <span class="fw-semibold text-body d-block mb-1">{{ __($step['title']) }} <span aria-hidden="true">&rarr;</span></span>
                    <span class="small text-body-secondary">{{ __($step['description']) }}</span>
                </a>
            </li>
            @endforeach
        </ol>
        @can('manage-evaluations')
        <p class="text-body-secondary small border-top pt-3 mt-3 mb-0">{{ __('Evaluations open automatically on the session start date and close 14 days after it ends. Once open, share the six-digit feedback code from the session details with participants. Admins and editors can also open or close evaluations there.') }}</p>
        @endcan
    </div>
    </div>
</div>
@endcan
