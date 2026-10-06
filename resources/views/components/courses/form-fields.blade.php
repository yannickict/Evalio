@props(['templates', 'course' => null])

<section aria-labelledby="course-details-heading" class="mb-4 pb-4 border-bottom">
    <h2 id="course-details-heading" class="h5 fw-semibold mb-1">Course details</h2>
    <p class="small text-body-secondary mb-4">Choose a name that makes this course easy to find.</p>
    <label for="course-name" class="form-label fw-medium">Course name</label>
    <input id="course-name" name="name" type="text" class="form-control"
        value="{{ old('name', $course?->name) }}" maxlength="255" required
        placeholder="e.g. Introduction to web development">
</section>

<section aria-labelledby="course-questionnaire-heading">
    <h2 id="course-questionnaire-heading" class="h5 fw-semibold mb-1">Feedback questionnaire</h2>
    <p class="small text-body-secondary mb-4">Choose the questionnaire participants will use to evaluate this course.</p>
    <label for="course-questionnaire" class="form-label fw-medium">Assigned questionnaire</label>
    <select id="course-questionnaire" name="questionnaire_template_id" class="form-select"
        aria-describedby="questionnaire-note" required @disabled($templates->isEmpty())>
        <option value="" @selected(! old('questionnaire_template_id', $course?->questionnaire_template_id)) disabled>Select a questionnaire</option>
        @foreach ($templates as $template)
            <option value="{{ $template->id }}" @selected(old('questionnaire_template_id', $course?->questionnaire_template_id) == $template->id)>{{ $template->name }}</option>
        @endforeach
    </select>
    @if ($templates->isEmpty())
        <p id="questionnaire-note" class="form-text mb-0">
            No questionnaires available. <a href="{{ route('questionnaires.create') }}" class="link-success">Create a questionnaire</a> before adding a course.
        </p>
    @else
        <p id="questionnaire-note" class="form-text mb-0">This questionnaire will be assigned to new sessions. Existing sessions keep their questionnaire.</p>
    @endif
</section>
