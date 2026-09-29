@extends('layouts.app')

@section('title', 'Users - Feedback')

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
                <h2 class="mb-0" id="approval-title">
                    <button class="accordion-button rounded-3 bg-white shadow-sm gap-3" type="button"
                        data-bs-toggle="collapse" data-bs-target="#pending-users-content"
                        aria-expanded="true" aria-controls="pending-users-content">
                        <span class="h3 fw-bold mb-0">Approve people</span>
                        <span class="badge rounded-pill text-success-emphasis bg-success-subtle border border-success-subtle">{{ $non_approved_users->count() }} pending</span>
                    </button>
                </h2>
                <div id="pending-users-content" class="collapse show">
                    <div class="card border-0 rounded-4 shadow-sm overflow-hidden">
                        <div class="list-group list-group-flush">
                            @forelse ($non_approved_users as $user)
                            <article class="list-group-item p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                                <div class="text-break">
                                    <h3 class="h5 mb-1">{{ $user->name }}</h3>
                                    <p class="text-body-secondary mb-0">{{ $user->email }}</p>
                                    <p class="small text-body-secondary mt-2 mb-0">Registered {{ $user->created_at->format('M j, Y') }}</p>
                                </div>
                                <div class="d-flex flex-wrap gap-2">
                                    <button class="btn btn-success rounded-pill px-3"
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
                                        <div class="modal-dialog modal-dialog-centered">
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
                                        <button class="btn btn-outline-danger rounded-pill px-3" type="submit" aria-label="Delete {{ $user->name }}">Delete</button>
                                    </form>
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
            </section>
            <section id="all-users" class="accordion" aria-labelledby="users-title">
                <h2 class="mb-0" id="users-title">
                    <button class="accordion-button rounded-3 bg-white shadow-sm gap-3" type="button"
                        data-bs-toggle="collapse" data-bs-target="#all-users-content"
                        aria-expanded="true" aria-controls="all-users-content">
                        <span class="h3 fw-bold mb-0">All users</span>
                        <span class="badge rounded-pill text-success-emphasis bg-success-subtle border border-success-subtle">
                            {{ $approved_users->count() }} {{ $approved_users->count() === 1 ? 'user' : 'users' }}
                        </span>
                    </button>
                </h2>
                <div id="all-users-content" class="collapse show">
                    <div class="card border-0 rounded-4 shadow-sm overflow-hidden">
                        <div class="list-group list-group-flush">
                            @forelse ($approved_users as $user)
                            <article class="list-group-item p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                                <div class="text-break">
                                    <div class="d-flex align-items-center flex-wrap gap-2 mb-1">
                                        <h3 class="h5 mb-0">{{ $user->name }}</h3>
                                        <span class="badge rounded-pill text-bg-light border">{{ ucfirst($user->role?->name ?? 'No role assigned') }}</span>
                                    </div>
                                    <p class="text-body-secondary mb-0">{{ $user->email }}</p>
                                    <p class="small text-body-secondary mt-2 mb-0">Registered {{ $user->created_at->format('M j, Y') }}</p>
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
            </section>
        </div>
    </div>
</main>
@endsection
