<?php

namespace App\Services;

/**
 * Currency formatting + conversion.
 *
 * Internally everything is "minor units" (cents for 2-decimal currencies).
 * All currently-supported currencies are 2-decimal so the math stays simple;
 * if we ever add zero-decimal currencies (JPY, KRW) or 3-decimal (BHD), the
 * `decimals` field below + convert()'s scale factor handle it.
 */
class Money
{
    /**
     * Currencies a store admin can pick from. The order here also drives
     * UI ordering in dropdowns.
     *
     * `position` controls where the symbol goes:
     *   prefix → "$1,234.56"
     *   suffix → "1,234.56 лв."  (used by Bulgarian Lev — the local convention)
     */
    public const SUPPORTED = [
        'USD' => ['symbol' => '$',    'name' => 'US Dollar',         'decimals' => 2, 'position' => 'prefix'],
        'EUR' => ['symbol' => '€',    'name' => 'Euro',              'decimals' => 2, 'position' => 'prefix'],
        'GBP' => ['symbol' => '£',    'name' => 'British Pound',     'decimals' => 2, 'position' => 'prefix'],
        'CAD' => ['symbol' => 'CA$',  'name' => 'Canadian Dollar',   'decimals' => 2, 'position' => 'prefix'],
        'AUD' => ['symbol' => 'A$',   'name' => 'Australian Dollar', 'decimals' => 2, 'position' => 'prefix'],
        'BGN' => ['symbol' => 'лв.',  'name' => 'Bulgarian Lev',     'decimals' => 2, 'position' => 'suffix'],
    ];

    /**
     * The OFFICIAL fixed peg the Bulgarian Lev was switched to before Bulgaria
     * adopted the Euro. Set by law / the BNB — this is not a floating FX rate
     * and never changes. 1 EUR == 1.95583 BGN.
     *
     * During the transition, Bulgarian merchants are required to display
     * prices in EUR as primary with BGN as a secondary reference; this
     * constant is what we multiply EUR by to produce that secondary value.
     */
    public const EUR_BGN_RATE = 1.95583;

    /**
     * Format a minor-units integer as a localized money string.
     * Unknown codes fall through to "<CODE> 1,234.56" so we never silently
     * drop the value.
     */
    public static function format(int $cents, string $code): string
    {
        $code = strtoupper($code);
        $meta = self::SUPPORTED[$code] ?? null;
        $decimals = $meta['decimals'] ?? 2;
        $symbol = $meta['symbol'] ?? ($code . ' ');
        $position = $meta['position'] ?? 'prefix';
        $value = $cents / (10 ** $decimals);
        $formatted = number_format($value, $decimals, '.', ',');
        return $position === 'suffix' ? $formatted . ' ' . $symbol : $symbol . $formatted;
    }

    /**
     * Convert minor-units from base currency to a target currency at the given rate.
     *
     * The rate is "units of target per 1 unit of base" — same convention as
     * Stripe/Wise UIs. So 1 USD * 0.92 = 0.92 EUR.
     *
     * Both base and target are assumed to be in the same decimal scale (2 for
     * everything we currently support). When/if we add JPY etc., extend with a
     * second-currency parameter and rescale here.
     */
    public static function convert(int $baseCents, float $rate): int
    {
        return (int) round($baseCents * $rate);
    }

    /**
     * Display-ready string for a base-currency amount in a target currency.
     *
     * Special case: when the customer has picked BGN, render the EUR-primary
     * dual format — "€18.39 (35.97 лв.)" — matching the legal transition
     * convention in Bulgaria. The $rate here is base → BGN; the EUR companion
     * is computed *directly from base* (via rate / EUR_BGN_RATE), not via a
     * round-trip through BGN, so EUR-base stores don't see a one-cent
     * rounding drift.
     */
    public static function display(int $baseCents, float $rate, string $code): string
    {
        $code = strtoupper($code);
        if ($code === 'BGN') {
            $bgnCents = self::convert($baseCents, $rate);
            // EUR rate = BGN rate / 1.95583. Computing the EUR amount directly
            // from base preserves precision (e.g. €19.99 stays €19.99 instead
            // of rounding to €20.00 when reverse-derived from rounded BGN).
            $eurCents = (int) round($baseCents * ($rate / self::EUR_BGN_RATE));
            return self::format($eurCents, 'EUR') . ' (' . self::format($bgnCents, 'BGN') . ')';
        }
        return self::format(self::convert($baseCents, $rate), $code);
    }

    /**
     * Format an amount that's already been converted to the display currency
     * (i.e. snapshot data on an order). Like display() but skips the base→target
     * conversion step.
     *
     * For BGN orders, we re-derive the EUR primary from the BGN snapshot via
     * the peg. When the order's base currency happens to be EUR, prefer the
     * exact stored base amount — that avoids the 1-cent drift that
     * `round($bgnCents / 1.95583)` would produce.
     */
    public static function formatAsDisplay(
        int $cents,
        string $code,
        ?int $baseCents = null,
        ?string $baseCode = null
    ): string {
        $code = strtoupper($code);
        if ($code === 'BGN') {
            if ($baseCents !== null && $baseCode !== null && strtoupper($baseCode) === 'EUR') {
                $eurCents = $baseCents;
            } else {
                $eurCents = (int) round($cents / self::EUR_BGN_RATE);
            }
            return self::format($eurCents, 'EUR') . ' (' . self::format($cents, 'BGN') . ')';
        }
        return self::format($cents, $code);
    }

    public static function symbol(string $code): string
    {
        return self::SUPPORTED[strtoupper($code)]['symbol'] ?? $code;
    }

    public static function name(string $code): string
    {
        return self::SUPPORTED[strtoupper($code)]['name'] ?? $code;
    }

    /** Whether a code is one we support out of the box. */
    public static function supports(string $code): bool
    {
        return isset(self::SUPPORTED[strtoupper($code)]);
    }

    /** @return array<string, array{symbol: string, name: string, decimals: int}> */
    public static function supported(): array
    {
        return self::SUPPORTED;
    }

    /** Options array for Filament Select / Radio — [code => "USD — US Dollar ($)"]. */
    public static function options(): array
    {
        $out = [];
        foreach (self::SUPPORTED as $code => $meta) {
            $out[$code] = sprintf('%s — %s (%s)', $code, $meta['name'], $meta['symbol']);
        }
        return $out;
    }
}
