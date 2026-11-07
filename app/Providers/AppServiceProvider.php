<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
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
        // super_admin passes every permission check ($user->can(...), @can, can: middleware)
        Gate::before(fn ($user) => $user->hasRole('super_admin') ? true : null);

        Paginator::defaultView('vendor.pagination.estidama');
        Paginator::defaultSimpleView('vendor.pagination.estidama');
    }
}
