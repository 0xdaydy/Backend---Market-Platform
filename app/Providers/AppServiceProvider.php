<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);

        Gate::define('isAdmin', function (User $user): bool {
            return $user->role === UserRole::Admin;
        });

        Gate::define('isSupervisor', function (User $user): bool {
            return $user->role === UserRole::Supervisor;
        });

        Gate::define('isOperator', function (User $user): bool {
            return $user->role === UserRole::Operator;
        });
    }
}
