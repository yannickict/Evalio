@php($workspace = auth()->check() && ! request()->routeIs('feedback.*'))
<nav aria-label="{{ __('Main navigation') }}">
    @if ($workspace)
    <aside class="app-sidebar offcanvas-lg offcanvas-start border-end bg-white" tabindex="-1" id="main-navigation" aria-labelledby="navigation-title">
        <div class="offcanvas-header border-bottom">
            <span class="fw-bold text-primary" id="navigation-title">Evalio</span>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#main-navigation" aria-label="{{ __('Close') }}"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column p-3">
            <a class="text-decoration-none text-primary fw-bold fs-4 px-3 pt-2 pb-4 d-none d-lg-block" href="{{ route('home') }}" aria-label="{{ __('Evalio home') }}">
                Evalio<span class="d-block small fw-normal text-body-secondary fs-6 mt-1">{{ __('Course evaluations') }}</span>
            </a>
            <div class="nav nav-pills flex-column gap-1">
                @foreach ([['home', 'home', 'Home'], ['sessions.index', 'sessions.*', 'Sessions'], ['courses.index', 'courses.*', 'Courses'], ['questionnaires.index', 'questionnaires.*', 'Questionnaires']] as [$route, $pattern, $label])
                <a href="{{ route($route) }}" @class(['nav-link px-3 py-2 fw-medium', 'active bg-primary-subtle text-primary-emphasis' => request()->routeIs($pattern), 'text-body-secondary' => ! request()->routeIs($pattern)]) @if(request()->routeIs($pattern)) aria-current="page" @endif>{{ __($label) }}</a>
                @endforeach
            </div>
            @can('manage-users')
            <div class="border-top mt-4 pt-3 nav nav-pills flex-column gap-1">
                <p class="small text-body-secondary px-3 mb-2">{{ __('Administration') }}</p>
                <a href="{{ route('users.index') }}" @class(['nav-link px-3 py-2 fw-medium', 'active bg-primary-subtle text-primary-emphasis' => request()->routeIs('users.*'), 'text-body-secondary' => ! request()->routeIs('users.*')]) @if(request()->routeIs('users.*')) aria-current="page" @endif>{{ __('Users') }}</a>
                @can('manage-settings')
                <a href="{{ route('settings.index') }}" @class(['nav-link px-3 py-2 fw-medium', 'active bg-primary-subtle text-primary-emphasis' => request()->routeIs('settings.*'), 'text-body-secondary' => ! request()->routeIs('settings.*')]) @if(request()->routeIs('settings.*')) aria-current="page" @endif>{{ __('Settings') }}</a>
                @endcan
            </div>
            @endcan
            <div class="mt-auto pt-4 px-3 small">
                <a class="text-body-secondary text-decoration-none" href="{{ route('profile.show') }}" @if(request()->routeIs('profile.*')) aria-current="page" @endif>{{ __('Account profile') }}</a>
                <form method="POST" action="{{ route('logout') }}" class="mt-2">
                    @csrf
                    <button class="btn btn-link text-body-secondary text-decoration-none p-0 small" type="submit">{{ __('Log out') }}</button>
                </form>
            </div>
        </div>
    </aside>
    @endif
    <header @class(['app-topbar bg-white border-bottom d-print-none', 'workspace-topbar' => $workspace])>
        <div class="d-flex align-items-center gap-3 px-3 px-lg-4 py-3">
            @if ($workspace)
            <button class="btn btn-outline-secondary btn-sm d-lg-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#main-navigation" aria-controls="main-navigation" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">{{ __('Menu') }}</button>
            <span class="small text-body-secondary d-none d-lg-inline">{{ __(match (true) {
                request()->routeIs('sessions.*') => 'Sessions',
                request()->routeIs('courses.*') => 'Courses',
                request()->routeIs('questionnaires.*') => 'Questionnaires',
                request()->routeIs('users.*') => 'Users',
                request()->routeIs('settings.*') => 'Settings',
                request()->routeIs('profile.*') => 'Account profile',
                default => 'Home',
            }) }}</span>
            <a class="text-primary fw-bold text-decoration-none d-lg-none" href="{{ route('home') }}">Evalio</a>
            @else
            <a class="text-primary fw-bold text-decoration-none fs-5" href="{{ route('home') }}" aria-label="{{ __('Evalio home') }}">Evalio</a>
            <span class="small text-body-secondary d-none d-sm-inline">{{ __('Course evaluations') }}</span>
            @endif
            <div class="d-flex align-items-center gap-3 ms-auto">
                <x-language-switch />
                @auth
                <div class="dropdown">
                    <button class="btn btn-light btn-sm border dropdown-toggle" type="button" id="profile-menu-toggle" data-bs-toggle="dropdown" aria-expanded="false" aria-label="{{ __('Account menu for :value1', ['value1' => auth()->user()->name]) }}">
                        <span class="d-none d-sm-inline">{{ auth()->user()->name }}</span>
                        <span class="d-sm-none">{{ mb_strtoupper(mb_substr(auth()->user()->first_name, 0, 1).mb_substr(auth()->user()->last_name, 0, 1)) }}</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="profile-menu-toggle">
                        <a class="dropdown-item" href="{{ route('profile.show') }}">{{ __('Account profile') }}</a>
                        <hr class="dropdown-divider">
                        <form method="POST" action="{{ route('logout') }}" class="m-0">
                            @csrf
                            <button class="dropdown-item" type="submit">{{ __('Log out') }}</button>
                        </form>
                    </div>
                </div>
                @else
                <a class="btn btn-outline-primary btn-sm" href="{{ route('login') }}">{{ __('Log in') }}</a>
                @endauth
            </div>
        </div>
    </header>
</nav>
