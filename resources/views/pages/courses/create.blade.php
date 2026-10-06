@extends('layouts.app')

@section('title', 'New course - Evalio')

@section('content')
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9 col-xl-8">
            <header class="mb-4">
                <a href="{{ route('courses') }}" class="link-secondary text-decoration-none small d-inline-block mb-4">
                    <span aria-hidden="true">&larr;</span> Back to courses
                </a>
                <p class="text-success small fw-semibold text-uppercase mb-2">Course evaluations</p>
                <h1 class="h2 fw-bold mb-2">New course</h1>
                <p class="text-body-secondary mb-0">Set up a course to bring its sessions and feedback together.</p>
            </header>

            <section class="card border-0 rounded-4 shadow-sm mb-4" aria-labelledby="course-details-heading">
                <div class="card-body p-4">
                    <h2 id="course-details-heading" class="h5 fw-semibold mb-1">Course details</h2>
                    <p class="small text-body-secondary mb-4">Choose a name that makes this course easy to find.</p>
                    <div class="mb-4">
                        <label for="course-name" class="form-label fw-medium">Course name</label>
                        <input id="course-name" name="name" type="text" class="form-control" placeholder="e.g. Introduction to web development">
                    </div>
                    <label for="course-questionnaire" class="form-label fw-medium">Assigned questionnaire</label>
                    <select id="course-questionnaire" name="questionnaire_template_id" class="form-select" disabled aria-describedby="questionnaire-note">
                        <option selected>No questionnaire assigned</option>
                    </select>
                    <p id="questionnaire-note" class="form-text mb-0">Questionnaire selection will be available soon.</p>
                </div>
            </section>

            <p id="course-save-note" class="small text-body-secondary">Saving courses will be available soon.</p>
            <div class="d-flex justify-content-end flex-wrap gap-2">
                <a href="{{ route('courses') }}" class="btn btn-outline-secondary rounded-3">Cancel</a>
                <button type="button" class="btn btn-success rounded-3" disabled aria-describedby="course-save-note">Create course</button>
            </div>
        </div>
    </div>
</main>
@endsection
