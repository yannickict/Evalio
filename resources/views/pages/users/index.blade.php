@extends('layouts.app')

@section('title', __('Users - Evalio'))

@section('content')
<main class="container py-5">
    <div class="row justify-content-center g-5">
        <div class="col-12 col-lg-10 col-xl-9">
            <header class="mb-4">
                <p class="text-success small fw-semibold text-uppercase mb-2">{{ __('Account management') }}</p>
                <h1 class="h2 fw-bold mb-2">{{ __('Users') }}</h1>
                <p class="text-body-secondary mb-0">{{ __('Review registrations and browse user accounts.') }}</p>
            </header>

            <x-ui.status-alert class="rounded-3" />

            <x-ui.validation-errors :errors="$errors" class="rounded-3" :heading="__('Please check your changes.')" />

            <x-users.list id="pending-users" heading-id="approval-title" title="{{ __('Approve people') }}" class="mb-5">
                <x-slot:count>{{ $pendingUsers->count() }} {{ __('pending') }}</x-slot:count>
                @forelse ($pendingUsers as $user)
                <x-users.pending-row :user="$user" :roles="$roles" />
                @empty
                <div class="p-5 text-center">
                    <span class="badge rounded-pill text-bg-success mb-3">{{ __('All caught up') }}</span>
                    <h3 class="h5">{{ __('No users waiting for approval.') }}</h3>
                    <p class="text-body-secondary mb-0">{{ __('New registrations will appear here.') }}</p>
                </div>
                @endforelse
            </x-users.list>
            <x-users.list id="all-users" heading-id="users-title" title="{{ __('All users') }}">
                <x-slot:count>{{ $approvedUsers->count() }} {{ $approvedUsers->count() === 1 ? __('user') : __('users') }}</x-slot:count>
                @forelse ($approvedUsers as $user)
                <x-users.approved-row :user="$user" :roles="$roles" />
                @empty
                <div class="p-5 text-center">
                    <h3 class="h5">{{ __('No users yet.') }}</h3>
                    <p class="text-body-secondary mb-0">{{ __('Registered accounts will appear here.') }}</p>
                </div>
                @endforelse
            </x-users.list>
        </div>
    </div>
</main>
@endsection
