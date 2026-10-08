@extends('layouts.auth')

@section('title', 'Reset password')
@section('intro', 'Choose a new password for your account.')

@section('content')
    <form class="d-grid gap-3" method="POST" action="{{ route('password.update') }}">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div>
            <label class="form-label fw-semibold" for="email">Email address</label>
            <input class="form-control" id="email" type="email" name="email"
                   autocomplete="email" value="{{ old('email', $email) }}" required>
        </div>
        <div>
            <label class="form-label fw-semibold" for="password">New password</label>
            <input class="form-control" id="password" type="password" name="password"
                   autocomplete="new-password" minlength="8" required>
        </div>
        <div>
            <label class="form-label fw-semibold" for="password_confirmation">Confirm password</label>
            <input class="form-control" id="password_confirmation" type="password"
                   name="password_confirmation" autocomplete="new-password" minlength="8" required>
        </div>
        <button class="btn btn-success rounded-pill py-2" type="submit">Reset password</button>
    </form>
@endsection

@section('footer')
    <a class="link-success" href="{{ route('login') }}">Back to login</a>
@endsection
