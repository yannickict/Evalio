@props(['template'])

<x-ui.modal id="questionnaire-{{ $template->id }}" labelledby="questionnaire-title-{{ $template->id }}" edit-permission="edit-questionnaires">
    <x-slot:header>
        <div class="pe-3">
            <p class="small text-success fw-semibold mb-1">Questionnaire preview</p>
            <h2 class="modal-title fs-4 fw-bold text-break mb-0" id="questionnaire-title-{{ $template->id }}">{{ $template->name }}</h2>
        </div>
    </x-slot:header>

    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
        <p class="small text-body-secondary mb-0">Questions and answer options</p>
        <span class="badge rounded-pill bg-success-subtle text-success-emphasis">
            {{ $template->questions->count() }} {{ $template->questions->count() === 1 ? 'question' : 'questions' }}
        </span>
    </div>

    <ol class="list-unstyled d-grid gap-3 mb-0">
        @forelse ($template->questions as $question)
            <x-questionnaires.preview-question :question="$question" :number="$loop->iteration" :template-id="$template->id" />
        @empty
            <li class="border rounded-4 bg-body-tertiary p-4 text-center">
                <h3 class="h5">No questions yet</h3>
                <p class="small text-body-secondary mb-0">This questionnaire has no questions to preview.</p>
            </li>
        @endforelse
    </ol>
</x-ui.modal>
