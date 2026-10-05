@extends('layouts.app')

@section('title', 'Users - Evalio')

@section('content')
<main class="container py-5">
    <div class="row justify-content-center g-5">
        <div class="col-12 col-lg-10 col-xl-9">
            <header class="mb-4">
                <p class="text-success small fw-semibold text-uppercase mb-2">Account management</p>
                <h1 class="h2 fw-bold mb-2">Users</h1>
                <p class="text-body-secondary mb-0">Review registrations and browse user accounts.</p>
            </header>

            @if (session('status'))
            <div class="alert alert-success rounded-3" role="status">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
            <div class="alert alert-danger rounded-3" role="alert">
                <p class="fw-semibold mb-2">Please check your changes.</p>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <section id="pending-users" class="accordion mb-5" aria-labelledby="approval-title">
                <div class="accordion-item border-0 shadow-sm">
                <h2 class="accordion-header" id="approval-title">
                    <button class="accordion-button bg-white gap-3" type="button"
                        data-bs-toggle="collapse" data-bs-target="#pending-users-content"
                        aria-expanded="true" aria-controls="pending-users-content">
                        <span class="h3 fw-bold mb-0">Approve people</span>
                        <span class="badge rounded-pill text-success-emphasis bg-success-subtle border border-success-subtle">{{ $non_approved_users->count() }} pending</span>
                    </button>
                </h2>
                <div id="pending-users-content" class="collapse show">
                    <div class="card border-0 rounded-0 rounded-bottom overflow-hidden">
                        <div class="list-group list-group-flush">
                            @forelse ($non_approved_users as $user)
                            <article class="list-group-item px-3 px-md-4 py-3">
                                <div class="row align-items-center g-3">
                                    <div class="col-12 col-md-7">
                                        <div class="d-flex align-items-start gap-3">
                                            <span class="user-avatar d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success-emphasis fw-bold lh-1 flex-shrink-0" aria-hidden="true">
                                                {{ mb_strtoupper(mb_substr($user->first_name, 0, 1).mb_substr($user->last_name, 0, 1)) }}
                                            </span>
                                            <div class="text-break">
                                                <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                                    <h3 class="h6 fw-semibold mb-0">{{ $user->name }}</h3>
                                                    <span class="badge rounded-pill text-bg-light border fw-normal">Pending</span>
                                                </div>
                                                <p class="small text-body-secondary mb-1">{{ $user->email }}</p>
                                                <p class="small text-body-secondary mb-0">Joined {{ $user->created_at->format('M j, Y') }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-5">
                                <div class="d-flex align-items-center gap-3">
                                    <button class="btn btn-success btn-sm rounded-3 px-3 flex-grow-1"
                                        type="button"
                                        data-bs-toggle="modal"
                                        data-bs-target="#approve-user-{{ $user->id }}">
                                        Approve
                                    </button>

                                    <div class="modal fade"
                                        id="approve-user-{{ $user->id }}"
                                        tabindex="-1"
                                        aria-labelledby="approve-title-{{ $user->id }}"
                                        aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                                            <form class="modal-content"
                                                method="POST"
                                                action="{{ route('users.update', $user) }}">
                                                @csrf
                                                @method('PATCH')

                                                <div class="modal-header">
                                                    <h2 class="modal-title fs-5"
                                                        id="approve-title-{{ $user->id }}">
                                                        Approve {{ $user->name }}
                                                    </h2>
                                                    <button class="btn-close" type="button"
                                                        data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>

                                                <div class="modal-body">
                                                    <label class="form-label" for="role-{{ $user->id }}">
                                                        Assign role
                                                    </label>
                                                    <select class="form-select"
                                                        id="role-{{ $user->id }}"
                                                        name="role_id" required>
                                                        @foreach ($roles as $role)
                                                        <option value="{{ $role->id }}"
                                                            @selected($role->id === $user->role_id)>
                                                            {{ ucfirst($role->name) }}
                                                        </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="modal-footer">
                                                    <button class="btn btn-outline-secondary" type="button"
                                                        data-bs-dismiss="modal">
                                                        Cancel
                                                    </button>
                                                    <button class="btn btn-success" type="submit">
                                                        Approve and assign role
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                    <form method="POST" action="{{ route('users.destroy', $user) }}"
                                        onsubmit="return confirm('Permanently delete this pending registration?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-danger btn-sm rounded-3 px-3" type="submit" aria-label="Delete {{ $user->name }}">Delete</button>
                                    </form>
                                </div>
                                        <p class="small text-body-secondary mt-2 mb-0">Choose a role when approving.</p>
                                    </div>
                                </div>
                            </article>
                            @empty
                            <div class="p-5 text-center">
                                <span class="badge rounded-pill text-bg-success mb-3">All caught up</span>
                                <h3 class="h5">No users waiting for approval.</h3>
                                <p class="text-body-secondary mb-0">New registrations will appear here.</p>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
                </div>
            </section>
            <section id="all-users" class="accordion" aria-labelledby="users-title">
                <div class="accordion-item border-0 shadow-sm">
                <h2 class="accordion-header" id="users-title">
                    <button class="accordion-button bg-white gap-3" type="button"
                        data-bs-toggle="collapse" data-bs-target="#all-users-content"
                        aria-expanded="true" aria-controls="all-users-content">
                        <span class="h3 fw-bold mb-0">All users</span>
                        <span class="badge rounded-pill text-success-emphasis bg-success-subtle border border-success-subtle">
                            {{ $approved_users->count() }} {{ $approved_users->count() === 1 ? 'user' : 'users' }}
                        </span>
                    </button>
                </h2>
                <div id="all-users-content" class="collapse show">
                    <div class="card border-0 rounded-0 rounded-bottom overflow-hidden">
                        <div class="list-group list-group-flush">
                            @forelse ($approved_users as $user)
                            <article class="list-group-item px-3 px-md-4 py-3">
                                <div class="row align-items-center g-3">
                                    <div class="col-12 col-md-7">
                                        <div class="d-flex align-items-start gap-3">
                                            <span class="user-avatar d-inline-flex align-items-center justify-content-center rounded-circle bg-success-subtle text-success-emphasis fw-bold lh-1 flex-shrink-0" aria-hidden="true">
                                                {{ mb_strtoupper(mb_substr($user->first_name, 0, 1).mb_substr($user->last_name, 0, 1)) }}
                                            </span>
                                            <div class="text-break">
                                                <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                                    <h3 class="h6 fw-semibold mb-0">{{ $user->name }}</h3>
                                                    @if ($user->id === auth()->id())
                                                        <span class="badge rounded-pill text-bg-light border fw-normal">You</span>
                                                    @endif
                                                </div>
                                                <p class="small text-body-secondary mb-1">{{ $user->email }}</p>
                                                <p class="small text-body-secondary mb-0">Joined {{ $user->created_at->format('M j, Y') }}</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 col-md-5">
                                        <div class="d-flex align-items-end gap-3">
                                    <form class="flex-grow-1" method="POST" action="{{ route('users.update', $user) }}">
                                        @csrf
                                        @method('PATCH')
                                        <label class="form-label small text-body-secondary mb-1" for="role-{{ $user->id }}">
                                            Role <span class="visually-hidden">for {{ $user->name }}</span>
                                        </label>
                                        <select class="form-select form-select-sm bg-body-tertiary rounded-3"
                                            id="role-{{ $user->id }}"
                                            name="role_id" required onchange="this.form.requestSubmit()">
                                            @foreach ($roles as $role)
                                            <option value="{{ $role->id }}"
                                                @selected($role->id === $user->role_id)>
                                                {{ ucfirst($role->name) }}
                                            </option>
                                            @endforeach
                                        </select>
                                        <noscript><button class="btn btn-success btn-sm mt-2" type="submit">Save role</button></noscript>
                                    </form>
                                    <form method="POST" action="{{ route('users.destroy', $user) }}"
                                        onsubmit="return confirm('Permanently delete this user?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-outline-danger btn-sm rounded-3 px-3" type="submit" aria-label="Delete {{ $user->name }}" @disabled($user->id === auth()->id())>Delete</button>
                                    </form>
                                        </div>
                                        <p class="small text-body-secondary mt-2 mb-0">Role changes save automatically.</p>
                                    </div>
                                </div>
                            </article>
                            @empty
                            <div class="p-5 text-center">
                                <h3 class="h5">No users yet.</h3>
                                <p class="text-body-secondary mb-0">Registered accounts will appear here.</p>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
                </div>
            </section>
        </div>
    </div>
</main>
@endsection
