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
                    <x-courses.form-fields :templates="$templates" />
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
