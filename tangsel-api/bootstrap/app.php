<?php

use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => EnsureUserHasRole::class,
        ]);

        // Aplikasi ini hanya menyediakan API, sehingga tidak ada rute
        // "login" yang bisa dipakai untuk pengalihan tamu. Tanpa
        // penyesuaian ini, permintaan tanpa token berakhir sebagai galat
        // 500 alih-alih jawaban 401 dalam format JSON.
        $middleware->redirectGuestsTo(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson() ? null : route('login'),
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
