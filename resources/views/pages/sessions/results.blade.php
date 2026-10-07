@extends('layouts.app')

@section('title', 'Evaluation results - Evalio')

@section('content')
<main class="container py-5 evaluation-results">
    <header class="mb-4">
        <a class="link-secondary text-decoration-none small d-inline-block mb-4 d-print-none"
            href="{{ route('sessions.index', ['session' => $courseSession->id]) }}">
            <span aria-hidden="true">&larr;</span> Back to session
        </a>
        <p class="small text-success fw-semibold text-uppercase mb-2">{{ $courseSession->session_identifier }}</p>
        <h1 class="h2 fw-bold mb-2">Evaluation results</h1>
        <p class="text-body-secondary mb-0">{{ $courseSession->course->name }}</p>
        <p class="small text-body-secondary mt-1 mb-0">{{ $courseSession->start_date->format('d M Y') }} &ndash; {{ $courseSession->end_date->format('d M Y') }}</p>
        <button class="btn btn-outline-success rounded-3 mt-3 d-print-none" type="button" onclick="window.print()">Print / Save as PDF</button>
        <p class="small text-body-secondary mt-2 mb-0 d-print-none">The print summary includes charts and response counts. Written responses and comments are shown below.</p>
    </header>

    @if (! $hasAnswers)
    <p class="alert alert-info">No answers have been received for this session yet.</p>
    @endif

    <div class="results-grid">
        @forelse ($results as $result)
        <section class="card border-0 rounded-4 shadow-sm p-4 results-card">
            <p class="small text-success fw-semibold mb-2">Question {{ $loop->iteration }}</p>
            <h2 class="h5 text-break mb-3">{{ $result['question']->question_text }}</h2>

            @if ($result['question']->type === 'single_choice')
            <x-questionnaires.results-pie :options="$result['options']" />
            @else
            <p class="small text-body-secondary mb-0">{{ $result['responses']->count() }} written {{ $result['responses']->count() === 1 ? 'response' : 'responses' }}</p>
            <p class="small text-body-secondary mb-0 d-none d-print-block">Read written responses in the full results view.</p>
            <ul class="mb-0 mt-2 d-print-none">
                @forelse ($result['responses'] as $response)
                <li class="text-break" style="white-space: pre-wrap">{{ $response }}</li>
                @empty
                <li class="text-body-secondary">No written answers yet.</li>
                @endforelse
            </ul>
            @endif

            @if ($result['comments']->isNotEmpty())
            <h3 class="h6 border-top pt-3 mt-3 d-print-none">Comments</h3>
            <ul class="mb-0 d-print-none">
                @foreach ($result['comments'] as $comment)
                <li class="text-break" style="white-space: pre-wrap">{{ $comment }}</li>
                @endforeach
            </ul>
            @endif
        </section>
        @empty
        <p class="text-body-secondary">No questionnaire questions are assigned to this session.</p>
        @endforelse
    </div>
</main>
@endsection
