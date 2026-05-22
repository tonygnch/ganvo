<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves which currency a customer wants to view prices in on the storefront,
 * and shares it (plus the conversion rate from the store's base currency) with
 * every view via View::share.
 *
 * Must run AFTER ResolveStorefrontTenant — it needs the current tenant's store
 * to know the base currency, list of supported display currencies, and FX rates.
 *
 * Resolution order:
 *   1. ?currency=XYZ query (also persisted as a cookie when valid)
 *   2. ganvo_display_currency cookie
 *   3. The store's base currency
 */
class SetDisplayCurrency
{
    public const COOKIE = 'ganvo_display_currency';

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = app()->bound('current_tenant') ? app('current_tenant') : null;
        if (! $tenant || ! $tenant->store) {
            // Outside the storefront context — nothing to do.
            return $next($request);
        }

        $store = $tenant->store;
        $base = strtoupper($store->currency ?? 'EUR');
        $supported = $store->supportedDisplayCurrencies();

        $code = $this->resolve($request, $base, $supported);
        $rate = $store->fxRateFor($code);

        app()->instance('display_currency', $code);
        app()->instance('display_rate', $rate);

        // Make available to every Blade view in this request.
        View::share('displayCurrency', $code);
        View::share('displayRate', $rate);
        View::share('baseCurrency', $base);

        return $next($request);
    }

    /**
     * @param array<int, string> $supported
     */
    private function resolve(Request $request, string $base, array $supported): string
    {
        $query = $request->query('currency');
        if (is_string($query) && in_array(strtoupper($query), $supported, true)) {
            $code = strtoupper($query);
            \Illuminate\Support\Facades\Cookie::queue(self::COOKIE, $code, 60 * 24 * 365);
            return $code;
        }

        $cookie = $request->cookie(self::COOKIE);
        if (is_string($cookie) && in_array(strtoupper($cookie), $supported, true)) {
            return strtoupper($cookie);
        }

        return $base;
    }
}
