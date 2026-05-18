<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bounce a logged-in merchant out of the Filament admin panel and into the
 * onboarding wizard if they haven't finished it yet.
 *
 * Attached to the StoreAdmin panel's middleware stack. Skips unauthenticated
 * requests (Filament's own auth handles those), skips users without a tenant
 * (defensive — shouldn't happen post-signup but harmless to allow), and skips
 * once the wizard reaches the 'done' step.
 */
class EnsureOnboardingComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if (! $user) {
            return $next($request);
        }

        $tenant = $user->tenant;
        if (! $tenant || $tenant->isOnboarded()) {
            return $next($request);
        }

        return redirect('/onboarding');
    }
}
