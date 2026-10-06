<?php

namespace App\Http\Requests;

class UpdateCourseSessionRequest extends StoreCourseSessionRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = parent::rules();
        unset($rules['course_id']);

        return $rules;
    }
}
