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

        // Stripe webhooks aren't browser sessions — they're server-to-
        // server POSTs from Stripe's IPs that can't carry a CSRF token.
        // Both endpoints below are signature-verified inside their
        // controllers, so dropping CSRF here is safe.
        $middleware->validateCsrfTokens(except: [
            'stripe/webhook',          // Cashier (subscription) webhook
            'webhooks/stripe/connect', // Connect (storefront PI) webhook
        ]);

        // Trust reverse-proxy headers so url() generates `https://` when
        // we sit behind Caddy (dev `--profile https` or prod). Without
        // this Laravel only sees the inbound http://app:8000 scheme and
        // emits mixed-content links. '*' is broad but safe inside a
        // Docker compose network where the only proxy on the path is
        // ours; tighten to a CIDR if you ever expose this beyond local.
        $middleware->trustProxies(at: '*');

        // Unauthenticated requests inside the merchant area (e.g. /onboarding/*)
        // get bounced here. Filament panels handle their own login redirects.
        $middleware->redirectGuestsTo(fn () => url('/onboarding/login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
