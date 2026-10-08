<form id="language-switch" method="POST" action="{{ route('language.update') }}" class="d-inline-flex flex-shrink-0"
    data-error="{{ __('The language could not be changed. Please try again.') }}">
    @csrf
    <input type="hidden" name="return_to" value="{{ request()->routeIs('questionnaires.preview') ? route('questionnaires.create', [], false) : request()->getRequestUri() }}">
    <div class="language-switch-controls" role="group" aria-label="{{ __('Language') }}">
        @foreach (['de' => 'Deutsch', 'en' => 'English'] as $locale => $label)
        <button type="submit" name="locale" value="{{ $locale }}" lang="{{ $locale }}"
            class="language-switch-option"
            aria-label="{{ $label }}" title="{{ $label }}"
            aria-pressed="{{ app()->getLocale() === $locale ? 'true' : 'false' }}"
            @disabled(app()->getLocale() === $locale)>{{ strtoupper($locale) }}</button>
        @if (! $loop->last)<span class="language-switch-divider" aria-hidden="true">/</span>@endif
        @endforeach
    </div>
</form>
