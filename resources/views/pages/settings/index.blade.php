@extends('layouts.app')

@section('title', __('Settings - Evalio'))

@section('content')
<main class="container py-4 py-lg-5">
    <header class="mb-4">
        <p class="small text-body-secondary fw-medium mb-2">{{ __('Administration') }}</p>
        <h1 class="h3 fw-semibold mb-2">{{ __('Settings') }}</h1>
        <p class="text-body-secondary mb-0">{{ __('Manage backups and data transfers.') }}</p>
    </header>
    <x-ui.status-alert />
    <x-ui.validation-errors :errors="$errors" />
    <div class="row g-0 border rounded-2 bg-white overflow-hidden">
        @foreach ([
            [
                'title' => 'Backup',
                'description' => 'Store a database backup on the application server.',
                'button' => 'Create backup',
                'route' => 'settings.backup',
            ],
            [
                'title' => 'CSV import',
                'description' => 'Import records from a CSV file.',
                'button' => 'Import CSV',
                'link' => 'settings.imports',
            ],
            [
                'title' => 'SQL dump',
                'description' => 'Download the current database as a SQL file.',
                'button' => 'Export SQL dump',
                'route' => 'settings.sql-dump',
            ],
        ] as $setting)
        <div class="col-12">
            <section class="border-bottom p-4 d-flex flex-column flex-md-row align-items-md-start gap-3">
                <h2 class="h6 fw-semibold col-md-2 mb-0">{{ __($setting['title']) }}</h2>
                <p class="small text-body-secondary col-md-5 mb-0">{{ __($setting['description']) }}</p>
                <div class="ms-md-auto">
                    @if (isset($setting['link']))
                    <a href="{{ route($setting['link']) }}" class="btn btn-outline-primary rounded-2">{{ __($setting['button']) }}</a>
                    @elseif (isset($setting['route']))
                    <form method="POST" action="{{ route($setting['route']) }}">
                        @csrf
                        <button class="btn btn-outline-primary rounded-2" type="submit">{{ __($setting['button']) }}</button>
                    </form>
                    @else
                    <button class="btn btn-outline-primary rounded-2" type="button" disabled>{{ __($setting['button']) }}</button>
                    <p class="small text-body-secondary mb-0 mt-2">{{ __('Coming soon') }}</p>
                    @endif
                </div>
            </section>
        </div>
        @endforeach
    </div>
</main>
@endsection
