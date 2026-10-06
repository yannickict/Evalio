<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCourseSessionRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', 'exists:courses,id'],
            'instructor_id' => ['required', 'integer', 'exists:users,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ];
    }

    /** @return list<\Closure> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->has('instructor_id')) {
                return;
            }

            $user = $this->user();

            if (
                $user->role?->name === 'instructor'
                && (int) $this->input('instructor_id') !== $user->id
            ) {
                $validator->errors()->add(
                    'instructor_id',
                    'You can only create sessions for yourself.'
                );

                return;
            }

            if (! User::approvedInstructors()->whereKey($this->input('instructor_id'))->exists()) {
                $validator->errors()->add('instructor_id', 'Choose an approved instructor.');
            }
        }];
    }
}
