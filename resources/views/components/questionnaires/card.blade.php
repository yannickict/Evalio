@props(['template'])

<div class="col-12">
    <article class="questionnaire-card border-bottom bg-white px-3 px-lg-4 py-3">
        <div class="row g-3 align-items-center">
            <div class="col-12 col-md-7"><h2 class="h6 fw-semibold mb-0 text-break">{{ $template->name }}</h2></div>
            <div class="col-6 col-md-2 small text-body-secondary">{{ $template->questions_count }} {{ $template->questions_count === 1 ? __('question') : __('questions') }}</div>
            <div class="col-6 col-md-3 text-end">
                <button class="btn btn-link btn-sm px-0 text-decoration-none" type="button" data-bs-toggle="modal" data-bs-target="#questionnaire-{{ $template->id }}" aria-label="{{ __('View questionnaire :value1', ['value1' => $template->name]) }}">{{ __('View questionnaire') }}</button>
            </div>
        </div>
    </article>
</div>
<x-questionnaires.preview-modal :template="$template" />
