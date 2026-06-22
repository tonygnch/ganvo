<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
        // CSRF token expiry (419): when a login/form page has sat idle past the
        // session lifetime, a normal browser POST comes back with a dead token
        // and Laravel maps TokenMismatchException -> HttpException(419) -> a raw
        // "page has expired" error. Redirect such requests back to the page they
        // came from (which re-issues a fresh token) with a friendly flash, so
        // the user simply sees the form again instead of a dead-end.
        //
        // NOTE: the handler maps TokenMismatchException to HttpException(419)
        // *before* running render callbacks, so we match on the mapped 419, not
        // the original exception type.
        //
        // We intentionally DON'T touch AJAX / Livewire / JSON requests: Livewire
        // has its own client-side 419 recovery (it offers to refresh) and APIs
        // expect the 419 status. Only plain document navigations are rewritten.
        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($e->getStatusCode() !== 419) {
                return null; // not a CSRF expiry — leave other HTTP errors alone
            }

            if ($request->expectsJson()
                || $request->ajax()
                || $request->hasHeader('X-Livewire')) {
                return null; // let the framework / Livewire handle it
            }

            return redirect()
                ->back()
                ->withInput($request->except('_token', 'password', 'password_confirmation'))
                ->with('error', __('Your session expired — please try again.'));
        });
    })->create();
