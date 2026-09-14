<?php

namespace App\Services\Rack;

/**
 * A priced rack: the bill of materials, and what it costs.
 *
 * Lines carry structure, not sentences — kind, sizes, SKU, quantity, price.
 * Turning a line into "6 × Рамка 210×60 cm" is presentation and belongs to
 * {@see RackPresenter}, so the same quote reads correctly in either language
 * and freezes onto an order in the language it was ordered in.
 *
 * Money: unit prices and the subtotal are EX-VAT, matching the price book.
 * VAT is applied once, to the subtotal, so the customer's total never drifts
 * from the sum of the parts by a rounding cent per line.
 */
final class RackQuote
{
    public function __construct(
        public readonly RackConfig $config,
        /** @var list<array{kind:string,sku:?string,height_cm:?int,depth_cm:?int,width_cm:?int,quantity:int,unit_price_cents:int,subtotal_cents:int}> */
        public readonly array $lines,
        public readonly int $subtotalCents,
        public readonly int $vatRateBp,
        public readonly int $vatCents,
        public readonly int $totalCents,
        public readonly string $currency,
    ) {}

    public function totalLengthCm(): int
    {
        return $this->config->totalLengthCm();
    }

    /** Total piece count — what the lorry actually carries. */
    public function pieceCount(): int
    {
        return array_sum(array_column($this->lines, 'quantity'));
    }

    public function toArray(): array
    {
        return [
            'config' => $this->config->toArray(),
            'lines' => $this->lines,
            'subtotal_cents' => $this->subtotalCents,
            'vat_rate_bp' => $this->vatRateBp,
            'vat_cents' => $this->vatCents,
            'total_cents' => $this->totalCents,
            'currency' => $this->currency,
            'total_length_cm' => $this->totalLengthCm(),
            'sections' => $this->config->sectionCount(),
            'type' => $this->config->type,
            'frames' => $this->config->frameCount(),
            'shelves' => $this->config->shelfCount(),
            'boards' => $this->config->boardCount(),
            'braced_sections' => $this->config->bracedSectionIndexes(),
        ];
    }
}
