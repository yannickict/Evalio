@extends('layouts.app')

@section('title', 'Approve people - Feedback')

@section('content')
    <main class="container py-5">
        <div class="row justify-content-center">
            <section class="col-12 col-lg-9" aria-labelledby="approval-title">
                <p class="text-success small fw-semibold text-uppercase mb-2">Account management</p>
                <div class="d-flex align-items-center flex-wrap gap-3 mb-2">
                    <h1 class="h2 fw-bold mb-0" id="approval-title">Approve people</h1>
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
                                    <form method="POST" action="{{ route('approve.update', $user) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn btn-success rounded-pill px-4" type="submit" aria-label="Approve {{ $user->name }}">Approve</button>
                                    </form>
                                    <form method="POST" action="{{ route('approve.destroy', $user) }}"
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
