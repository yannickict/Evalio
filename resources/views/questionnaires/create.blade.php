@extends('layouts.app')

@section('title', 'New questionnaire - Evalio')

@section('content')
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

            <section class="card border-0 rounded-4 shadow-sm mb-4" aria-labelledby="questionnaire-details-heading">
                <div class="card-body p-4">
                    <h2 id="questionnaire-details-heading" class="h5 fw-semibold mb-1">Questionnaire details</h2>
                    <p class="small text-body-secondary mb-4">Choose a clear name so you can find this questionnaire later.</p>
                    <label for="questionnaire-name" class="form-label fw-medium">Questionnaire name</label>
                    <input id="questionnaire-name" name="name" type="text" class="form-control" placeholder="e.g. End-of-course feedback">
                </div>
            </section>

            <div class="d-flex align-items-center justify-content-between gap-3 mb-3">
                <h2 class="h5 fw-semibold mb-0">Questions</h2>
                <span class="badge rounded-pill bg-success-subtle text-success-emphasis">2 sample questions</span>
            </div>
            <p id="question-editor-note" class="small text-body-secondary mb-3">These sample fields show the question layout. Adding and removing questions will be available soon.</p>

            <section class="card border-0 rounded-4 shadow-sm mb-3" aria-labelledby="question-one-heading">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
                        <h3 id="question-one-heading" class="h6 text-success fw-semibold mb-0">Question 1</h3>
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-3" disabled aria-label="Remove question 1" aria-describedby="question-editor-note">Remove</button>
                    </div>
                    <div class="mb-3">
                        <label for="question-one-text" class="form-label fw-medium">Question</label>
                        <input id="question-one-text" type="text" class="form-control" placeholder="e.g. How would you rate this course?">
                    </div>
                    <div class="mb-4">
                        <label for="question-one-type" class="form-label fw-medium">Answer type</label>
                        <select id="question-one-type" class="form-select">
                            <option value="single_choice" selected>Single choice</option>
                            <option value="free_text">Free text</option>
                        </select>
                    </div>
                    <fieldset id="question-one-options" class="mb-4">
                        <legend class="fs-6 fw-medium mb-2">Answer options</legend>
                        <div class="d-grid gap-2">
                            <div class="input-group">
                                <label for="question-one-option-one" class="input-group-text">1</label>
                                <input id="question-one-option-one" type="text" class="form-control" placeholder="e.g. Excellent">
                            </div>
                            <div class="input-group">
                                <label for="question-one-option-two" class="input-group-text">2</label>
                                <input id="question-one-option-two" type="text" class="form-control" placeholder="e.g. Good">
                            </div>
                            <div class="input-group">
                                <label for="question-one-option-three" class="input-group-text">3</label>
                                <input id="question-one-option-three" type="text" class="form-control" placeholder="e.g. Could be better">
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-secondary btn-sm rounded-3 mt-3" disabled aria-describedby="question-editor-note">Add option</button>
                    </fieldset>
                    <div id="question-one-preview" class="bg-body-tertiary border rounded-3 p-3" hidden>
                        <p class="small text-body-secondary mb-2">Answer preview</p>
                        <label for="question-one-free-text" class="visually-hidden">Participant free-text answer preview</label>
                        <textarea id="question-one-free-text" class="form-control" rows="3" placeholder="Participants will write their answer here" disabled></textarea>
                    </div>
                    <div id="question-one-comment-options" class="form-check border-top pt-3">
                        <input id="question-one-comment" type="checkbox" class="form-check-input">
                        <label for="question-one-comment" class="form-check-label">Allow an optional comment</label>
                    </div>
                </div>
            </section> 

            <button type="button" class="btn btn-outline-success rounded-3 w-100 py-3 mb-4" disabled aria-describedby="question-editor-note">+ Add question</button>

            <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 border-top pt-4">
                <p id="questionnaire-save-note" class="small text-body-secondary mb-0">Saving questionnaires will be available soon.</p>
                <div class="d-flex gap-2">
                    <a href="{{ route('questionnaires.index') }}" class="btn btn-outline-secondary rounded-3">Cancel</a>
                    <button type="button" class="btn btn-success rounded-3" disabled aria-describedby="questionnaire-save-note">Create questionnaire</button>
                </div>
            </div>
        </div>
    </div>
</main>
@endsection