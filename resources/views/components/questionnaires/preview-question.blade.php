@props(['question', 'number', 'templateId'])

<li class="border rounded-4 p-3 p-sm-4" aria-labelledby="preview-question-{{ $templateId }}-{{ $question->id }}">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
        <span class="small text-success fw-semibold">Question {{ $number }}</span>
        <span class="badge rounded-pill text-body-secondary bg-body-tertiary border fw-normal">
            {{ $question->type === 'single_choice' ? 'Single choice' : 'Free text' }}
        </span>
    </div>
    <h3 class="h6 fw-semibold text-break mb-3" id="preview-question-{{ $templateId }}-{{ $question->id }}">{{ $question->question_text }}</h3>

    @if ($question->type === 'single_choice')
        <ul class="list-unstyled d-grid gap-2 mb-0">
            @forelse ($question->options as $option)
                <li class="d-flex align-items-start gap-2 border rounded-3 bg-body-tertiary px-3 py-2">
                    <span class="text-success flex-shrink-0" aria-hidden="true">&#9675;</span>
                    <span class="small text-break">{{ $option->option_text }}</span>
                </li>
            @empty
                <li class="small text-body-secondary">No answer options yet.</li>
            @endforelse
        </ul>
    @elseif ($question->type === 'free_text')
        <textarea class="form-control bg-body-tertiary rounded-3 small" rows="3"
            aria-labelledby="preview-question-{{ $templateId }}-{{ $question->id }}"
            placeholder="Participants can write their answer here" disabled></textarea>
    @endif

    @if ($question->allows_comment)
        <div class="border-top pt-3 mt-3">
            <label class="form-label small fw-medium" for="preview-comment-{{ $templateId }}-{{ $question->id }}">
                Comment <span class="text-body-secondary fw-normal">(optional)</span>
            </label>
            <textarea class="form-control bg-body-tertiary rounded-3 small" rows="2"
                id="preview-comment-{{ $templateId }}-{{ $question->id }}"
                placeholder="Participants can add a comment" disabled></textarea>
        </div>
    @endif
</li>
