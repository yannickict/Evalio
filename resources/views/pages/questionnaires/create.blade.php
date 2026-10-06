@extends('layouts.app')

@section('title', 'New questionnaire - Evalio')

@section('content')
@php
$draft['name'] = old('name', $draft['name']) ?? '';
$draft['questions'] = old('questions', $draft['questions']);

foreach ($draft['questions'] as &$question) {
$question['text'] = $question['text'] ?? '';
$question['options'] = $question['options'] ?? ['', ''];
}

unset($question);
@endphp
<main class="container py-5">
    <div class="row justify-content-center">
    <div class="col-12 col-lg-9 col-xl-8">
    <header class="mb-4">
        <a href="{{ route('questionnaires.index') }}" class="link-secondary text-decoration-none small d-inline-block mb-4">
            <span aria-hidden="true">&larr;</span> Back to questionnaires
        </a>
        <p class="text-success small fw-semibold text-uppercase mb-2">Course evaluations</p>
        <h1 class="h2 fw-bold mb-2">New questionnaire</h1>
        <p class="text-body-secondary mb-0">Build a feedback form you can reuse across course sessions.</p>
    </header>
    <x-ui.validation-errors :errors="$errors" />
    <form method="POST" action="{{ route('questionnaires.preview') }}">
        @csrf

        <section class="card border-0 rounded-4 shadow-sm mb-4" aria-labelledby="questionnaire-details-heading">
        <div class="card-body p-4">
            <h2 id="questionnaire-details-heading" class="h5 fw-semibold mb-1">Questionnaire details</h2>
            <p class="small text-body-secondary mb-4">Choose a clear name so you can find this questionnaire later.</p>
            <label for="questionnaire-name" class="form-label fw-medium">
                Questionnaire name
            </label>

            <input
                id="questionnaire-name"
                name="name"
                type="text"
                value="{{ $draft['name'] }}"
                placeholder="e.g. End-of-course feedback"
                class="form-control">
        </div>
        </section>

        <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
            <h2 class="h5 fw-semibold mb-0">Questions</h2>
            <span class="badge rounded-pill bg-success-subtle text-success-emphasis">
                {{ count($draft['questions']) }} {{ count($draft['questions']) === 1 ? 'question' : 'questions' }}
            </span>
        </div>

        @foreach ($draft['questions'] as $index => $question)
        <x-questionnaires.editor-question :question="$question" :index="$index" :question-count="count($draft['questions'])" />
        @endforeach

        <div class="d-grid gap-2 mb-4">
            <button
                type="submit"
                name="action"
                value="refresh"
                id="refresh-answer-types"
                class="btn btn-outline-secondary rounded-3">
                Update answer types
            </button>

            <button
                type="submit"
                name="action"
                value="add_question"
                class="btn btn-outline-success rounded-3 py-3" @disabled(count($draft['questions']) >= 50)>
                + Add question
            </button>
        </div>
        <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 border-top pt-4">
            <p class="small text-body-secondary mb-0">Save your template when all questions are ready.</p>
            <div class="d-flex gap-2">
            <a href="{{ route('questionnaires.index') }}" class="btn btn-outline-secondary rounded-3">Cancel</a>
            <button type="submit" formaction="{{ route('questionnaires.store') }}"
                class="btn btn-success rounded-3">Save questionnaire</button>
            </div>
        </div>
    </form>
    </div>
    </div>
</main>
@endsection
