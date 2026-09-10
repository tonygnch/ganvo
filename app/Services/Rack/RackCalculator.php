<?php

namespace App\Services\Rack;

use App\Models\RackPart;

/**
 * Turns a configuration into a bill of materials and a price.
 *
 * Pure: no database, no session, no request, no locale. Everything it needs
 * arrives as arguments, which is what makes it the one piece of this feature
 * that can be pinned by tests against the client's own worked example.
 */
final class RackCalculator
{
    /**
     * @param  array  $limits  Store::rackConfigurator() — for the shelf size trims
     *
     * @throws RackException when the price book cannot price a needed part
     */
    public function quote(
        RackConfig $config,
        RackPriceBook $prices,
        array $limits,
        string $currency = 'EUR',
    ): RackQuote {
        $lines = [];

        // Frames — one per section plus one, because uprights are shared.
        $frame = $prices->frame($config->heightCm, $config->depthCm);
        $lines[] = $this->line($frame, $config->frameCount());

        // Shelves — grouped by size, because a run may mix bay widths and the
        // yard picks 20 identical boards off the stack, not 20 separate ones.
        $realDepth = max(1, $config->depthCm - $limits['shelf_depth_trim_cm']);
        $byWidth = [];
        foreach ($config->segments as $nominalWidth) {
            $realWidth = max(1, $nominalWidth - $limits['shelf_width_trim_cm']);
            $byWidth[$realWidth] = ($byWidth[$realWidth] ?? 0) + $config->levels;
        }
        ksort($byWidth);
        foreach ($byWidth as $realWidth => $quantity) {
            $lines[] = $this->line($prices->shelf($realWidth, $realDepth), $quantity);
        }

        // Fasteners. An extension pin is double-sided — see RackConfig.
        $lines[] = $this->line($prices->flat(RackPart::KIND_END_PIN), $config->endPinCount());

        if ($config->extensionPinCount() > 0) {
            $lines[] = $this->line($prices->flat(RackPart::KIND_EXTENSION_PIN), $config->extensionPinCount());
        }

        $lines[] = $this->line($prices->flat(RackPart::KIND_CROSS_BRACE), $config->crossBraceCount());

        $subtotal = array_sum(array_column($lines, 'subtotal_cents'));
        $vatRateBp = max(0, (int) ($limits['vat_rate_bp'] ?? 0));
        // Once, on the subtotal — not per line, which would drift by a cent
        // per row against the figure the customer was shown.
        $vat = (int) round($subtotal * $vatRateBp / 10000);

        return new RackQuote(
            config: $config,
            lines: $lines,
            subtotalCents: $subtotal,
            vatRateBp: $vatRateBp,
            vatCents: $vat,
            totalCents: $subtotal + $vat,
            currency: $currency,
        );
    }

    private function line(RackPart $part, int $quantity): array
    {
        return [
            'kind' => $part->kind,
            'sku' => $part->sku,
            'height_cm' => $part->height_cm,
            'depth_cm' => $part->depth_cm,
            'width_cm' => $part->width_cm,
            'quantity' => $quantity,
            'unit_price_cents' => (int) $part->price_cents,
            'subtotal_cents' => (int) $part->price_cents * $quantity,
        ];
    }
}
