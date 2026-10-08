<?php

namespace App\Http\Requests;

use Illuminate\Validation\Validator;

class StoreCourseSessionRequest extends CourseSessionRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['course_id' => ['required', 'integer', 'exists:courses,id']] + parent::rules();
    }

    /** @return list<\Closure> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $validator->errors()->has('instructor_id')
                && $this->user()->hasRole('instructor')
                && (int) $this->input('instructor_id') !== $this->user()->id) {
                $validator->errors()->add('instructor_id', __('You can only create sessions for yourself.'));
            }
        }, ...parent::after()];
    }
}
