<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['instructor', 'editor', 'admin'] as $name) {
            Role::updateOrCreate(['name' => $name], [
                'see_overview' => $name !== 'instructor',
                'update' => $name !== 'instructor',
                'delete' => $name === 'admin',
                'assign_roles' => $name === 'admin',
                'approve_registrations' => $name === 'admin',
            ]);
        }
    }
}
