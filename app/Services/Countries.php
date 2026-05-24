<?php

namespace App\Services;

/**
 * Curated country list for the storefront checkout dropdown.
 *
 * This isn't an authoritative ISO 3166 list — it's a working set of
 * countries we expect the typical Ganvo merchant to ship to (EU, US,
 * UK, Canada, Australia, and a handful elsewhere). The keys are
 * ISO 3166-1 alpha-2 codes (what gets persisted on the order) and the
 * values are display names.
 *
 * Add to this list rather than building a full registry — the long
 * tail of countries can come in a follow-up slice if/when a merchant
 * asks for it.
 */
class Countries
{
    public const LIST = [
        'BG' => 'Bulgaria',
        'AT' => 'Austria',
        'BE' => 'Belgium',
        'HR' => 'Croatia',
        'CY' => 'Cyprus',
        'CZ' => 'Czech Republic',
        'DK' => 'Denmark',
        'EE' => 'Estonia',
        'FI' => 'Finland',
        'FR' => 'France',
        'DE' => 'Germany',
        'GR' => 'Greece',
        'HU' => 'Hungary',
        'IE' => 'Ireland',
        'IT' => 'Italy',
        'LV' => 'Latvia',
        'LT' => 'Lithuania',
        'LU' => 'Luxembourg',
        'MT' => 'Malta',
        'NL' => 'Netherlands',
        'PL' => 'Poland',
        'PT' => 'Portugal',
        'RO' => 'Romania',
        'SK' => 'Slovakia',
        'SI' => 'Slovenia',
        'ES' => 'Spain',
        'SE' => 'Sweden',
        'GB' => 'United Kingdom',
        'CH' => 'Switzerland',
        'NO' => 'Norway',
        'IS' => 'Iceland',
        'US' => 'United States',
        'CA' => 'Canada',
        'MX' => 'Mexico',
        'AU' => 'Australia',
        'NZ' => 'New Zealand',
        'JP' => 'Japan',
        'SG' => 'Singapore',
        'AE' => 'United Arab Emirates',
        'IL' => 'Israel',
        'TR' => 'Turkey',
        'RS' => 'Serbia',
        'UA' => 'Ukraine',
    ];

    public static function all(): array
    {
        return self::LIST;
    }

    public static function isValid(string $code): bool
    {
        return array_key_exists(strtoupper($code), self::LIST);
    }

    public static function name(string $code): ?string
    {
        return self::LIST[strtoupper($code)] ?? null;
    }
}
