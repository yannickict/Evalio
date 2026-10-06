<header class="sticky-top bg-white border-bottom">
    <nav class="navbar navbar-expand-md py-3" aria-label="Main navigation">
    <div class="container">
        <a class="navbar-brand text-success fw-bold fs-4 mb-0 me-4" href="{{ route('home') }}" aria-label="Evalio home">
            Evalio<span class="d-none d-lg-inline small fw-normal text-body-secondary fs-6 ms-3">Course evaluations</span>
        </a>
            @auth
            <button class="navbar-toggler border-0 p-2" type="button" data-bs-toggle="collapse"
                data-bs-target="#main-navigation" aria-controls="main-navigation" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="main-navigation">
                <div class="navbar-nav gap-1 mt-3 mt-md-0">
                <a href="{{ route('home') }}"
                   @class(['nav-link rounded-3 px-3 py-2 small fw-semibold', 'active bg-success-subtle text-success-emphasis' => request()->routeIs('home')])
                   @if (request()->routeIs('home')) aria-current="page" @endif>Home</a>
                <a href="{{ route('sessions.index') }}"
                   @class(['nav-link rounded-3 px-3 py-2 small fw-semibold', 'active bg-success-subtle text-success-emphasis' => request()->routeIs('sessions.index')])
                   @if (request()->routeIs('sessions.index')) aria-current="page" @endif>Overview</a>
                <a href="{{ route('courses.index') }}"
                   @class(['nav-link rounded-3 px-3 py-2 small fw-semibold', 'active bg-success-subtle text-success-emphasis' => request()->routeIs('courses.*')])
                   @if (request()->routeIs('courses.index')) aria-current="page" @endif>Courses</a>
                <a href="{{ route('questionnaires.index') }}"
                   @class(['nav-link rounded-3 px-3 py-2 small fw-semibold', 'active bg-success-subtle text-success-emphasis' => request()->routeIs('questionnaires.*')])
                   @if (request()->routeIs('questionnaires.index')) aria-current="page" @endif>Questionnaires</a>
                @can('manage-users')
                    <a href="{{ route('users.index') }}"
                       @class(['nav-link rounded-3 px-3 py-2 small fw-semibold', 'active bg-success-subtle text-success-emphasis' => request()->routeIs('users.*')])
                       @if (request()->routeIs('users.index')) aria-current="page" @endif>Users</a>
                @endcan
                </div>
                <form method="POST" action="{{ route('logout') }}" class="d-md-none border-top mt-3 pt-3">
                    @csrf
                    <button class="btn btn-outline-secondary rounded-3 w-100 py-2" type="submit">Log out</button>
                </form>
                <div class="dropdown d-none d-md-block ms-md-auto ps-md-4">
                    <button class="user-avatar btn d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success-emphasis fw-bold lh-1 flex-shrink-0 p-0"
                        type="button" id="profile-menu-toggle" data-bs-toggle="dropdown" aria-expanded="false"
                        aria-label="Account menu for {{ auth()->user()->name }}">
                        {{ mb_strtoupper(mb_substr(auth()->user()->first_name, 0, 1).mb_substr(auth()->user()->last_name, 0, 1)) }}
                    </button>
                    <div class="dropdown-menu dropdown-menu-md-end" aria-labelledby="profile-menu-toggle">
                        <form method="POST" action="{{ route('logout') }}" class="m-0">
                            @csrf
                            <button class="dropdown-item" type="submit">Log out</button>
                        </form>
                    </div>
                </div>
            </div>
            @else
                <a class="btn btn-success btn-sm rounded-3 px-4 py-2" href="{{ route('login') }}">Log in</a>
            @endauth
    </div>
    </nav>
</header>
