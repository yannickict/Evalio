<?php

namespace App\Http\Requests;

use Illuminate\Support\Facades\Validator as ValidatorFacade;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreFeedbackResponseRequest extends FeedbackCodeRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return parent::rules() + [
            'answers' => ['sometimes', 'array'],
            'answers.*' => ['nullable'],
            'answers_comment' => ['sometimes', 'array'],
            'answers_comment.*' => ['nullable', 'string', 'max:5000'],
        ];
    }

    /** @return list<\Closure> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $questions = $this->template()->questions->keyBy('id');
            $data = $validator->validated();

            foreach ($data['answers'] ?? [] as $questionId => $answer) {
                $question = $questions->get($questionId);
                $field = "answers.$questionId";

                if ($question === null) {
                    $validator->errors()->add($field, __('Choose a question from this questionnaire.'));

                    continue;
                }

                $rules = $question->type === 'single_choice'
                    ? ['nullable', 'integer', Rule::in($question->options->modelKeys())]
                    : ['nullable', 'string', 'max:5000'];
                $answerValidator = ValidatorFacade::make(['answer' => $answer], ['answer' => $rules]);

                foreach ($answerValidator->errors()->all() as $error) {
                    $validator->errors()->add($field, $error);
                }
            }

            foreach ($data['answers_comment'] ?? [] as $questionId => $comment) {
                $question = $questions->get($questionId);

                if ($question === null || ! $question->allows_comment) {
                    $validator->errors()->add("answers_comment.$questionId", __('Comments are not allowed for this question.'));
                }
            }
        }];
    }
}
