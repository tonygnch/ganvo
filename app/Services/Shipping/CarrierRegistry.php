<?php

namespace App\Services\Shipping;

/**
 * Carrier metadata + tracking URL templates.
 *
 * Single source of truth used by:
 *   - StoreAdmin ViewOrder (Mark shipped + Edit tracking selects)
 *   - OrderShipped notification (carrier label, fallback URL)
 *   - Storefront order page (clickable tracking link)
 *
 * Each carrier definition is shape:
 *   slug => [
 *     'label'    => display name shown in admin + email
 *     'url'      => sprintf template — %s is replaced by the tracking
 *                   number (urlencoded). Null when the carrier doesn't
 *                   expose a parseable URL pattern.
 *     'region'   => 'eu' | 'na' | 'global' — used to prioritize lists
 *                   for merchants in a given region. Bulgarian /
 *                   EU carriers float to the top of the default list.
 *   ]
 *
 * Adding a new carrier: drop a row in CARRIERS, that's it — every
 * caller picks it up automatically.
 */
class CarrierRegistry
{
    /** @var array<string, array{label: string, url: ?string, region: string}> */
    public const CARRIERS = [
        // EU / BG-first — these are the carriers the typical early
        // merchant on Ganvo actually uses.
        'econt'   => ['label' => 'Econt',   'url' => 'https://www.econt.com/services/track-shipment/%s',           'region' => 'eu'],
        'speedy'  => ['label' => 'Speedy',  'url' => 'https://www.speedy.bg/en/track-shipment?shipmentNumber=%s', 'region' => 'eu'],
        'dpd'     => ['label' => 'DPD',     'url' => 'https://tracking.dpd.de/status/en_US/parcel/%s',             'region' => 'eu'],
        'gls'     => ['label' => 'GLS',     'url' => 'https://gls-group.com/EU/en/parcel-tracking?match=%s',      'region' => 'eu'],
        'dhl'     => ['label' => 'DHL',     'url' => 'https://www.dhl.com/global-en/home/tracking.html?tracking-id=%s', 'region' => 'global'],
        'postnl'  => ['label' => 'PostNL',  'url' => 'https://jouw.postnl.nl/track-and-trace/%s',                  'region' => 'eu'],

        // North America
        'ups'     => ['label' => 'UPS',     'url' => 'https://www.ups.com/track?tracknum=%s',                      'region' => 'na'],
        'usps'    => ['label' => 'USPS',    'url' => 'https://tools.usps.com/go/TrackConfirmAction?tLabels=%s',    'region' => 'na'],
        'fedex'   => ['label' => 'FedEx',   'url' => 'https://www.fedex.com/fedextrack/?tracknumbers=%s',          'region' => 'na'],

        // Catch-all so the operator can record + label a shipment we
        // don't have a URL template for. Tracking link won't render
        // automatically; they paste a URL by hand.
        'other'   => ['label' => 'Other',   'url' => null,                                                          'region' => 'global'],
    ];

    /**
     * Get the {slug => label} map for a Filament Select component.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $out = [];
        foreach (self::CARRIERS as $slug => $row) {
            $out[$slug] = $row['label'];
        }
        return $out;
    }

    /**
     * Display label for a slug. Falls back to ucfirst($slug) for
     * legacy or operator-typed values that aren't in the registry.
     */
    public static function label(?string $slug): string
    {
        if (! $slug) {
            return '';
        }
        return self::CARRIERS[$slug]['label'] ?? ucfirst($slug);
    }

    /**
     * Build the tracking URL for a carrier + tracking number. Returns
     * null when:
     *   - the carrier slug isn't registered
     *   - the carrier has no URL template ('other', custom carriers)
     *   - the tracking number is empty
     *
     * Used at save-time (auto-fill the tracking_url column when the
     * merchant didn't paste one) AND at render-time (storefront order
     * page falls back to this when the column is empty, so legacy
     * orders without a stored URL still get a clickable link).
     */
    public static function trackingUrlFor(?string $slug, ?string $trackingNumber): ?string
    {
        if (! $slug || ! $trackingNumber) {
            return null;
        }
        $row = self::CARRIERS[$slug] ?? null;
        if (! $row || ! $row['url']) {
            return null;
        }
        // urlencode the tracking number so funky chars (rare but
        // possible) survive the round-trip into the URL.
        return sprintf($row['url'], rawurlencode(trim($trackingNumber)));
    }
}
