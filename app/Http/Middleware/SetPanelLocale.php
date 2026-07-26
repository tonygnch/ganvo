<?php

namespace App\Http\Middleware;

use App\Support\PanelLocale;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies the merchant's own language choice to the admin panel.
 *
 * A Filament panel does not run the `web` middleware group — it builds its own
 * stack — so SetLocale, which handles the storefronts, never sees these
 * requests. Without this the panel was pinned to APP_LOCALE for everybody, and
 * a merchant had no way to change it.
 *
 * Registered AFTER StartSession in the panel's stack, and NOT in authMiddleware:
 * it has to run on the login screen too, where there is no user yet and the
 * server default applies.
 */
class SetPanelLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        App::setLocale(PanelLocale::forUser($request->user()));

        return $next($request);
    }
}
