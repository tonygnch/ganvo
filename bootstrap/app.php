<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        // Unauthenticated requests inside the merchant area (e.g. /onboarding/*)
        // get bounced here. Filament panels handle their own login redirects.
        $middleware->redirectGuestsTo(fn () => url('/onboarding/login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
