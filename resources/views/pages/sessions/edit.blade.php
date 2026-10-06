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
                <p class="text-body-secondary mb-0">Review the instructor and dates for {{ $courseSession->course_session_number }}.</p>
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
                        <label for="session-instructor" class="form-label fw-medium">Instructor</label>
                        <select id="session-instructor" name="instructor_id" class="form-select" required>
                            @if (! $instructors->contains('id', $courseSession->instructor_id))
                            <option value="{{ $courseSession->instructor_id }}" selected disabled>{{ $courseSession->instructor->name }} (currently unavailable)</option>
                            @endif
                            @foreach ($instructors as $instructor)
                            <option value="{{ $instructor->id }}" @selected(old('instructor_id', $courseSession->instructor_id) == $instructor->id)>{{ $instructor->name }}</option>
                            @endforeach
                        </select>
                    </section>

                    <section aria-labelledby="course-dates-heading">
                        <h2 id="course-dates-heading" class="h5 fw-semibold mb-1">Course dates</h2>
                        <p class="small text-body-secondary mb-4">Evaluations open on the start date and close 14 days after the end date.</p>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label for="session-start-date" class="form-label fw-medium">Start date</label>
                                <input id="session-start-date" name="start_date" value="{{ old('start_date', $courseSession->start_date->toDateString()) }}" type="date" class="form-control" required>
                            </div>
                            <div class="col-md-6">
                                <label for="session-end-date" class="form-label fw-medium">End date</label>
                                <input id="session-end-date" name="end_date" value="{{ old('end_date', $courseSession->end_date->toDateString()) }}" type="date" class="form-control" required>
                            </div>
                        </div>
                    </section>
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
