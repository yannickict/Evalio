@extends('layouts.app')

@section('title', __('Edit course - Evalio'))

@section('content')
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9 col-xl-8">
            <header class="mb-4">
                <a href="{{ route('courses.index') }}" class="link-secondary text-decoration-none small d-inline-block mb-4">
                    <span aria-hidden="true">&larr;</span> {{ __('Back to courses') }}
                </a>
                <p class="text-success small fw-semibold text-uppercase mb-2">{{ __('Course evaluations') }}</p>
                <h1 class="h2 fw-bold mb-2">{{ __('Edit course') }}</h1>
                <p class="text-body-secondary mb-0">{{ __('Review the course name and assigned feedback questionnaire.') }}</p>
            </header>

            <x-ui.validation-errors :errors="$errors" />

            <form method="POST" action="{{ route('courses.update', $course) }}"
                class="card border-0 rounded-4 shadow-sm">
                @csrf
                @method('PATCH')
                <div class="card-body p-4 p-md-5">
                    <x-courses.form-fields :templates="$templates" :course="$course" />
                </div>
                <div class="card-footer bg-transparent border-top px-4 px-md-5 py-4">
                    <div class="d-flex justify-content-end gap-2">
                        <a href="{{ route('courses.index') }}" class="btn btn-outline-secondary rounded-3">{{ __('Cancel') }}</a>
                        <button type="submit" class="btn btn-success rounded-3" @disabled($templates->isEmpty())>{{ __('Save changes') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</main>
@endsection
