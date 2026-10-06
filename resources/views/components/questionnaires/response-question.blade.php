@props(['question', 'number'])

<fieldset class="card border-0 rounded-4 shadow-sm p-3 px-md-4 mb-3" aria-labelledby="question-title-{{ $question->id }}">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
        <span class="text-success small fw-semibold">Question {{ $number }}</span>
        <span class="text-body-secondary small">{{ $question->type === 'single_choice' ? 'Choose one answer' : 'In your own words' }}</span>
    </div>
    <h2 class="h5 fw-semibold mb-3" id="question-title-{{ $question->id }}">{{ $question->question_text }}</h2>

    @if ($question->type === 'single_choice')
    <div class="d-grid gap-2">
        @foreach ($question->options as $option)
        <label class="d-flex align-items-center gap-2 border rounded-3 px-3 py-2 bg-body-tertiary" for="question-{{ $question->id }}-option-{{ $option->id }}">
            <input class="form-check-input flex-shrink-0 mt-0" type="radio"
                id="question-{{ $question->id }}-option-{{ $option->id }}"
                name="answers[{{ $question->id }}]" value="{{ $option->id }}"
                @checked((string) old('answers.'.$question->id) === (string) $option->id)>
            <span>{{ $option->option_text }}</span>
        </label>
        @endforeach
    </div>
    @elseif ($question->type === 'free_text')
    <textarea class="form-control bg-body-tertiary rounded-3 px-3 py-2" rows="3"
        id="answer-{{ $question->id }}" name="answers[{{ $question->id }}]"
        aria-labelledby="question-title-{{ $question->id }}"
        placeholder="Share your thoughts…">{{ old('answers.'.$question->id) }}</textarea>
    @endif
    @if ($question->allows_comment)
    <div class="border-top mt-3 pt-3">
        <label class="form-label small fw-semibold mb-2" for="comment-{{ $question->id }}">
            Add a comment <span class="text-body-secondary fw-normal">(optional)</span>
        </label>
        <textarea class="form-control bg-body-tertiary rounded-3 px-3 py-2" rows="2"
            id="comment-{{ $question->id }}" name="answers_comment[{{ $question->id }}]"
            placeholder="What worked well, or what could be improved?">{{ old('answers_comment.'.$question->id) }}</textarea>
    </div>
    @endif
</fieldset>
