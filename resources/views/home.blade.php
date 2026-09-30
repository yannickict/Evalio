@extends('layouts.app')

@section('title', 'Feedback · Course evaluations')

@section('content')
<main class="container flex-grow-1 d-flex align-items-center justify-content-center py-5">
    <section class="col-12 col-md-8 col-lg-6 text-center" aria-labelledby="entry-title">
        <p class="text-success text-uppercase small fw-semibold mb-3">Your experience matters</p>
        <h1 class="display-5 fw-bold" id="entry-title">Share your feedback.</h1>
        <p class="text-body-secondary mb-4">Enter the six-digit code from your instructor.</p>
        <form class="card border-0 rounded-4 shadow-sm p-4 p-sm-5 col-12 col-sm-8 mx-auto" method="GET" action="{{ route('questionaire') }}">
            <label class="form-label fw-semibold" for="session-code">Session code</label>
            <input class="form-control form-control-lg bg-body-tertiary text-center fs-2 py-3" id="session-code" name="code" type="text" inputmode="numeric"
                pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="off" required
                placeholder="123456" spellcheck="false" aria-describedby="code-hint">
            <button class="btn btn-success btn-lg mt-3" type="submit">Open questionnaire</button>
        </form>
        <p class="text-body-secondary small mt-3" id="code-hint">No account needed.</p>
    </section>
</main>
@endsection
