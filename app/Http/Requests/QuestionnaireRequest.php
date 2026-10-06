<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

abstract class QuestionnaireRequest extends FormRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'questions' => ['required', 'array', 'min:1', 'max:50'],
            'questions.*' => ['array:text,type,options,allows_comment'],
            'questions.*.allows_comment' => ['sometimes', 'boolean'],
            'questions.*.text' => ['nullable', 'string', 'max:5000'],
            'questions.*.type' => ['required', 'in:single_choice,free_text'],
            'questions.*.options' => ['sometimes', 'array', 'max:20'],
            'questions.*.options.*' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
