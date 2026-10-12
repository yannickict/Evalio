@props(['question', 'number'])

<fieldset class="border-bottom bg-white p-3 p-md-4 mb-0" aria-labelledby="question-title-{{ $question->id }}">
    <legend class="fs-6 fw-semibold mb-2" id="question-title-{{ $question->id }}"><span class="d-block small text-primary mb-2">{{ __('Question') }} {{ $number }}</span>{{ $question->question_text }}</legend>
    <p class="text-body-secondary small mb-3">{{ $question->type === 'single_choice' ? __('Choose one answer') : __('In your own words') }}</p>
    @if ($question->type === 'single_choice')
    <div class="d-grid gap-2">
        @foreach ($question->options as $option)
        <label class="response-option d-flex align-items-center gap-2 border rounded-2 px-3 py-2 bg-body-tertiary" for="question-{{ $question->id }}-option-{{ $option->id }}">
            <input class="form-check-input flex-shrink-0 mt-0" type="radio"
                id="question-{{ $question->id }}-option-{{ $option->id }}"
                name="answers[{{ $question->id }}]" value="{{ $option->id }}"
                @checked((string) old('answers.'.$question->id) === (string) $option->id)>
            <span>{{ $option->option_text }}</span>
        </label>
        @endforeach
    </div>
    @elseif ($question->type === 'free_text')
    <textarea class="form-control bg-body-tertiary rounded-2 px-3 py-2" rows="3"
        id="answer-{{ $question->id }}" name="answers[{{ $question->id }}]"
        aria-labelledby="question-title-{{ $question->id }}"
        placeholder="{{ __('Share your thoughts…') }}">{{ old('answers.'.$question->id) }}</textarea>
    @endif
    @if ($question->allows_comment)
    <div class="border-top mt-3 pt-3">
        <label class="form-label small fw-semibold mb-2" for="comment-{{ $question->id }}">
            {{ __('Add a comment') }} <span class="text-body-secondary fw-normal">{{ __('(optional)') }}</span>
        </label>
        <textarea class="form-control bg-body-tertiary rounded-2 px-3 py-2" rows="2"
            id="comment-{{ $question->id }}" name="answers_comment[{{ $question->id }}]"
            placeholder="{{ __('What worked well, or what could be improved?') }}">{{ old('answers_comment.'.$question->id) }}</textarea>
    </div>
    @endif
</fieldset>
