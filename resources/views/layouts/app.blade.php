<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Evalio')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-body-tertiary min-vh-100">
    <a class="skip-link visually-hidden-focusable position-fixed top-0 start-0 bg-white p-3 d-print-none" href="#page-content">{{ __('Skip to content') }}</a>
    <x-navigation />
    <div id="page-content" tabindex="-1" @class(['app-content', 'workspace-content' => auth()->check() && ! request()->routeIs('feedback.*')])>
        @yield('content')
    </div>
</body>
</html>
