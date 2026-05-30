<?php

namespace App\Services\Payments;

use App\Models\Tenant;

/**
 * Resolves the platform fee (Ganvo's cut) charged on Stripe Connect
 * transactions. Lives as its own service so the cascade logic is
 * testable in isolation + the checkout controller never needs to know
 * whether the rate came from a tenant override or the plan default.
 *
 * Fee resolution cascade:
 *   1. Tenant.platform_fee_bps  (operator/support override)
 *   2. Plan.platform_fee_bps    (plan default)
 *   3. 0                        (free — the safe initial default)
 *
 * Computed via Tenant::effectiveFeeBps(); this class layers convenience
 * methods that translate basis points to actual cents for a given
 * order total.
 */
class PlatformFee
{
    /**
     * Effective fee rate in basis points for the given tenant.
     * 100 bps = 1%. Capped at 5000 (50%) defensively.
     */
    public static function bpsFor(Tenant $tenant): int
    {
        return $tenant->effectiveFeeBps();
    }

    /**
     * Compute the platform fee in cents for a given order total +
     * tenant. Returns 0 when no fee is configured or the total is 0.
     *
     * Rounds down (floor) so we never accidentally charge a merchant
     * more than the configured rate would suggest — a fraction of a
     * cent in our favor each transaction adds up; a fraction against
     * us each transaction is fine.
     */
    public static function compute(Tenant $tenant, int $totalCents): int
    {
        if ($totalCents <= 0) {
            return 0;
        }
        $bps = self::bpsFor($tenant);
        if ($bps <= 0) {
            return 0;
        }
        // basis points / 10_000 = decimal rate; intdiv floors cleanly.
        return intdiv($totalCents * $bps, 10000);
    }

    /**
     * Human-readable rate ("2.50%" or "—" for no fee). Used by admin
     * views; not called inside the hot path.
     */
    public static function formatRate(Tenant $tenant): string
    {
        $bps = self::bpsFor($tenant);
        if ($bps <= 0) {
            return '—';
        }
        return rtrim(rtrim(number_format($bps / 100, 2, '.', ''), '0'), '.') . '%';
    }
}
