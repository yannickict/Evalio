<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Role> */
class RoleFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'see_overview' => false,
            'update' => false,
            'delete' => false,
            'assign_roles' => false,
            'approve_registrations' => false,
        ];
    }
}
