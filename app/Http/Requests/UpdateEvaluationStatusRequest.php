<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEvaluationStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // The route checks manage-evaluations.
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'evaluation_status' => ['required', 'in:open,closed'],
        ];
    }
}
