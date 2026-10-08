@extends('layouts.app')

@section('title', __('Import sessions - Evalio'))

@section('content')
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9 col-xl-8">
            <header class="mb-4">
                <a href="{{ route('settings.imports') }}" class="link-secondary text-decoration-none small d-inline-block mb-4">
                    <span aria-hidden="true">&larr;</span> {{ __('Back to CSV imports') }}
                </a>
                <p class="text-success small fw-semibold text-uppercase mb-2">{{ __('Course evaluations') }}</p>
                <h1 class="h2 fw-bold mb-2">{{ __('Import sessions') }}</h1>
                <p class="text-body-secondary mb-0">{{ __('Add sessions from a CSV file using existing courses and instructors.') }}</p>
            </header>

            <x-ui.validation-errors :errors="$errors" />

            @isset($imported)
            <section class="card border-0 rounded-4 shadow-sm mb-4" aria-labelledby="import-results-heading">
                <div class="card-body p-4">
                    <h2 id="import-results-heading" class="h5 fw-semibold mb-3">{{ __('Import results') }}</h2>
                    <div class="alert alert-info mb-0" role="status">
                        {{ __('Imported :count session(s). Skipped :skipped row(s).', ['count' => $imported, 'skipped' => count($failures)]) }}
                    </div>
                    @if ($failures)
                    <h3 class="h6 fw-semibold mt-4 mb-2">{{ __('Failed rows') }}</h3>
                    <ul class="small mb-0">
                        @foreach ($failures as $failure)
                        <li class="mb-1">{{ __('Row :number: :message', ['number' => $failure['row'], 'message' => $failure['message']]) }}</li>
                        @endforeach
                    </ul>
                    @endif
                    <a href="{{ route('sessions.index') }}" class="link-success small d-inline-block mt-3">{{ __('View sessions') }}</a>
                </div>
            </section>
            @endisset

            <form method="POST" action="{{ route('settings.sessions-import.store') }}" enctype="multipart/form-data" class="card border-0 rounded-4 shadow-sm">
                @csrf
                <div class="card-body p-4 p-md-5">
                    <section aria-labelledby="csv-format-heading" class="mb-4 pb-4 border-bottom">
                        <h2 id="csv-format-heading" class="h5 fw-semibold mb-1">{{ __('Prepare your CSV') }}</h2>
                        <p class="small text-body-secondary mb-3">{{ __('Include these column headers and use YYYY-MM-DD for dates.') }}</p>
                        <div class="bg-body-tertiary rounded-3 p-3 mb-3 overflow-auto">
                            <code class="text-body text-nowrap">course_name,instructor_email,start_date,end_date</code>
                        </div>
                        <p class="small text-body-secondary mb-0">{{ __('Courses and approved instructors must already exist. Invalid rows are skipped while valid rows are imported.') }}</p>
                        <p class="small text-body-secondary mt-2 mb-0">{{ __('Each upload creates new sessions. Retry only failed rows to avoid additional sessions.') }}</p>
                    </section>

                    <section aria-labelledby="csv-upload-heading">
                        <h2 id="csv-upload-heading" class="h5 fw-semibold mb-1">{{ __('Upload your file') }}</h2>
                        <p id="sessions-csv-help" class="small text-body-secondary mb-3">{{ __('Choose a UTF-8, comma-separated CSV file, up to 2 MB.') }}</p>
                        <label for="sessions-csv" class="form-label fw-medium">{{ __('CSV file') }}</label>
                        <input id="sessions-csv" type="file" name="file" accept=".csv" class="form-control" aria-describedby="sessions-csv-help" required>
                    </section>
                </div>
                <div class="card-footer bg-transparent border-top px-4 px-md-5 py-4">
                    <div class="d-flex justify-content-end flex-wrap gap-2">
                        <a href="{{ route('settings.imports') }}" class="btn btn-outline-secondary rounded-3">{{ __('Cancel') }}</a>
                        <button type="submit" class="btn btn-success rounded-3">{{ __('Import sessions') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</main>
@endsection
