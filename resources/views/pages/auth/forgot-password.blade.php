@extends('layouts.auth')

@section('title', __('Forgot password'))
@section('intro', __('Enter your email address to request a password reset link.'))

@section('content')
    <form class="d-grid gap-3" method="POST" action="{{ route('password.email') }}">
        @csrf
        <div>
            <label class="form-label fw-semibold" for="email">{{ __('Email address') }}</label>
            <input class="form-control" id="email" type="email" name="email"
                   autocomplete="email" value="{{ old('email') }}" required>
        </div>
        <button class="btn btn-success rounded-pill py-2" type="submit">{{ __('Send reset link') }}</button>
    </form>
@endsection

@section('footer')
    <a class="link-success" href="{{ route('login') }}">{{ __('Back to login') }}</a>
@endsection
