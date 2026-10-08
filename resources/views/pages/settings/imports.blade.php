@extends('layouts.app')

@section('title', 'CSV imports - Evalio')

@section('content')
<main class="container py-5">
    <header class="mb-4">
        <a href="{{ route('settings.index') }}" class="link-secondary">Back to settings</a>
        <p class="small text-success fw-semibold text-uppercase mt-4 mb-2">Administration</p>
        <h1 class="h2 fw-bold mb-2">CSV imports</h1>
        <p class="text-body-secondary mb-0">Choose the records you want to import.</p>
    </header>
    <div class="row g-4">
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
        <div class="col-12 col-md-6">
            <section class="card border-0 rounded-4 shadow-sm h-100 p-4">
                <h2 class="h5 fw-semibold">{{ $import['title'] }}</h2>
                <p class="small text-body-secondary">{{ $import['description'] }}</p>
                <div class="mt-auto">
                    @if (isset($import['route']))
                    <a href="{{ route($import['route']) }}" class="btn btn-outline-success rounded-3">Import {{ strtolower($import['title']) }}</a>
                    @else
                    <span class="small text-body-secondary">Coming soon</span>
                    @endif
                </div>
            </section>
        </div>
        @endforeach
    </div>
</main>
@endsection
