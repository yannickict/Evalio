<?php

namespace App\Models;

use Database\Factories\CourseSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property-read string $session_identifier
 */
#[Fillable(['course_id', 'instructor_id', 'questionnaire_template_id', 'session_number', 'start_date', 'end_date', 'evaluation_status'])]
class CourseSession extends Model
{
    /** @use HasFactory<CourseSessionFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (CourseSession $session): void {
            if (! array_key_exists('questionnaire_template_id', $session->getAttributes())) {
                $session->questionnaire_template_id = Course::query()->whereKey($session->course_id)
                    ->value('questionnaire_template_id');
            }

            $number = $session->getAttributes()['session_number'] ?? null;

            if ($number !== null && $number !== '') {
                return;
            }

            $session->session_number = max(0, (int) static::where('course_id', $session->course_id)
                ->max('session_number')) + 1;
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'session_number' => 'integer'];
    }

    /** @return Attribute<non-falsy-string, never> */
    protected function sessionIdentifier(): Attribute
    {
        return Attribute::get(fn (): string => sprintf('%s.%03d', $this->course->name, $this->session_number));
    }

    /** @return BelongsTo<Course, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return BelongsTo<User, $this> */
    public function instructor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'instructor_id');
    }

    /** @return BelongsTo<QuestionnaireTemplate, $this> */
    public function questionnaireTemplate(): BelongsTo
    {
        return $this->belongsTo(QuestionnaireTemplate::class);
    }

    /** @return HasOne<FeedbackForm, $this> */
    public function feedbackForm(): HasOne
    {
        return $this->hasOne(FeedbackForm::class);
    }
}
