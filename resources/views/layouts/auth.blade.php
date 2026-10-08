<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · Evalio</title>
    @vite('resources/css/app.css')
    <x-page-scrollbar />
</head>
<body class="bg-body-tertiary">
    <main class="container min-vh-100 d-flex align-items-center py-5">
        <div class="row justify-content-center w-100 mx-0">
            <div class="col-12 col-md-8 col-lg-6 col-xl-5">
                <div class="d-flex justify-content-end mb-3"><x-language-switch /></div>
                <a class="d-block mb-4 fw-bold text-success text-decoration-none" href="/">Evalio <span class="small fw-normal text-body-secondary">{{ __('/ Course evaluations') }}</span></a>
                <section class="card border-0 rounded-4 shadow-sm p-4 p-sm-5" aria-labelledby="auth-title">
                    <h1 class="h3" id="auth-title">@yield('title')</h1>
                    <p class="text-body-secondary small mb-4">@yield('intro')</p>
                    <x-ui.status-alert />
                    <x-ui.validation-errors :errors="$errors" class="small" :heading="__('Please check the following:')" heading-class="fw-semibold" />
                    @yield('content')
                </section>
                <p class="text-center text-body-secondary small mt-4">@yield('footer')</p>
            </div>
        </div>
    </main>
</body>
</html>
