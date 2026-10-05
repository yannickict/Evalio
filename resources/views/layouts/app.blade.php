<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Evalio')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <x-page-scrollbar />
</head>
<body class="bg-body-tertiary min-vh-100 d-flex flex-column">
    <x-navigation />

    @yield('content')
</body>
</html>
