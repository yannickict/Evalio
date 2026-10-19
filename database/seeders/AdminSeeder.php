<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('admin.email');
        if (! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Set a valid ADMIN_EMAIL in .env before running AdminSeeder.');
        }

        DB::transaction(function () use ($email): void {
            $existing = User::where('email', $email)->first();
            if ($existing) {
                if (! $existing->hasRole('admin')) {
                    throw new RuntimeException('ADMIN_EMAIL already belongs to a non-admin user. No account was changed.');
                }

                return;
            }

            if (User::whereHas('role', fn ($query) => $query->where('name', 'admin'))->exists()) {
                throw new RuntimeException('An administrator already exists. No additional account was created.');
            }

            $values = [
                'password' => config('admin.password'),
                'first_name' => config('admin.first_name'),
                'last_name' => config('admin.last_name'),
            ];
            if (Validator::make($values, [
                'password' => ['required', 'string', 'min:12'],
                'first_name' => ['required', 'string', 'max:255'],
                'last_name' => ['required', 'string', 'max:255'],
            ])->fails()) {
                throw new RuntimeException('Set ADMIN_PASSWORD (at least 12 characters), ADMIN_FIRST_NAME and ADMIN_LAST_NAME before running AdminSeeder.');
            }

            $role = Role::firstOrCreate(['name' => 'admin']);
            $user = new User;
            $user->forceFill([
                ...$values,
                'email' => $email,
                'role_id' => $role->id,
                'is_approved' => true,
                'email_verified_at' => now(),
            ])->save();
        });
    }
}
