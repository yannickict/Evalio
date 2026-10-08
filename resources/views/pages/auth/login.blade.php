@extends('layouts.auth')

@section('title', __('Welcome back'))
@section('intro', __('Log in to your account to continue.'))

@section('content')
    <form class="d-grid gap-3" method="POST" action="{{ route('login.store') }}">
        @csrf
        <div class="mb-1">
            <label class="form-label fw-semibold" for="email">{{ __('Email address') }}</label>
            <input class="form-control" id="email" type="email" name="email" autocomplete="email"
                   placeholder="you@example.com" value="{{ old('email') }}" required>
        </div>
        <div class="mb-1">
            <label class="form-label fw-semibold" for="password">{{ __('Password') }}</label>
            <input class="form-control" id="password" type="password" name="password" autocomplete="current-password" required>
        </div>
        <label class="d-flex align-items-center gap-2">
            <input class="form-check-input mt-0" type="checkbox" name="remember" value="1" @checked(old('remember'))>
            {{ __('Remember me') }}
        </label>
        <a class="link-success small" href="{{ route('password.request') }}">{{ __('Forgot password?') }}</a>
        <button class="btn btn-success rounded-pill py-2" type="submit">{{ __('Log in') }}</button>
    </form>
@endsection

@section('footer')
    {{ __('New here?') }} <a class="link-success fw-semibold link-offset-2" href="{{ route('register') }}">{{ __('Create an account') }}</a>
@endsection
