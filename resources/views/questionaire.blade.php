@extends('layouts.app')

@section('title', 'Questionnaire - Feedback')

@section('content')
<main class="container py-4 py-md-5">
    <div class="row justify-content-center">
        <div class="col-12 col-lg-9 col-xl-8">
            <header class="mb-4 mb-md-5">
                <p class="text-success small fw-semibold text-uppercase mb-2">Course feedback</p>
                <h1 class="display-6 fw-bold mb-3">How was your experience?</h1>
                <p class="text-body-secondary mb-3">Share what worked well and what we could improve.</p>
                <span class="badge rounded-pill text-success-emphasis bg-success-subtle border border-success-subtle px-3 py-2">
                    {{ $questions->count() }} {{ $questions->count() === 1 ? 'question' : 'questions' }}
                </span>
            </header>

            @if ($errors->any())
            <div class="alert alert-danger rounded-3" role="alert">
                <p class="fw-semibold mb-2">Please check your answers.</p>
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            <form method="POST" id="questionnaire-form"
                action="{{ route('questionaire.submit', ['code' => request()->query('code')]) }}">
                @csrf
                @forelse ($questions as $question)
                <fieldset class="card border-0 rounded-4 shadow-sm p-3 px-md-4 mb-3" aria-labelledby="question-title-{{ $question->id }}">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                        <span class="text-success small fw-semibold">Question {{ $loop->iteration }}</span>
                        <span class="text-body-secondary small">{{ $question->type === 'single_choice' ? 'Choose one answer' : 'In your own words' }}</span>
                    </div>
                    <h2 class="h5 fw-semibold mb-3" id="question-title-{{ $question->id }}">{{ $question->question_text }}</h2>

                    @if ($question->type === 'single_choice')
                    <div class="d-grid gap-2">
                        @foreach ($question->options as $option)
                        <label class="d-flex align-items-center gap-2 border rounded-3 px-3 py-2 bg-body-tertiary" for="question-{{ $question->id }}-option-{{ $option->id }}">
                            <input class="form-check-input flex-shrink-0 mt-0" type="radio"
                                id="question-{{ $question->id }}-option-{{ $option->id }}"
                                name="answers[{{ $question->id }}]" value="{{ $option->id }}" required
                                @checked((string) old('answers.'.$question->id) === (string) $option->id)>
                            <span>{{ $option->option_text }}</span>
                        </label>
                        @endforeach
                    </div>
                    @elseif ($question->type === 'free_text')
                    <textarea class="form-control bg-body-tertiary rounded-3 px-3 py-2" rows="3"
                        id="answer-{{ $question->id }}" name="answers[{{ $question->id }}]"
                        aria-labelledby="question-title-{{ $question->id }}" required
                        placeholder="Share your thoughts…">{{ old('answers.'.$question->id) }}</textarea>
                    @endif
                    @if ($question->allows_comment)
                    <div class="border-top mt-3 pt-3">
                        <label class="form-label small fw-semibold mb-2" for="comment-{{ $question->id }}">
                            Add a comment <span class="text-body-secondary fw-normal">(optional)</span>
                        </label>
                        <textarea class="form-control bg-body-tertiary rounded-3 px-3 py-2" rows="2"
                            id="comment-{{ $question->id }}" name="answers_comment[{{ $question->id }}]"
                            placeholder="What worked well, or what could be improved?">{{ old('answers_comment.'.$question->id) }}</textarea>
                    </div>
                    @endif
                </fieldset>
                @empty
                <div class="card border-0 rounded-4 shadow-sm p-5 text-center">
                    <h2 class="h5">No questions available</h2>
                    <p class="text-body-secondary mb-0">Please check with your instructor.</p>
                </div>
                @endforelse

                @if ($questions->isNotEmpty())
                <p class="alert alert-warning rounded-3 mt-4 mb-0" id="incomplete-answers" role="alert" hidden>
                    Please answer every question before submitting. Comments are optional.
                </p>
                <div class="d-flex flex-column flex-sm-row align-items-sm-center justify-content-between gap-3 mt-4">
                    <p class="text-body-secondary small mb-0">Thank you for taking the time to share your feedback.</p>
                    <button class="btn btn-success rounded-pill px-4 py-3 flex-shrink-0" type="submit">Submit feedback</button>
                </div>
                @endif
            </form>
        </div>
    </div>
</main>
@endsection
