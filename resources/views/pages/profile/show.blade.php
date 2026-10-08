@extends('layouts.app')

@section('title', __('Account profile - Evalio'))

@section('content')
@php($editingProfile = $errors->hasAny(['name', 'email']))
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">
            <header class="mb-4">
                <p class="small text-success fw-semibold text-uppercase mb-2">{{ __('Your account') }}</p>
                <h1 class="h2 fw-bold mb-2">{{ __('Account profile') }}</h1>
                <p class="text-body-secondary mb-0">{{ __('Your personal details and assigned role.') }}</p>
            </header>
            <x-ui.status-alert />
            <x-ui.validation-errors :errors="$errors" />
            <section class="card border-0 rounded-4 shadow-sm p-4">
                <div class="mb-3">
                    <span class="badge rounded-pill bg-success-subtle text-success-emphasis">{{ $user->role ? __(ucfirst($user->role->name)) : __('No role assigned') }}</span>
                </div>
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
                    <h2 class="h5 mb-0">{{ __('Personal details') }}</h2>
                    <button class="btn btn-outline-success btn-sm rounded-3" id="profile-edit" type="button" aria-controls="profile-form" aria-expanded="{{ $editingProfile ? 'true' : 'false' }}" @if($editingProfile) hidden @endif>{{ __('Edit profile') }}</button>
                </div>
                <dl class="mb-0" id="profile-details" @if($editingProfile) hidden @endif>
                    <dt class="small text-body-secondary fw-normal">{{ __('Name') }}</dt>
                    <dd class="fw-medium text-break mb-3">{{ $user->name }}</dd>
                    <dt class="small text-body-secondary fw-normal">{{ __('Email') }}</dt>
                    <dd class="fw-medium text-break mb-3">{{ $user->email }}</dd>
                </dl>
                <form id="profile-form" method="POST" action="{{ route('profile.update') }}" @unless($editingProfile) hidden @endunless>
                    @csrf
                    @method('PATCH')
                    <div class="mb-3">
                        <label class="form-label" for="profile-name">{{ __('Name') }}</label>
                        <input class="form-control" id="profile-name" name="name" type="text" autocomplete="name" value="{{ old('name', $user->name) }}" data-original-value="{{ $user->name }}" maxlength="255" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="profile-email">{{ __('Email') }}</label>
                        <input class="form-control" id="profile-email" name="email" type="email" autocomplete="email" value="{{ old('email', $user->email) }}" data-original-value="{{ $user->email }}" maxlength="255" required>
                    </div>
                    <div class="d-flex gap-2 mb-3">
                        <button class="btn btn-success rounded-3" type="submit">{{ __('Save') }}</button>
                        <button class="btn btn-outline-secondary rounded-3" id="profile-cancel" type="button">{{ __('Cancel') }}</button>
                    </div>
                </form>
            </section>
            <section class="card border-0 rounded-4 shadow-sm p-4 mt-4">
                <h2 class="h5 mb-2">{{ __('Change password') }}</h2>
                <p class="small text-body-secondary mb-4">{{ __('Enter your current password and choose a new password of at least 8 characters.') }}</p>
                <form method="POST" action="{{ route('profile.password.update') }}">
                    @csrf
                    @method('PATCH')
                    <div class="mb-3">
                        <label class="form-label" for="current-password">{{ __('Current password') }}</label>
                        <input class="form-control" id="current-password" name="current_password" type="password" autocomplete="current-password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="new-password">{{ __('New password') }}</label>
                        <input class="form-control" id="new-password" name="password" type="password" autocomplete="new-password" minlength="8" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label" for="confirm-password">{{ __('Confirm new password') }}</label>
                        <input class="form-control" id="confirm-password" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required>
                    </div>
                    <button class="btn btn-success rounded-3" type="submit">{{ __('Change password') }}</button>
                </form>
            </section>
        </div>
    </div>
</main>
@endsection
