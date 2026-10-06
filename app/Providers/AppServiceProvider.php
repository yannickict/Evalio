<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::define('view-course-lists', fn (User $user): bool => $user->role !== null);
        Gate::define('manage-users', fn (User $user): bool => $user->role?->name === 'admin');
        Gate::define(
            'manage-evaluations',
            fn (User $user): bool => in_array($user->role?->name, ['admin', 'editor'], true)
        );
    }
}
