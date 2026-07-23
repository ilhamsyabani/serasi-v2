<?php

use App\Http\Middleware\PbfAuthenticate;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\PemohonRedirectIfAuthenticated;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function ($router) {
            require __DIR__.'/../routes/internal.php';
            require __DIR__.'/../routes/pemohon.php';
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'pbf.auth' => PbfAuthenticate::class,
            'pbf.guest' => PemohonRedirectIfAuthenticated::class,
        ]);

        // Aplikasi punya dua portal dengan halaman login berbeda dan TIDAK punya
        // route bernama `login` (default yang dicari middleware `auth`). Tanpa ini,
        // tamu yang membuka URL terlindungi mendapat RouteNotFoundException.
        $middleware->redirectGuestsTo(function ($request) {
            return $request->is('pemohon/*')
                ? route('pemohon.login')
                : route('internal.login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
