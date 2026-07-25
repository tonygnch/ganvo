<?php

namespace App\Http\Middleware;

use App\Support\BasicAuthGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Per-storefront HTTP Basic, configured per tenant in the Super Admin panel.
 *
 * One client's site can sit behind a password on its public subdomain while
 * every other tenant on the platform stays open — which is the difference
 * between this and PreviewGate, the env-driven switch that locks EVERYTHING
 * including the admin panels.
 *
 * MUST run after ResolveStorefrontTenant: it needs the tenant to know whose
 * lock to apply. Attached inside the storefront route groups for that reason
 * rather than in the global `web` stack.
 *
 * A store with the lock ON but no credentials saved FAILS CLOSED. The Super
 * Admin form will not let you save that combination, but a hand-edited row
 * could, and publishing the site because its lock is half-configured is the
 * one outcome this must never produce.
 */
class StorefrontPreviewGate
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = app()->bound('current_tenant') ? app('current_tenant') : null;
        $store = $tenant?->store;

        if (! $store || ! $store->preview_lock) {
            return $next($request);
        }

        $user = (string) ($store->preview_user ?? '');
        $hash = (string) ($store->preview_password_hash ?? '');

        if ($user === '' || $hash === '') {
            return BasicAuthGate::challenge(
                (string) ($tenant->name ?? 'Preview'),
                'This storefront is locked but has no preview credentials saved.'
            );
        }

        if (! BasicAuthGate::passes($request, $user, $hash)) {
            // The realm is the shop's own name, so a visitor with several
            // previews open can tell which one is asking.
            return BasicAuthGate::challenge((string) ($tenant->name ?? 'Preview'));
        }

        $response = $next($request);
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow', true);

        return $response;
    }
}
