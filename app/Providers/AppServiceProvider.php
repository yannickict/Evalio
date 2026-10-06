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
        Gate::define('create-courses', fn (User $user): bool => in_array($user->role?->name, ['admin', 'editor'], true));
        Gate::define('create-questionnaires', fn (User $user): bool => in_array($user->role?->name, ['admin', 'editor'], true));
        Gate::define('edit-courses', fn (User $user): bool => in_array($user->role?->name, ['admin', 'editor'], true));
        Gate::define('edit-sessions', fn (User $user): bool => in_array($user->role?->name, ['admin', 'editor'], true));
        Gate::define('edit-questionnaires', fn (User $user): bool => in_array($user->role?->name, ['admin', 'editor'], true));
        Gate::define(
            'view-session-filters',
            fn (User $user): bool => in_array($user->role?->name, ['admin', 'editor'], true)
        );
        Gate::define(
            'view-workflow-guide',
            fn (User $user): bool => in_array($user->role?->name, ['admin', 'editor', 'instructor'], true)
        );
        Gate::define(
            'manage-evaluations',
            fn (User $user): bool => in_array($user->role?->name, ['admin', 'editor'], true)
        );
    }
}
