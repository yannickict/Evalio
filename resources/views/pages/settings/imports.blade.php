@extends('layouts.app')

@section('title', __('CSV imports - Evalio'))

@section('content')
<main class="container py-4 py-lg-5">
    <header class="mb-4">
        <a href="{{ route('settings.index') }}" class="link-secondary">{{ __('Back to settings') }}</a>
        <p class="small text-body-secondary fw-medium mt-4 mb-2">{{ __('Administration') }}</p>
        <h1 class="h3 fw-semibold mb-2">{{ __('CSV imports') }}</h1>
        <p class="text-body-secondary mb-0">{{ __('Choose the records you want to import.') }}</p>
    </header>
    <div class="row g-0 border rounded-2 bg-white overflow-hidden">
        @foreach ([
            [
                'title' => 'Sessions',
                'description' => 'Import sessions using existing course names and approved instructor emails.',
                'route' => 'settings.sessions-import.create',
            ],
            [
                'title' => 'Courses',
                'description' => 'Import courses using existing questionnaire names.',
                'route' => 'settings.courses-import.create',
            ],
        ] as $import)
        <div class="col-12">
            <section class="border-bottom p-4 d-flex flex-column flex-md-row align-items-md-start gap-3">
                <h2 class="h6 fw-semibold col-md-2 mb-0">{{ __($import['title']) }}</h2>
                <p class="small text-body-secondary col-md-5 mb-0">{{ __($import['description']) }}</p>
                <div class="ms-md-auto">
                    @if (isset($import['route']))
                    <a href="{{ route($import['route']) }}" class="btn btn-outline-primary rounded-2">{{ __($import['title'] === 'Courses' ? 'Import courses' : 'Import sessions') }}</a>
                    @else
                    <span class="small text-body-secondary">{{ __('Coming soon') }}</span>
                    @endif
                </div>
            </section>
        </div>
        @endforeach
    </div>
</main>
@endsection
