<header class="bg-white border-bottom">
    <nav class="navbar navbar-expand-md py-3" aria-label="Main navigation">
    <div class="container">
        <a class="navbar-brand text-success fw-bold fs-4 mb-0 me-4" href="{{ route('home') }}" aria-label="Feedback home">
            Feedback<span class="d-none d-lg-inline small fw-normal text-body-secondary fs-6 ms-3">Course evaluations</span>
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
                <a href="{{ route('overview') }}"
                   @class(['nav-link rounded-3 px-3 py-2 small fw-semibold', 'active bg-success-subtle text-success-emphasis' => request()->routeIs('overview')])
                   @if (request()->routeIs('overview')) aria-current="page" @endif>Overview</a>
                @if (auth()->user()->role?->name === 'admin')
                    <a href="{{ route('users') }}"
                       @class(['nav-link rounded-3 px-3 py-2 small fw-semibold', 'active bg-success-subtle text-success-emphasis' => request()->routeIs('users*')])
                       @if (request()->routeIs('users*')) aria-current="page" @endif>Users</a>
                @endif
                </div>
                <div class="d-flex align-items-center justify-content-between gap-3 ms-md-auto pt-3 pt-md-0 mt-2 mt-md-0 ps-md-4">
                    <span class="small text-body-secondary text-break">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}" class="m-0">
                    @csrf
                    <button class="btn btn-outline-secondary btn-sm rounded-3 px-3 text-nowrap" type="submit">Log out</button>
                </form>
                </div>
            </div>
            @else
                <a class="btn btn-success btn-sm rounded-3 px-4 py-2" href="{{ route('login') }}">Log in</a>
            @endauth
    </div>
    </nav>
</header>
