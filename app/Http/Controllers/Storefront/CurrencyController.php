<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Middleware\SetDisplayCurrency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cookie;

class CurrencyController extends Controller
{
    /**
     * Sets the customer's preferred display currency for this storefront.
     * Mirrors the language switcher's pattern (GET, persistent cookie, redirect back).
     */
    public function switch(string $code): RedirectResponse
    {
        $code = strtoupper($code);
        $store = app('current_tenant')->store;

        if (! in_array($code, $store->supportedDisplayCurrencies(), true)) {
            // Unknown / unsupported code — silently bounce.
            return back();
        }

        return back()->withCookie(
            Cookie::make(SetDisplayCurrency::COOKIE, $code, 60 * 24 * 365)
        );
    }
}
