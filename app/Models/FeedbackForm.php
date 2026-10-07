<?php

namespace App\Models;

use Database\Factories\FeedbackFormFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['course_session_id', 'code'])]
class FeedbackForm extends Model
{
    /** @use HasFactory<FeedbackFormFactory> */
    use HasFactory;

    /** @return BelongsTo<CourseSession, $this> */
    public function courseSession(): BelongsTo
    {
        return $this->belongsTo(CourseSession::class);
    }

    /** @return HasMany<Answer, $this> */
    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }
}
