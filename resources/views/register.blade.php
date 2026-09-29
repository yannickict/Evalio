@extends('layouts.auth')

@section('title', 'Create your account')
@section('intro', 'Your account will need approval before you can log in.')

@section('content')
    <form class="auth-form" method="POST" action="{{ route('register.store') }}">
        @csrf
        <div class="auth-name-row">
            <div class="auth-field">
                <label for="first_name">First name</label>
                <input id="first_name" name="first_name" autocomplete="given-name" value="{{ old('first_name') }}" maxlength="255" required>
            </div>
            <div class="auth-field">
                <label for="last_name">Last name</label>
                <input id="last_name" name="last_name" autocomplete="family-name" value="{{ old('last_name') }}" maxlength="255" required>
            </div>
        </div>
        <div class="auth-field">
            <label for="email">Email address</label>
            <input id="email" type="email" name="email" autocomplete="email" placeholder="you@example.com" value="{{ old('email') }}" maxlength="255" required>
        </div>
        <div class="auth-field">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" autocomplete="new-password" minlength="8" aria-describedby="password-hint" required>
            <p id="password-hint" class="auth-hint">Use at least 8 characters.</p>
        </div>
        <div class="auth-field">
            <label for="password_confirmation">Confirm password</label>
            <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required>
        </div>
        <button class="auth-button" type="submit">Create account</button>
    </form>
@endsection

@section('footer')
    Already have an account? <a href="{{ route('login') }}">Log in</a>
@endsection
