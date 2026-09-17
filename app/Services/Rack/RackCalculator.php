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
     * @param  array  $limits  Store::rackConfigurator() — for the board size trims
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
        // Every price comes from this rack type's own tables.
        $frame = $prices->frame($config->heightCm, $config->depthCm, $config->type);
        $lines[] = $this->line($frame, $config->frameCount());

        // Boards — shelves, or what the model uses in their place (wine trays,
        // an office rack's desk plate). By kind, then grouped by size, because
        // a run may mix bay widths and the yard picks 20 identical boards off
        // the stack, not 20 separate ones.
        $realDepth = max(1, $config->depthCm - $limits['shelf_depth_trim_cm']);
        $realDeskDepth = max(1, (int) ($config->deskDepthCm ?? $config->depthCm) - $limits['shelf_depth_trim_cm']);
        // Section by section, because an office rack's desk is on the sections
        // the customer chose and not on the others.
        $boards = [];
        foreach ($config->segments as $index => $nominalWidth) {
            $realWidth = max(1, $nominalWidth - $limits['shelf_width_trim_cm']);
            foreach ($config->boardsForSection($index) as $kind => $count) {
                if ($count > 0) {
                    $boards[$kind][$realWidth] = ($boards[$kind][$realWidth] ?? 0) + $count;
                }
            }
        }

        foreach ($boards as $kind => $byWidth) {
            ksort($byWidth);
            foreach ($byWidth as $realWidth => $quantity) {
                // The office desk IS a shelf, only deeper: priced from the
                // shelf table at the depth the customer chose, and listed as
                // the desk plate it is.
                $part = $kind === RackPart::KIND_DESK_TOP
                    ? $prices->shelf($realWidth, $realDeskDepth, $config->type)
                    : $prices->board($kind, $realWidth, $realDepth, $config->type);
                $lines[] = $this->line($part, $quantity, $kind);
            }
        }

        // Fasteners. An extension pin is double-sided — see RackConfig.
        $lines[] = $this->line($prices->flat(RackPart::KIND_END_PIN), $config->endPinCount());

        if ($config->extensionPinCount() > 0) {
            $lines[] = $this->line($prices->flat(RackPart::KIND_EXTENSION_PIN), $config->extensionPinCount());
        }

        $lines[] = $this->line($prices->flat(RackPart::KIND_CROSS_BRACE), $config->crossBraceCount());

        /*
         | THE PRICE BOOK IS VAT-INCLUSIVE.
         |
         | The merchant types what the customer pays for a part, VAT and all. So
         | the lines add up to the total, and the VAT is the share of it the rate
         | accounts for (20/120 at 20%) — worked out once, on the total, not per
         | line, which would drift by a cent a row against the figure shown. What
         | is left is the price without VAT.
         */
        $total = array_sum(array_column($lines, 'subtotal_cents'));
        $vatRateBp = max(0, (int) ($limits['vat_rate_bp'] ?? 0));
        $vat = (int) round($total * $vatRateBp / (10000 + $vatRateBp));
        $subtotal = $total - $vat;

        return new RackQuote(
            config: $config,
            lines: $lines,
            subtotalCents: $subtotal,
            vatRateBp: $vatRateBp,
            vatCents: $vat,
            totalCents: $total,
            currency: $currency,
        );
    }

    /** @param  ?string  $kind  what the line is, when a part is priced as another (a desk as a shelf) */
    private function line(RackPart $part, int $quantity, ?string $kind = null): array
    {
        return [
            'kind' => $kind ?? $part->kind,
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
