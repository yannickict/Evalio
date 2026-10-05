<?php

namespace App\Models;

use Database\Factories\CourseSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property Carbon $start_date
 * @property Carbon $end_date
 */
#[Fillable(['course_id', 'instructor_id', 'course_session_number', 'start_date', 'end_date', 'evaluation_status'])]
class CourseSession extends Model
{
    /** @use HasFactory<CourseSessionFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (CourseSession $session): void {
            $number = $session->getAttributes()['course_session_number'] ?? null;

            if ($number !== null && $number !== '') {
                return;
            }

            $highestNumber = 0;

            foreach (static::query()->pluck('course_session_number') as $number) {
                if (preg_match('/^COURSE\.(\d+)$/', $number, $matches)) {
                    $highestNumber = max($highestNumber, (int) $matches[1]);
                }
            }

            $session->course_session_number = sprintf('COURSE.%04d', $highestNumber + 1);
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
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

    /** @return HasOne<FeedbackForm, $this> */
    public function feedbackForm(): HasOne
    {
        return $this->hasOne(FeedbackForm::class);
    }
}
