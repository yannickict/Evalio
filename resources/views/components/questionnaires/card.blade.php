@props(['template'])

<div class="col-12 col-md-6 col-xl-4">
    <article class="questionnaire-card card h-100 border-0 rounded-4 shadow-sm">
        <div class="card-body p-4">
            <p class="small text-success fw-semibold text-uppercase mb-2">Questionnaire library</p>
            <h2 class="h5 fw-semibold text-break mb-3">{{ $template->name }}</h2>
            <span class="badge rounded-pill bg-success-subtle text-success-emphasis">
                {{ $template->questions_count }} {{ $template->questions_count === 1 ? 'question' : 'questions' }}
            </span>
        </div>
        <div class="card-footer bg-transparent border-top px-4 py-3">
            <button class="btn btn-link link-success text-decoration-none fw-semibold small p-0 stretched-link"
                type="button" data-bs-toggle="modal" data-bs-target="#questionnaire-{{ $template->id }}"
                aria-label="View questionnaire {{ $template->name }}">
                View questionnaire <span aria-hidden="true">&rarr;</span>
            </button>
        </div>
    </article>
</div>
<x-ui.modal id="questionnaire-{{ $template->id }}" label="Questionnaire {{ $template->name }}" >
    {{ $template }}
</x-ui.modal>
