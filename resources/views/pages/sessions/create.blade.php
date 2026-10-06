@extends('layouts.app')

@section('title', 'New session - Evalio')

@section('content')
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9 col-xl-8">
            <header class="mb-4">
                <a href="{{ route('sessions.index') }}" class="link-secondary text-decoration-none small d-inline-block mb-4">
                    <span aria-hidden="true">&larr;</span> Back to sessions
                </a>
                <p class="text-success small fw-semibold text-uppercase mb-2">Course evaluations</p>
                <h1 class="h2 fw-bold mb-2">New session</h1>
                <p class="text-body-secondary mb-0">Choose the course, instructor, and dates for your session.</p>
            </header>
            <x-ui.validation-errors :errors="$errors" />
            <form method="POST" action="{{ route('sessions.store') }}" class="card border-0 rounded-4 shadow-sm">
                @csrf
                <div class="card-body p-4 p-md-5">
                    <section aria-labelledby="session-details-heading" class="mb-4 pb-4 border-bottom">
                        <h2 id="session-details-heading" class="h5 fw-semibold mb-1">Session details</h2>
                        <p class="small text-body-secondary mb-4">Select the course and its instructor. A session number is assigned automatically.</p>

                        <div class="row g-4">
                            <div class="col-md-6">
                                <label for="session-course" class="form-label fw-medium">Course</label>
                                <select id="session-course" name="course_id" class="form-select" required>
                                    <option value="" @selected(! old('course_id', request()->query('course_id'))) disabled>Select a course</option>
                                    @forelse ($courses as $course)
                                    <option value="{{ $course->id }}" @selected(old('course_id', request()->query('course_id')) == $course->id)>{{ $course->name }}</option>
                                    @empty
                                    <option disabled>No courses available</option>
                                    @endforelse
                                </select>
                            </div>
                            <div class="col-md-6">
                                <x-sessions.instructor-field :instructors="$instructors" />
                            </div>
                        </div>
                    </section>

                    <x-sessions.date-fields />
                </div>
                <div class="card-footer bg-transparent border-top px-4 px-md-5 py-4">
                    <div class="d-flex justify-content-end">
                        <div class="d-flex gap-2">
                            <a href="{{ route('sessions.index') }}" class="btn btn-outline-secondary rounded-3">Cancel</a>
                            <button type="submit" class="btn btn-success rounded-3">
                                Create session
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</main>
@endsection
