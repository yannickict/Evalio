<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') · Feedback</title>
    @vite('resources/css/app.css')
</head>
<body class="auth-page">
    <main class="auth-container">
        <a class="auth-brand" href="/">Feedback <span>/ Course evaluations</span></a>
        <section class="auth-card" aria-labelledby="auth-title">
            <h1 id="auth-title">@yield('title')</h1>
            <p class="auth-intro">@yield('intro')</p>
            @if ($errors->any())
                <div class="auth-errors" role="alert">
                    <p>Please check the following:</p>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
        </section>
        <p class="auth-footer">@yield('footer')</p>
    </main>
</body>
</html>
