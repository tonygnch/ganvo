<?php

namespace App\Services;

use App\Models\Discount;
use App\Models\Tenant;

/**
 * Resolves which discount (if any) currently applies to a cart.
 *
 * Lookup rules:
 *   - If the customer has manually entered a code, prefer that one
 *     (assuming it's valid + the cart meets its conditions).
 *   - Otherwise scan auto-discounts and pick the one that yields the
 *     largest amount-off for the current subtotal + shipping.
 *
 * We do not stack discounts in v1 — exactly zero or one applies at a
 * time. Multiple-discount support adds enough surface area
 * (max-stack rules, exclusivity flags, order of operations) to merit
 * its own future slice.
 */
class DiscountEngine
{
    public function __construct(private readonly Tenant $tenant)
    {
    }

    public static function forCurrent(): self
    {
        return new self(app('current_tenant'));
    }

    /**
     * Look up a discount by code for the current tenant. Returns null
     * when the code doesn't exist or the discount is currently invalid
     * (inactive, out of window, capped out). Codes are matched
     * case-insensitively.
     */
    public function findByCode(string $code): ?Discount
    {
        $code = strtoupper(trim($code));
        if ($code === '') {
            return null;
        }
        return Discount::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('code', $code)
            ->first();
    }

    /**
     * Resolve the discount to apply, given a manually-entered code (if
     * any) and the current cart totals. Returns null when nothing
     * applies. Caller is responsible for computing amount-off via
     * Discount::amountOff().
     */
    public function resolve(?string $code, int $subtotalCents, int $shippingCents): ?Discount
    {
        // 1) Manual code wins outright when valid + the cart meets its
        //    minimum. (Don't fall through to auto when the customer
        //    typed something — that'd silently swap to a different
        //    discount than they expected.)
        if ($code) {
            $manual = $this->findByCode($code);
            if ($manual && $manual->isValid() && $manual->meetsMinimum($subtotalCents)) {
                return $manual;
            }
            // Code typed but no longer valid — fall through to auto
            // so the cart still gets the best available promo. The
            // session layer is responsible for surfacing the "your
            // code is no longer valid" flash if it cares.
        }

        // 2) Auto-discounts: pick the one with the biggest amount-off.
        $autos = Discount::query()
            ->where('tenant_id', $this->tenant->id)
            ->where('is_auto', true)
            ->where('is_active', true)
            ->get();

        $best = null;
        $bestAmount = 0;
        foreach ($autos as $auto) {
            $amount = $auto->amountOff($subtotalCents, $shippingCents);
            if ($amount > $bestAmount) {
                $best = $auto;
                $bestAmount = $amount;
            }
        }
        return $best;
    }
}
