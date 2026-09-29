<header class="bg-white border-bottom shadow-sm">
    <div class="container py-3 d-flex align-items-center justify-content-between flex-wrap gap-3">
        <a class="text-decoration-none text-success fw-bold fs-4 lh-sm" href="{{ route('home') }}" aria-label="Feedback home">
            Feedback<span class="d-block small fw-normal text-body-secondary fs-6">Course evaluations</span>
        </a>
        <nav class="nav nav-pills align-items-center flex-wrap gap-2" aria-label="Main navigation">
            @auth
                <a href="{{ route('home') }}"
                   @class(['nav-link rounded-pill px-3 fw-semibold', 'active bg-success text-white' => request()->routeIs('home'), 'link-success' => ! request()->routeIs('home')])
                   @if (request()->routeIs('home')) aria-current="page" @endif>Home</a>
                <a href="{{ route('overview') }}"
                   @class(['nav-link rounded-pill px-3 fw-semibold', 'active bg-success text-white' => request()->routeIs('overview'), 'link-success' => ! request()->routeIs('overview')])
                   @if (request()->routeIs('overview')) aria-current="page" @endif>Overview</a>
                @if (auth()->user()->role?->name === 'admin')
                    <a href="{{ route('users') }}"
                       @class(['nav-link rounded-pill px-3 fw-semibold', 'active bg-success text-white' => request()->routeIs('users*'), 'link-success' => ! request()->routeIs('users*')])
                       @if (request()->routeIs('users*')) aria-current="page" @endif>Users</a>
                @endif
                <form method="POST" action="{{ route('logout') }}" class="m-0">
                    @csrf
                    <button class="btn btn-outline-secondary rounded-pill px-3" type="submit">Log out</button>
                </form>
            @else
                <a class="btn btn-outline-success rounded-pill px-4" href="{{ route('login') }}">Log in</a>
            @endauth
        </nav>
    </div>
</header>
