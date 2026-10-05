@extends('layouts.app')

@section('title', 'Questionnaires - Evalio')

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

    @if (session('status'))
    <div class="alert alert-success" role="status">{{ session('status') }}</div>
    @endif

    <div class="row g-4">
        @forelse ($templates as $template)
        <div class="col-12 col-md-6 col-xl-4">
            <article class="card h-100 border-0 rounded-4 shadow-sm">
                <div class="card-body p-4">
                    <p class="small text-success fw-semibold text-uppercase mb-2">Questionnaire library</p>
                    <h2 class="h5 fw-semibold text-break mb-3">{{ $template->name }}</h2>
                    <span class="badge rounded-pill bg-success-subtle text-success-emphasis">
                        {{ $template->questions_count }} {{ $template->questions_count === 1 ? 'question' : 'questions' }}
                    </span>
                </div>
            </article>
        </div>
        @empty
        <div class="col-12">
    <div class="card border-0 rounded-4 shadow-sm">
        <div class="card-body p-4 p-md-5 text-center">
            <span class="badge rounded-pill bg-success-subtle text-success-emphasis mb-3">Questionnaire library</span>
            <h2 class="h5 fw-semibold">Build your next feedback form</h2>
            <p class="text-body-secondary mb-4">Start with a name, then add questions and answer options.</p>
            <a href="{{ route('questionnaires.create') }}" class="btn btn-outline-success rounded-3">Create a questionnaire</a>
        </div>
    </div>
        </div>
        @endforelse
    </div>
</main>
@endsection
