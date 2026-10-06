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

            <x-ui.status-alert class="rounded-3" />

            <x-ui.validation-errors :errors="$errors" class="rounded-3" heading="Please check your changes." />

            <x-users.list id="pending-users" heading-id="approval-title" title="Approve people" class="mb-5">
                <x-slot:count>{{ $non_approved_users->count() }} pending</x-slot:count>
                @forelse ($non_approved_users as $user)
                <x-users.pending-row :user="$user" :roles="$roles" />
                @empty
                <div class="p-5 text-center">
                    <span class="badge rounded-pill text-bg-success mb-3">All caught up</span>
                    <h3 class="h5">No users waiting for approval.</h3>
                    <p class="text-body-secondary mb-0">New registrations will appear here.</p>
                </div>
                @endforelse
            </x-users.list>
            <x-users.list id="all-users" heading-id="users-title" title="All users">
                <x-slot:count>{{ $approved_users->count() }} {{ $approved_users->count() === 1 ? 'user' : 'users' }}</x-slot:count>
                @forelse ($approved_users as $user)
                <x-users.approved-row :user="$user" :roles="$roles" />
                @empty
                <div class="p-5 text-center">
                    <h3 class="h5">No users yet.</h3>
                    <p class="text-body-secondary mb-0">Registered accounts will appear here.</p>
                </div>
                @endforelse
            </x-users.list>
        </div>
    </div>
</main>
@endsection
