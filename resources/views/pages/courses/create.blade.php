@extends('layouts.app')

@section('title', 'New course - Evalio')

@section('content')
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9 col-xl-8">
            <header class="mb-4">
                <a href="{{ route('courses.index') }}" class="link-secondary text-decoration-none small d-inline-block mb-4">
                    <span aria-hidden="true">&larr;</span> Back to courses
                </a>
                <p class="text-success small fw-semibold text-uppercase mb-2">Course evaluations</p>
                <h1 class="h2 fw-bold mb-2">New course</h1>
                <p class="text-body-secondary mb-0">Set up a course to bring its sessions and feedback together.</p>
            </header>

            <x-ui.validation-errors :errors="$errors" />

            <form method="POST" action="{{ route('courses.store') }}" class="card border-0 rounded-4 shadow-sm">
                @csrf
                <div class="card-body p-4 p-md-5">
                    <section aria-labelledby="course-details-heading" class="mb-4 pb-4 border-bottom">
                        <h2 id="course-details-heading" class="h5 fw-semibold mb-1">Course details</h2>
                        <p class="small text-body-secondary mb-4">Choose a name that makes this course easy to find.</p>
                        <label for="course-name" class="form-label fw-medium">Course name</label>
                        <input id="course-name" name="name" type="text" class="form-control"
                            value="{{ old('name') }}" maxlength="255" required
                            placeholder="e.g. Introduction to web development">
                    </section>

                    <section aria-labelledby="course-questionnaire-heading">
                        <h2 id="course-questionnaire-heading" class="h5 fw-semibold mb-1">Feedback questionnaire</h2>
                        <p class="small text-body-secondary mb-4">Choose the questionnaire participants will use to evaluate this course.</p>
                        <label for="course-questionnaire" class="form-label fw-medium">Assigned questionnaire</label>
                        <select id="course-questionnaire" name="questionnaire_template_id" class="form-select"
                            aria-describedby="questionnaire-note" required @disabled($templates->isEmpty())>
                            <option value="" @selected(! old('questionnaire_template_id')) disabled>Select a questionnaire</option>
                            @foreach ($templates as $template)
                                <option value="{{ $template->id }}" @selected(old('questionnaire_template_id') == $template->id)>{{ $template->name }}</option>
                            @endforeach
                        </select>
                        @if ($templates->isEmpty())
                            <p id="questionnaire-note" class="form-text mb-0">
                                No questionnaires available. <a href="{{ route('questionnaires.create') }}" class="link-success">Create a questionnaire</a> before adding a course.
                            </p>
                        @else
                            <p id="questionnaire-note" class="form-text mb-0">This questionnaire will be assigned to the course's sessions.</p>
                        @endif
                    </section>
                </div>

                <div class="card-footer bg-transparent border-top px-4 px-md-5 py-4">
                    <div class="d-flex justify-content-end flex-wrap gap-2">
                        <a href="{{ route('courses.index') }}" class="btn btn-outline-secondary rounded-3">Cancel</a>
                        <button type="submit" class="btn btn-success rounded-3" @disabled($templates->isEmpty())>Create course</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</main>
@endsection
