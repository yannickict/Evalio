<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['instructor', 'editor', 'admin'] as $name) {
            Role::firstOrCreate(['name' => $name]);
        }
    }
}
