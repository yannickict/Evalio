@extends('layouts.app')

@section('title', __('Questionnaire - Evalio'))

@section('content')
<main class="container py-4 py-md-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9 col-xl-8">
            <header class="mb-4 mb-md-5">
                <p class="text-success small fw-semibold text-uppercase mb-2">{{ __('Course feedback') }}</p>
                <h1 class="display-6 fw-bold mb-3">{{ __('How was your experience?') }}</h1>
                <p class="text-body-secondary mb-3">{{ __('Share what worked well and what we could improve.') }}</p>
                <p class="text-body-secondary small">{{ __('All questions are optional. You can skip any question.') }}</p>
                <span class="badge rounded-pill text-success-emphasis bg-success-subtle border border-success-subtle px-3 py-2">
                    {{ $questions->count() }} {{ $questions->count() === 1 ? __('question') : __('questions') }}
                </span>
            </header>

            <x-ui.validation-errors :errors="$errors" class="rounded-3" :heading="__('Please check your answers.')" />
            <form method="POST" id="questionnaire-form"
                action="{{ route('feedback.store', ['code' => request()->query('code')]) }}">
                @csrf
                @forelse ($questions as $question)
                <x-questionnaires.response-question :question="$question" :number="$loop->iteration" />
                @empty
                <div class="card border-0 rounded-4 shadow-sm p-5 text-center">
                    <h2 class="h5">{{ __('No questions available') }}</h2>
                    <p class="text-body-secondary mb-0">{{ __('Please check with your instructor.') }}</p>
                </div>
                @endforelse

                @if ($questions->isNotEmpty())
                <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mt-4">
                    <p class="text-body-secondary small mb-0">{{ __('Thank you for taking the time to share your feedback.') }}</p>
                    <button class="btn btn-success rounded-pill px-4 py-3 flex-shrink-0" type="submit">{{ __('Submit feedback') }}</button>
                </div>
                @endif
            </form>
        </div>
    </div>
</main>
@endsection
