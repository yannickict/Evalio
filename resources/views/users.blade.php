@extends('layouts.app')

@section('title', 'Users - Feedback')

@section('content')
<main class="container py-5">
    <div class="row justify-content-center">
        <section class="col-12 col-lg-9" aria-labelledby="approval-title">
            <p class="text-success small fw-semibold text-uppercase mb-2">Account management</p>
            <h1 class="h2 fw-bold mb-4">Users</h1>
            <div class="d-flex align-items-center flex-wrap gap-3 mb-2">
                <h2 class="h3 fw-bold mb-0" id="approval-title">Approve people</h2>
                <span class="badge rounded-pill text-success-emphasis bg-success-subtle border border-success-subtle">{{ $users->count() }} pending</span>
            </div>
            <p class="text-body-secondary mb-4">Review new registrations before granting access.</p>

            @if (session('status'))
            <div class="alert alert-success rounded-3" role="status">{{ session('status') }}</div>
            @endif

            <div class="card border-0 rounded-4 shadow-sm overflow-hidden">
                <div class="list-group list-group-flush">
                    @forelse ($users as $user)
                    <article class="list-group-item p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div class="text-break">
                            <h2 class="h5 mb-1">{{ $user->name }}</h2>
                            <p class="text-body-secondary mb-0">{{ $user->email }}</p>
                            <p class="small text-body-secondary mt-2 mb-0">Registered {{ $user->created_at->format('M j, Y') }}</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <button class="btn btn-success"
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
                        <h2 class="h5">No users waiting for approval.</h2>
                        <p class="text-body-secondary mb-0">New registrations will appear here.</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </section>
    </div>
</main>
@endsection
