<?php

namespace App\Models;

use Database\Factories\QuestionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

/** @property-read Pivot $pivot */
#[Fillable(['question_text', 'type', 'allows_comment'])]
class Question extends Model
{
    /** @use HasFactory<QuestionFactory> */
    use HasFactory;

    /** @return array<string, string> */
    protected function casts(): array
    {
        return ['allows_comment' => 'boolean'];
    }

    /** @return HasMany<QuestionOption, $this> */
    public function options(): HasMany
    {
        return $this->hasMany(QuestionOption::class)->orderBy('id');
    }

    /** @return BelongsToMany<QuestionnaireTemplate, $this> */
    public function questionnaireTemplates(): BelongsToMany
    {
        return $this->belongsToMany(QuestionnaireTemplate::class, 'questionnaire_template_question')->withPivot('position');
    }
}
