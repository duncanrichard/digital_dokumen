<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        // ...
    ];

    public function boot(): void
    {
        $this->registerPolicies();

        // SUPERADMIN BYPASS SEMUA PERMISSION
        Gate::before(function ($user, $ability) {
            // kalau user punya role "Superadmin" (Spatie)
            return $user->hasRole('Superadmin') ? true : null;
        });
    }
}
