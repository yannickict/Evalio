@extends('layouts.app')

@section('title', __('Settings - Evalio'))

@section('content')
<main class="container py-5">
    <header class="mb-4">
        <p class="small text-success fw-semibold text-uppercase mb-2">{{ __('Administration') }}</p>
        <h1 class="h2 fw-bold mb-2">{{ __('Settings') }}</h1>
        <p class="text-body-secondary mb-0">{{ __('Manage backups and data transfers.') }}</p>
    </header>
    <x-ui.status-alert />
    <x-ui.validation-errors :errors="$errors" />
    <div class="row g-4">
        @foreach ([
            [
                'title' => 'Backup',
                'description' => 'Store a demo backup file.',
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
                'description' => 'Download a demo SQL file.',
                'button' => 'Export SQL dump',
                'route' => 'settings.sql-dump',
            ],
        ] as $setting)
        <div class="col-12 col-md-6 col-lg-4">
            <section class="card border-0 rounded-4 shadow-sm h-100 p-4">
                <h2 class="h5 fw-semibold">{{ __($setting['title']) }}</h2>
                <p class="small text-body-secondary">{{ __($setting['description']) }}</p>
                <div class="mt-auto">
                    @if (isset($setting['link']))
                    <a href="{{ route($setting['link']) }}" class="btn btn-outline-success rounded-3">{{ __($setting['button']) }}</a>
                    @elseif (isset($setting['route']))
                    <form method="POST" action="{{ route($setting['route']) }}">
                        @csrf
                        <button class="btn btn-outline-success rounded-3" type="submit">{{ __($setting['button']) }}</button>
                    </form>
                    <p class="small text-body-secondary mb-0 mt-2">{{ __('Demo file only; not a real database backup.') }}</p>
                    @else
                    <button class="btn btn-outline-success rounded-3" type="button" disabled>{{ __($setting['button']) }}</button>
                    <p class="small text-body-secondary mb-0 mt-2">{{ __('Coming soon') }}</p>
                    @endif
                </div>
            </section>
        </div>
        @endforeach
    </div>
</main>
@endsection
