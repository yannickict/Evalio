@extends('layouts.app')

@section('title', 'Import courses - Evalio')

@section('content')
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9 col-xl-8">
            <header class="mb-4">
                <a href="{{ route('settings.imports') }}" class="link-secondary text-decoration-none small d-inline-block mb-4">
                    <span aria-hidden="true">&larr;</span> Back to CSV imports
                </a>
                <p class="text-success small fw-semibold text-uppercase mb-2">Course evaluations</p>
                <h1 class="h2 fw-bold mb-2">Import courses</h1>
                <p class="text-body-secondary mb-0">Add courses from a CSV file using existing questionnaires.</p>
            </header>

            <x-ui.validation-errors :errors="$errors" />

            @isset($imported)
            <section class="card border-0 rounded-4 shadow-sm mb-4" aria-labelledby="import-results-heading">
                <div class="card-body p-4">
                    <h2 id="import-results-heading" class="h5 fw-semibold mb-3">Import results</h2>
                    <div class="alert alert-info mb-0" role="status">
                        Imported {{ $imported }} course(s). Skipped {{ count($failures) }} row(s).
                    </div>
                    @if ($failures)
                    <h3 class="h6 fw-semibold mt-4 mb-2">Failed rows</h3>
                    <ul class="small mb-0">
                        @foreach ($failures as $failure)
                        <li class="mb-1">Row {{ $failure['row'] }}: {{ $failure['message'] }}</li>
                        @endforeach
                    </ul>
                    @endif
                    <a href="{{ route('courses.index') }}" class="link-success small d-inline-block mt-3">View courses</a>
                </div>
            </section>
            @endisset

            <form method="POST" action="{{ route('settings.courses-import.store') }}" enctype="multipart/form-data" class="card border-0 rounded-4 shadow-sm">
                @csrf
                <div class="card-body p-4 p-md-5">
                    <section aria-labelledby="csv-format-heading" class="mb-4 pb-4 border-bottom">
                        <h2 id="csv-format-heading" class="h5 fw-semibold mb-1">Prepare your CSV</h2>
                        <p class="small text-body-secondary mb-3">Include these column headers. Each course must have a unique name.</p>
                        <div class="bg-body-tertiary rounded-3 p-3 mb-3 overflow-auto">
                            <code class="text-body text-nowrap">course_name,questionnaire_name</code>
                        </div>
                        <p class="small text-body-secondary mb-0">Questionnaires must already exist. Duplicate course names and invalid rows are skipped while valid rows are imported.</p>
                    </section>

                    <section aria-labelledby="csv-upload-heading">
                        <h2 id="csv-upload-heading" class="h5 fw-semibold mb-1">Upload your file</h2>
                        <p id="courses-csv-help" class="small text-body-secondary mb-3">Choose a UTF-8, comma-separated CSV file, up to 2 MB.</p>
                        <label for="courses-csv" class="form-label fw-medium">CSV file</label>
                        <input id="courses-csv" type="file" name="file" accept=".csv" class="form-control" aria-describedby="courses-csv-help" required>
                    </section>
                </div>
                <div class="card-footer bg-transparent border-top px-4 px-md-5 py-4">
                    <div class="d-flex justify-content-end flex-wrap gap-2">
                        <a href="{{ route('settings.imports') }}" class="btn btn-outline-secondary rounded-3">Cancel</a>
                        <button type="submit" class="btn btn-success rounded-3">Import courses</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</main>
@endsection
