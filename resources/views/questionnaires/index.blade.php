@extends('layouts.app')

@section('title', 'Questionnaires - Feedback')

@section('content')
<main class="container py-5">
    <header class="mb-4">
        <p class="text-success small fw-semibold text-uppercase mb-2">Course evaluations</p>
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-2">
            <h1 class="h2 fw-bold mb-0">Questionnaires</h1>
            <a href="{{ route('questionnaires.create') }}" class="btn btn-success rounded-3">New questionnaire</a>
        </div>
        <p class="text-body-secondary mb-0">Create reusable feedback questionnaires for your course sessions.</p>
    </header>

    <div class="card border-0 rounded-4 shadow-sm">
        <div class="card-body p-4 p-md-5 text-center">
            <span class="badge rounded-pill bg-success-subtle text-success-emphasis mb-3">Questionnaire library</span>
            <h2 class="h5 fw-semibold">Build your next feedback form</h2>
            <p class="text-body-secondary mb-4">Start with a name, then add questions and answer options.</p>
            <a href="{{ route('questionnaires.create') }}" class="btn btn-outline-success rounded-3">Create a questionnaire</a>
            <p class="small text-body-secondary mt-4 mb-0">Saved questionnaires and editing will be available here soon.</p>
        </div>
    </div>
</main>
@endsection
