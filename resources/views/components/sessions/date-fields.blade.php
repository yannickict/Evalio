@props(['courseSession' => null])

<section aria-labelledby="course-dates-heading">
    <h2 id="course-dates-heading" class="h5 fw-semibold mb-1">{{ __('Course dates') }}</h2>
    <p class="small text-body-secondary mb-4">{{ __('Choose when the course starts and ends. Evaluations close 14 days after the end date.') }}</p>
    <div class="row g-4">
        <div class="col-md-6">
            <label for="session-start-date" class="form-label fw-medium">{{ __('Start date') }}</label>
            <input id="session-start-date" name="start_date" value="{{ old('start_date', $courseSession?->start_date?->toDateString()) }}" type="date" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label for="session-end-date" class="form-label fw-medium">{{ __('End date') }}</label>
            <input id="session-end-date" name="end_date" value="{{ old('end_date', $courseSession?->end_date?->toDateString()) }}" type="date" class="form-control" required>
        </div>
    </div>
</section>
