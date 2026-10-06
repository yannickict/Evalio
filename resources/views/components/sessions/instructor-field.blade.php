@props(['instructors', 'courseSession' => null])

<label for="session-instructor" class="form-label fw-medium">Instructor</label>
    <select id="session-instructor" name="instructor_id" class="form-select" required>
        <option value="" @selected(! old('instructor_id', $courseSession?->instructor_id)) disabled>Select an instructor</option>
        @if ($courseSession && ! $instructors->contains('id', $courseSession->instructor_id))
        <option value="{{ $courseSession->instructor_id }}" selected disabled>{{ $courseSession->instructor->name }} (currently unavailable)</option>
        @endif
        @foreach ($instructors as $instructor)
        <option value="{{ $instructor->id }}" @selected(old('instructor_id', $courseSession?->instructor_id) == $instructor->id)>{{ $instructor->name }}</option>
        @endforeach
        @if ($instructors->isEmpty() && ! $courseSession)
        <option disabled>No instructors available</option>
        @endif
    </select>
