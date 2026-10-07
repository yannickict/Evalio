@extends('layouts.app')

@section('title', 'Edit session - Evalio')

@section('content')
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9 col-xl-8">
            <header class="mb-4">
                <a href="{{ route('sessions.index') }}" class="link-secondary text-decoration-none small d-inline-block mb-4">
                    <span aria-hidden="true">&larr;</span> Back to sessions
                </a>
                <p class="text-success small fw-semibold text-uppercase mb-2">Course evaluations</p>
                <h1 class="h2 fw-bold mb-2">Edit session</h1>
                <p class="text-body-secondary mb-0">Review the instructor and dates for {{ $courseSession->session_identifier }}.</p>
            </header>

            <x-ui.validation-errors :errors="$errors" />

            <form method="POST" action="{{ route('sessions.update', $courseSession) }}" class="card border-0 rounded-4 shadow-sm">
                @csrf
                @method('PATCH')
                <div class="card-body p-4 p-md-5">
                    <section aria-labelledby="session-details-heading" class="mb-4 pb-4 border-bottom">
                        <h2 id="session-details-heading" class="h5 fw-semibold mb-3">Session details</h2>
                        <dl class="row g-3 mb-4">
                            <div class="col-md-6">
                                <dt class="small text-body-secondary fw-normal">Course</dt>
                                <dd class="fw-medium text-break mb-0">{{ $courseSession->course->name }}</dd>
                            </div>
                            <div class="col-md-6">
                                <dt class="small text-body-secondary fw-normal">Assigned questionnaire</dt>
                                <dd class="fw-medium text-break mb-0">{{ $courseSession->questionnaireTemplate?->name ?? 'No questionnaire assigned' }}</dd>
                            </div>
                        </dl>
                        <x-sessions.instructor-field :instructors="$instructors" :course-session="$courseSession" />
                    </section>

                    <x-sessions.date-fields :course-session="$courseSession" />
                </div>
                <div class="card-footer bg-transparent border-top px-4 px-md-5 py-4">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('sessions.index') }}" class="btn btn-outline-secondary rounded-3">Cancel</a>
                        <button type="submit" class="btn btn-success rounded-3">Save changes</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</main>
@endsection
