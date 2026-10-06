@extends('layouts.app')

@section('title', 'Evalio · Course evaluations')

@section('content')
<main class="container flex-grow-1 d-flex flex-column align-items-center justify-content-center py-5">
    <section class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5 text-center" aria-labelledby="entry-title">
        @if (session('status'))
        <div class="d-flex align-items-center gap-3 bg-success-subtle text-success-emphasis border border-success-subtle rounded-4 p-4 mb-4 text-start" role="status" aria-live="polite">
            <svg class="flex-shrink-0" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <circle cx="12" cy="12" r="9" />
                <path d="m8 12 3 3 5-6" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <p class="mb-0">{{ session('status') }}</p>
        </div>
        @endif

        <div class="card border-0 rounded-4 shadow-sm p-4 p-sm-5">
            <header class="mb-4">
                <p class="text-success text-uppercase small fw-semibold mb-3">Your experience matters</p>
                <h1 class="h2 fw-bold mb-3" id="entry-title">Share your feedback.</h1>
                <p class="text-body-secondary mb-0">Enter the six-digit code from your instructor.</p>
            </header>
            <form method="GET" action="{{ route('feedback.show') }}">
            <label class="form-label fw-semibold small" for="session-code">Feedback code</label>
            <input class="form-control form-control-lg bg-body-tertiary text-center fs-2 py-3 rounded-3" id="session-code" name="code" type="text" inputmode="numeric"
                pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="off" required
                placeholder="123456" spellcheck="false" aria-describedby="code-hint">
            <button class="btn btn-success btn-lg w-100 rounded-3 mt-3" type="submit">Open questionnaire</button>
            </form>
            <p class="text-body-secondary small mt-3 mb-0" id="code-hint">No account needed.</p>
            <x-ui.setup-guide :home="true" />
        </div>
    </section>
</main>
@endsection
