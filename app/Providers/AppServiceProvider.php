<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Pagination\Paginator;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{

    public function register(): void {}


    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Gate::define('mesa-entrada-send', fn (User $u) => $u->role === 'mesa' || $u->role === 'admin');
        Gate::define('mesa-entrada-view', fn (User $u) => in_array($u->role, ['admin','writer','reader','mesa'], true));
        
        Gate::define('view-maps', fn (User $u) =>
            in_array($u->role, ['admin','writer','reader'], true)
        );

        Gate::define('manage-ubicaciones', fn (User $u) =>
            in_array($u->role, ['admin','writer'], true)
        );

        Gate::define('access-admin', fn (User $u) =>
            $u->role === 'admin'
        );
    }
}
