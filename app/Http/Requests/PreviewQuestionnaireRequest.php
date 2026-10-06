<?php

namespace App\Http\Requests;

class PreviewQuestionnaireRequest extends QuestionnaireRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return parent::rules() + [
            'action' => ['required', 'string', 'regex:/\A(?:refresh|add_question|add_option:[0-9]+|remove_question:[0-9]+|remove_option:[0-9]+:[0-9]+)\z/'],
        ];
    }
}
