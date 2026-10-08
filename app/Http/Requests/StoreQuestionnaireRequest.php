<?php

namespace App\Http\Requests;

class StoreQuestionnaireRequest extends QuestionnaireRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $rules = parent::rules();
        $rules['name'] = ['required', 'string', 'max:255', 'unique:questionnaire_templates,name'];
        $rules['questions.*.text'] = ['required', 'string', 'max:5000'];

        $questions = $this->input('questions', []);

        foreach (is_array($questions) ? $questions : [] as $index => $question) {
            if (is_array($question) && ($question['type'] ?? null) === 'single_choice') {
                $rules["questions.$index.options"] = ['required', 'array', 'min:2', 'max:20'];
                $rules["questions.$index.options.*"] = ['required', 'string', 'max:1000'];
            }
        }

        return $rules;
    }
}
