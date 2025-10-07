<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any authentication services.
     */
    public function boot(): void
    {
        Gate::define('view-rh', function (User $user): bool {
            return in_array($user->role, ['rh_manager', 'admin'], true);
        });

        Gate::define('manage-roles', function (User $user): bool {
            return $user->role === 'admin';
        });
    }
}
