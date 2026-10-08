<form id="language-switch" method="POST" action="{{ route('language.update') }}" class="d-inline-flex flex-shrink-0"
    data-error="{{ __('The language could not be changed. Please try again.') }}">
    @csrf
    <input type="hidden" name="return_to" value="{{ request()->routeIs('questionnaires.preview') ? route('questionnaires.create', [], false) : request()->getRequestUri() }}">
    <div class="btn-group btn-group-sm" role="group" aria-label="{{ __('Language') }}">
        @foreach (['de' => 'Deutsch', 'en' => 'English'] as $locale => $label)
        <button type="submit" name="locale" value="{{ $locale }}" lang="{{ $locale }}"
            @class(['btn', 'btn-success' => app()->getLocale() === $locale, 'btn-outline-success' => app()->getLocale() !== $locale])
            aria-pressed="{{ app()->getLocale() === $locale ? 'true' : 'false' }}"
            @disabled(app()->getLocale() === $locale)>{{ $label }}</button>
        @endforeach
    </div>
</form>
