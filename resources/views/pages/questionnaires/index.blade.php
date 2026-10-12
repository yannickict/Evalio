@extends('layouts.app')

@section('title', __('Questionnaires - Evalio'))

@section('content')
<main class="container py-4 py-lg-5">
    <header class="mb-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-2">
            <h1 class="h3 fw-semibold mb-0">{{ __('Questionnaires') }}</h1>
            @can('create-questionnaires')
            <a href="{{ route('questionnaires.create') }}" class="btn btn-primary rounded-2">{{ __('New questionnaire') }}</a>
            @endcan
        </div>
        <p class="text-body-secondary mb-0">{{ __('Create reusable feedback questionnaires for your course sessions.') }}</p>
    </header>

    <x-ui.status-alert />
    <x-ui.validation-errors :errors="$errors" />

    <x-ui.setup-guide current="questionnaires" />

    <div class="collection-list row g-0 border rounded-2 overflow-hidden">
        @forelse ($templates as $template)
        <x-questionnaires.card :template="$template" />
        @empty
        <div class="col-12">
            <div class="card border rounded-2">
                <div class="card-body p-4 p-md-5 text-center">
                    <span class="badge rounded-1 bg-primary-subtle text-primary-emphasis mb-3">{{ __('Questionnaire library') }}</span>
                    <h2 class="h5 fw-semibold">{{ __('Build your next feedback form') }}</h2>
                    @can('create-questionnaires')
                    <p class="text-body-secondary mb-4">{{ __('Start with a name, then add questions and answer options.') }}</p>
                    <a href="{{ route('questionnaires.create') }}" class="btn btn-outline-primary rounded-2">{{ __('Create a questionnaire') }}</a>
                    @else
                    <p class="text-body-secondary mb-0">{{ __('An admin or editor can create a questionnaire and assign it to a course.') }}</p>
                    @endcan
                </div>
            </div>
        </div>
        @endforelse
    </div>
</main>
@endsection
