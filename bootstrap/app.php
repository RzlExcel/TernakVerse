<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Rute Portal WebGIS Publik (ternak.banyumaskab.go.id)
            Route::prefix('api/v1/public')
                ->middleware('api')
                ->name('public.')
                ->group(__DIR__.'/../routes/public.php');

            // Rute Portal Internal Petugas Dinas & Admin (internal-ternak.banyumaskab.go.id)
            Route::prefix('api/v1/internal')
                ->middleware('api')
                ->name('internal.')
                ->group(__DIR__.'/../routes/internal.php');
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
