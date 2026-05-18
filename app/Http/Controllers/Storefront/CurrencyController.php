<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SetDisplayCurrency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class CurrencyController extends Controller
{
    /**
     * Sets the customer's preferred display currency for this storefront.
     *
     * IMPORTANT: don't add `string $code` as a method parameter. The storefront
     * routes live under Route::domain('{tenantSlug}.ganvo.lvh.me'), so
     * positional string injection picks up the *domain* parameter first
     * ("acme") and the path parameter `{code}` never reaches the method. Pull
     * it off the route by name instead, matching LanguageController.
     */
    public function switch(Request $request): RedirectResponse
    {
        $code = strtoupper((string) $request->route('code'));
        $store = app('current_tenant')->store;

        if (! in_array($code, $store->supportedDisplayCurrencies(), true)) {
            return redirect($request->headers->get('referer') ?: '/');
        }

        Cookie::queue(SetDisplayCurrency::COOKIE, $code, 60 * 24 * 365);

        $referer = $request->headers->get('referer');
        return redirect($referer ?: '/');
    }
}
