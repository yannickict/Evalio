@extends('layouts.auth')

@section('title', 'Welcome back')
@section('intro', 'Log in to your account to continue.')

@section('content')
    <form class="auth-form" method="POST" action="{{ route('login.store') }}">
        @csrf
        <div class="auth-field">
            <label for="email">Email address</label>
            <input id="email" type="email" name="email" autocomplete="email"
                   placeholder="you@example.com" value="{{ old('email') }}" required>
        </div>
        <div class="auth-field">
            <label for="password">Password</label>
            <input id="password" type="password" name="password" autocomplete="current-password" required>
        </div>
        <label class="auth-remember">
            <input type="checkbox" name="remember" value="1" @checked(old('remember'))>
            Remember me
        </label>
        <button class="auth-button" type="submit">Log in</button>
    </form>
@endsection

@section('footer')
    New here? <a href="{{ route('register') }}">Create an account</a>
@endsection
