<?php

namespace App\Providers;

use App\Models\Distribusi;
use App\Models\Permohonan;
use App\Models\User;
use App\Policies\DistribusiPolicy;
use App\Policies\PermohonanPolicy;
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
        Gate::policy(Permohonan::class, PermohonanPolicy::class);
        Gate::policy(Distribusi::class, DistribusiPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
    }
}
