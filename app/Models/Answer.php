<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['feedback_form_id', 'question_id', 'question_option_id', 'answer_text', 'comment'])]
class Answer extends Model
{
    /** @return BelongsTo<FeedbackForm, $this> */
    public function feedbackForm(): BelongsTo
    {
        return $this->belongsTo(FeedbackForm::class);
    }

    /** @return BelongsTo<Question, $this> */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /** @return BelongsTo<QuestionOption, $this> */
    public function questionOption(): BelongsTo
    {
        return $this->belongsTo(QuestionOption::class);
    }
}
