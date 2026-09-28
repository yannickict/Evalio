<?php

namespace App\Models;

use Database\Factories\FeedbackFormFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['course_session_id'])]
class FeedbackForm extends Model
{
    /** @use HasFactory<FeedbackFormFactory> */
    use HasFactory;

    /** @return BelongsTo<CourseSession, $this> */
    public function courseSession(): BelongsTo
    {
        return $this->belongsTo(CourseSession::class);
    }
}
