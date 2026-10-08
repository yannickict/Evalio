<header class="sticky-top bg-white border-bottom">
    <nav class="navbar navbar-expand-md py-3" aria-label="{{ __('Main navigation') }}">
    <div class="container">
        <a class="navbar-brand text-success fw-bold fs-4 mb-0 me-4" href="{{ route('home') }}" aria-label="{{ __('Evalio home') }}">
            Evalio<span class="d-none d-lg-inline small fw-normal text-body-secondary fs-6 ms-3">{{ __('Course evaluations') }}</span>
        </a>
            @auth
            <button class="navbar-toggler border-0 p-2" type="button" data-bs-toggle="collapse"
                data-bs-target="#main-navigation" aria-controls="main-navigation" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="main-navigation">
                <div class="navbar-nav gap-1 mt-3 mt-md-0">
                <a href="{{ route('home') }}"
                   @class(['nav-link rounded-3 px-3 py-2 small fw-semibold', 'active bg-success-subtle text-success-emphasis' => request()->routeIs('home')])
                   @if (request()->routeIs('home')) aria-current="page" @endif>{{ __('Home') }}</a>
                <a href="{{ route('sessions.index') }}"
                   @class(['nav-link rounded-3 px-3 py-2 small fw-semibold', 'active bg-success-subtle text-success-emphasis' => request()->routeIs('sessions.*')])
                   @if (request()->routeIs('sessions.index')) aria-current="page" @endif>{{ __('Sessions') }}</a>
                <a href="{{ route('courses.index') }}"
                   @class(['nav-link rounded-3 px-3 py-2 small fw-semibold', 'active bg-success-subtle text-success-emphasis' => request()->routeIs('courses.*')])
                   @if (request()->routeIs('courses.index')) aria-current="page" @endif>{{ __('Courses') }}</a>
                <a href="{{ route('questionnaires.index') }}"
                   @class(['nav-link rounded-3 px-3 py-2 small fw-semibold', 'active bg-success-subtle text-success-emphasis' => request()->routeIs('questionnaires.*')])
                   @if (request()->routeIs('questionnaires.index')) aria-current="page" @endif>{{ __('Questionnaires') }}</a>
                @can('manage-users')
                    <a href="{{ route('users.index') }}"
                       @class(['nav-link rounded-3 px-3 py-2 small fw-semibold', 'active bg-success-subtle text-success-emphasis' => request()->routeIs('users.*')])
                       @if (request()->routeIs('users.index')) aria-current="page" @endif>{{ __('Users') }}</a>
                @endcan
                </div>
                <div class="d-md-none border-top mt-3 pt-3 d-flex gap-2">
                <a class="btn btn-outline-success rounded-3 flex-fill py-2" href="{{ route('profile.show') }}">{{ __('Account profile') }}</a>
                @can('manage-settings')
                <a class="settings-toggle btn btn-outline-secondary rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0 p-0" href="{{ route('settings.index') }}" aria-label="{{ __('Settings') }}">
<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" aria-hidden="true">
                            <polygon points="12.000,4.200 13.305,2.086 14.588,2.341 15.827,2.761 17.000,3.340 16.748,5.812 17.515,6.485 19.934,5.912 20.660,7.000 21.239,8.173 21.659,9.412 19.733,10.982 19.800,12.000 21.914,13.305 21.659,14.588 21.239,15.827 20.660,17.000 18.188,16.748 17.515,17.515 18.088,19.934 17.000,20.660 15.827,21.239 14.588,21.659 13.018,19.733 12.000,19.800 10.695,21.914 9.412,21.659 8.173,21.239 7.000,20.660 7.252,18.188 6.485,17.515 4.066,18.088 3.340,17.000 2.761,15.827 2.341,14.588 4.267,13.018 4.200,12.000 2.086,10.695 2.341,9.412 2.761,8.173 3.340,7.000 5.812,7.252 6.485,6.485 5.912,4.066 7.000,3.340 8.173,2.761 9.412,2.341 10.982,4.267"/>
                            <circle cx="12" cy="12" r="3.5"/>
                        </svg>
                </a>
                @endcan
                <form method="POST" action="{{ route('logout') }}" class="d-md-none flex-fill">
                    @csrf
                    <button class="btn btn-outline-secondary rounded-3 w-100 py-2" type="submit">{{ __('Log out') }}</button>
                </form>
                </div>
                <div class="dropdown d-none d-md-block ms-md-auto ps-md-4">
                    @can('manage-settings')
                    <a class="settings-toggle btn btn-outline-secondary rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0 p-0 me-2" href="{{ route('settings.index') }}" aria-label="{{ __('Settings') }}" title="{{ __('Settings') }}">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" aria-hidden="true">
                            <polygon points="12.000,4.200 13.305,2.086 14.588,2.341 15.827,2.761 17.000,3.340 16.748,5.812 17.515,6.485 19.934,5.912 20.660,7.000 21.239,8.173 21.659,9.412 19.733,10.982 19.800,12.000 21.914,13.305 21.659,14.588 21.239,15.827 20.660,17.000 18.188,16.748 17.515,17.515 18.088,19.934 17.000,20.660 15.827,21.239 14.588,21.659 13.018,19.733 12.000,19.800 10.695,21.914 9.412,21.659 8.173,21.239 7.000,20.660 7.252,18.188 6.485,17.515 4.066,18.088 3.340,17.000 2.761,15.827 2.341,14.588 4.267,13.018 4.200,12.000 2.086,10.695 2.341,9.412 2.761,8.173 3.340,7.000 5.812,7.252 6.485,6.485 5.912,4.066 7.000,3.340 8.173,2.761 9.412,2.341 10.982,4.267"/>
                            <circle cx="12" cy="12" r="3.5"/>
                        </svg>
                    </a>
                    @endcan
                    <button class="user-avatar btn d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success-emphasis fw-bold lh-1 flex-shrink-0 p-0"
                        type="button" id="profile-menu-toggle" data-bs-toggle="dropdown" aria-expanded="false"
                        aria-label="{{ __('Account menu for :value1', ['value1' => auth()->user()->name]) }}">
                        {{ mb_strtoupper(mb_substr(auth()->user()->first_name, 0, 1).mb_substr(auth()->user()->last_name, 0, 1)) }}
                    </button>
                    <div class="dropdown-menu dropdown-menu-md-end" aria-labelledby="profile-menu-toggle">
                        <a class="dropdown-item" href="{{ route('profile.show') }}" @if (request()->routeIs('profile.show')) aria-current="page" @endif>{{ __('Account profile') }}</a>
                        <hr class="dropdown-divider">
                        <form method="POST" action="{{ route('logout') }}" class="m-0">
                            @csrf
                            <button class="dropdown-item" type="submit">{{ __('Log out') }}</button>
                        </form>
                    </div>
                </div>
            </div>
            @else
                <a class="btn btn-success btn-sm rounded-3 px-4 py-2" href="{{ route('login') }}">{{ __('Log in') }}</a>
            @endauth
    </div>
    </nav>
    <div class="container d-flex justify-content-end pb-2">
        <x-language-switch />
    </div>
</header>
