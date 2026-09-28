<?php

namespace Database\Factories;

use App\Models\QuestionnaireTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<QuestionnaireTemplate> */
class QuestionnaireTemplateFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
        ];
    }
}
