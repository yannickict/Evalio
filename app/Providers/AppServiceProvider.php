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
        Gate::define('manage-users', fn (User $user): bool => $user->hasRole('admin'));
        Gate::define('create-courses', fn (User $user): bool => $user->hasRole('admin', 'editor'));
        Gate::define('create-questionnaires', fn (User $user): bool => $user->hasRole('admin', 'editor'));
        Gate::define('edit-courses', fn (User $user): bool => $user->hasRole('admin', 'editor'));
        Gate::define('edit-sessions', fn (User $user): bool => $user->hasRole('admin', 'editor'));
        Gate::define('edit-questionnaires', fn (User $user): bool => $user->hasRole('admin', 'editor'));
        Gate::define(
            'view-session-filters',
            fn (User $user): bool => $user->hasRole('admin', 'editor')
        );
        Gate::define(
            'view-workflow-guide',
            fn (User $user): bool => $user->hasRole('admin', 'editor', 'instructor')
        );
        Gate::define(
            'manage-evaluations',
            fn (User $user): bool => $user->hasRole('admin', 'editor')
        );
    }
}
