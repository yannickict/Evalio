<?php

namespace App\Http\Requests;

use App\Models\FeedbackForm;
use App\Models\QuestionnaireTemplate;
use Illuminate\Foundation\Http\FormRequest;

class FeedbackCodeRequest extends FormRequest
{
    private ?FeedbackForm $feedbackForm = null;

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['code' => ['required', 'digits:6']];
    }

    /** @return array<string, mixed> */
    public function validationData(): array
    {
        return array_merge($this->all(), ['code' => $this->query('code')]);
    }

    public function form(): FeedbackForm
    {
        $form = $this->feedbackForm ??= FeedbackForm::with([
            'courseSession.course',
            'courseSession.questionnaireTemplate.questions.options',
        ])
            ->where('code', $this->query('code'))
            ->firstOrFail();

        abort_unless($form->courseSession->evaluation_status === 'open', 403, __('This evaluation is closed.'));

        return $form;
    }

    public function template(): QuestionnaireTemplate
    {
        $template = $this->form()->courseSession->questionnaireTemplate;

        abort_if($template === null, 404, __('No questionnaire assigned.'));

        return $template;
    }
}
