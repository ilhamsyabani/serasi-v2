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
        api: __DIR__.'/../routes/api.php',
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
            'throttle.login' => \App\Http\Middleware\ThrottleLogin::class,
            'force.https' => \App\Http\Middleware\ForceHttps::class,
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->append([
            \App\Http\Middleware\ForceHttps::class,
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->redirectGuestsTo(function ($request) {
            return $request->is('pemohon/*')
                ? route('pemohon.login')
                : route('internal.login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Http\Exceptions\PostTooLargeException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Ukuran data yang diupload terlalu besar. Maksimum 64MB per request. Pastikan total ukuran file yang diupload tidak melebihi batas tersebut.',
                ], 413);
            }
            return redirect()->back()->with('error', 'Ukuran data yang diupload terlalu besar. Maksimum 64MB per request. Harap upload file dengan ukuran lebih kecil.');
        });
    })->create();
