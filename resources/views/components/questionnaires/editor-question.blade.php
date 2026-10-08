@props(['question', 'index', 'questionCount'])

<section class="card border-0 rounded-4 shadow-sm mb-3" aria-labelledby="question-{{ $index }}-heading">
    <div class="card-body p-4">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
            <h3 id="question-{{ $index }}-heading" class="h6 text-success fw-semibold mb-0">{{ __('Question') }} {{ $index + 1 }}</h3>
            <button type="submit" name="action" value="remove_question:{{ $index }}"
                class="btn btn-outline-danger btn-sm rounded-3 editor-remove" @disabled($questionCount <= 1)
                aria-label="{{ __('Remove question :value1', ['value1' => $index + 1]) }}">{{ __('Remove question') }}</button>
        </div>

        <div class="mb-3">
            <label
                for="question-{{ $index }}-text"
                class="form-label fw-medium">
                {{ __('Your question') }}
            </label>

            <input
                id="question-{{ $index }}-text"
                name="questions[{{ $index }}][text]"
                type="text"
                value="{{ $question['text'] }}"
                class="form-control">
        </div>

        <div class="mb-3">
            <label
                for="question-{{ $index }}-type"
                class="form-label fw-medium">
                {{ __('Answer type') }}
            </label>

            <select
                data-answer-type
                id="question-{{ $index }}-type"
                name="questions[{{ $index }}][type]"
                class="form-select">
                <option
                    value="single_choice"
                    @selected($question['type']==='single_choice' )>
                    {{ __('Single choice') }}
                </option>

                <option
                    value="free_text"
                    @selected($question['type']==='free_text' )>
                    {{ __('Free text') }}
                </option>
            </select>
        </div>

        @if ($question['type'] === 'single_choice')
        <fieldset>
            <legend class="fs-6 fw-medium">{{ __('Answer options') }}</legend>

            @foreach ($question['options'] as $optionIndex => $option)
            <x-questionnaires.editor-option :index="$index" :option-index="$optionIndex" :option="$option" :option-count="count($question['options'])" />
            @endforeach
            <button type="submit" name="action" value="add_option:{{ $index }}"
                class="btn btn-outline-success btn-sm rounded-3 mt-2" @disabled(count($question['options']) >= 20)>{{ __('+ Add option') }}</button>
        </fieldset>
        @else
        <div class="bg-body-tertiary border rounded-3 p-3">
        <label for="question-{{ $index }}-preview" class="form-label small text-body-secondary">{{ __('Answer preview') }}</label>
        <textarea id="question-{{ $index }}-preview" class="form-control" rows="3"
            placeholder="{{ __('Participants will write their answer here') }}" disabled></textarea>
        </div>

        @foreach ($question['options'] as $optionIndex => $option)
        <input
            type="hidden"
            name="questions[{{ $index }}][options][{{ $optionIndex }}]"
            value="{{ $option }}">
        @endforeach
        @endif
        <div class="border-top mt-4 pt-3">
            <input type="hidden" name="questions[{{ $index }}][allows_comment]" value="0">
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" role="switch"
                    id="question-{{ $index }}-comment" name="questions[{{ $index }}][allows_comment]" value="1"
                    @checked($question['allows_comment'] ?? false)
                    aria-describedby="question-{{ $index }}-comment-hint">
                <label class="form-check-label fw-medium" for="question-{{ $index }}-comment">{{ __('Allow an optional comment') }}</label>
            </div>
            <p class="small text-body-secondary mt-1 mb-0" id="question-{{ $index }}-comment-hint">{{ __('Participants can add a comment alongside their answer to this question.') }}</p>
        </div>
    </div>
</section>
