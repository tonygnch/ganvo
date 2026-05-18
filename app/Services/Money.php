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
     */
    public const SUPPORTED = [
        'USD' => ['symbol' => '$',   'name' => 'US Dollar',         'decimals' => 2],
        'EUR' => ['symbol' => '€',   'name' => 'Euro',              'decimals' => 2],
        'GBP' => ['symbol' => '£',   'name' => 'British Pound',     'decimals' => 2],
        'CAD' => ['symbol' => 'CA$', 'name' => 'Canadian Dollar',   'decimals' => 2],
        'AUD' => ['symbol' => 'A$',  'name' => 'Australian Dollar', 'decimals' => 2],
    ];

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
        $value = $cents / (10 ** $decimals);
        return $symbol . number_format($value, $decimals, '.', ',');
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

    /** Display-ready string for a base-currency amount in a target currency. */
    public static function display(int $baseCents, float $rate, string $code): string
    {
        return self::format(self::convert($baseCents, $rate), $code);
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
