<?php

namespace App\Providers;

use App\Events\PermohonanStatusChanged;
use App\Listeners\PermohonanNotifier;
use App\Models\Distribusi;
use App\Models\HariLibur;
use App\Models\Permohonan;
use App\Models\User;
use App\Policies\DistribusiPolicy;
use App\Policies\HariLiburPolicy;
use App\Policies\PermohonanPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\DB;
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
        // Cegah migrate:fresh/refresh/reset & db:wipe mengosongkan DB asli.
        DB::prohibitDestructiveCommands(
            ! $this->app->runningUnitTests()
            && ! config('database.allow_destructive_commands')
        );

        Gate::policy(Permohonan::class, PermohonanPolicy::class);
        Gate::policy(Distribusi::class, DistribusiPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(HariLibur::class, HariLiburPolicy::class);

        $this->app['events']->listen(
            PermohonanStatusChanged::class,
            PermohonanNotifier::class,
        );
    }
}
