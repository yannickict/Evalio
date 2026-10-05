<!DOCTYPE html>
<html lang="en">
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
                <a class="d-block mb-4 fw-bold text-success text-decoration-none" href="/">Evalio <span class="small fw-normal text-body-secondary">/ Course evaluations</span></a>
                <section class="card border-0 rounded-4 shadow-sm p-4 p-sm-5" aria-labelledby="auth-title">
                    <h1 class="h3" id="auth-title">@yield('title')</h1>
                    <p class="text-body-secondary small mb-4">@yield('intro')</p>
                    @if ($errors->any())
                        <div class="alert alert-danger small" role="alert">
                            <p class="fw-semibold">Please check the following:</p>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    @yield('content')
                </section>
                <p class="text-center text-body-secondary small mt-4">@yield('footer')</p>
            </div>
        </div>
    </main>
</body>
</html>
