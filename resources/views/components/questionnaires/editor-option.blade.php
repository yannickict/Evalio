@props(['index', 'optionIndex', 'option', 'optionCount'])

<div class="mb-2">
    <label
        for="question-{{ $index }}-option-{{ $optionIndex }}"
        class="form-label small text-body-secondary">
        {{ __('Option') }} {{ $optionIndex + 1 }}
    </label>

    <div class="input-group">
        <input
            id="question-{{ $index }}-option-{{ $optionIndex }}"
            name="questions[{{ $index }}][options][{{ $optionIndex }}]"
            type="text"
            value="{{ $option }}"
            class="form-control">
        <button type="submit" name="action" value="remove_option:{{ $index }}:{{ $optionIndex }}"
            class="btn btn-outline-danger editor-remove"
            @disabled($optionCount <= 2)
            aria-label="{{ $optionCount <= 2 ? __('Cannot remove: minimum two options required') : __('Remove option :option from question :question', ['option' => $optionIndex + 1, 'question' => $index + 1]) }}">
            {{ __('Remove') }}
        </button>
    </div>
</div>
