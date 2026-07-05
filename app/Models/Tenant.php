<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Cashier\Billable;

class Tenant extends Model
{
    use Billable, SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';

    public const STATUSES = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_SUSPENDED => 'Suspended',
    ];

    /**
     * What kind of client this tenant is: a self-serve storefront (the
     * classic flow) or a hand-built custom website hosted outside the
     * platform, managed here as a hub (registry + billing + status).
     */
    public const TYPE_STORE = 'store';
    public const TYPE_WEBSITE = 'website';

    public const TYPES = [
        self::TYPE_STORE => 'Storefront',
        self::TYPE_WEBSITE => 'Custom website',
    ];

    public function isWebsite(): bool
    {
        return $this->type === self::TYPE_WEBSITE;
    }

    public const PLAN_STARTER = 'starter';
    public const PLAN_PRO = 'pro';
    public const PLAN_BUSINESS = 'business';

    public const PLANS = [
        self::PLAN_STARTER => 'Starter',
        self::PLAN_PRO => 'Pro',
        self::PLAN_BUSINESS => 'Business',
    ];

    protected $fillable = [
        'name',
        'slug',
        'type',
        'business_type',
        'contact_email',
        'contact_phone',
        'subscription_plan',
        'billing_period',
        'stripe_account_id',                    // Connect account id (acct_…)
        'stripe_connect_account_type',          // 'express' | 'standard' | null
        'stripe_connect_charges_enabled',
        'stripe_connect_payouts_enabled',
        'stripe_connect_details_submitted',
        'stripe_connect_disabled_reason',
        'platform_fee_bps',
        'stripe_id',                            // Cashier subscription customer id
        'pm_type',
        'pm_last_four',
        'trial_ends_at',
        'status',
        'onboarding_progress',
        'onboarding_step',
        'onboarded_at',
    ];

    protected $casts = [
        'onboarding_progress' => 'array',
        'onboarded_at' => 'datetime',
        'trial_ends_at' => 'datetime',
        'stripe_connect_charges_enabled' => 'boolean',
        'stripe_connect_payouts_enabled' => 'boolean',
        'stripe_connect_details_submitted' => 'boolean',
        'platform_fee_bps' => 'integer',
    ];

    // The wizard step sequence — order matters; advance() walks this list.
    public const ONBOARDING_STEPS = [
        'business',
        'plan',
        'theme',
        'customize',
        'products',
        'launch',
        'done',
    ];

    public function isOnboarded(): bool
    {
        return $this->onboarding_step === 'done';
    }

    /* -----------------------------------------------------------------
     | Stripe Connect helpers
     |
     | `stripe_account_id` is the Connect account id (acct_…) when set.
     | `canAcceptRealPayments()` is the single gate the storefront
     | checks before swapping out of stub mode. Anything below is
     | derived state from columns synced by the account.updated webhook
     | + explicit syncFromStripe() pulls.
     ------------------------------------------------------------------*/

    /** True when a Connect account exists (regardless of status). */
    public function hasConnect(): bool
    {
        return ! empty($this->stripe_account_id);
    }

    /**
     * The one bit the checkout flow checks: connected, charges_enabled,
     * and details fully submitted. Stripe can revoke charges_enabled
     * mid-flight (compliance issues, restricted countries) — when that
     * happens, the storefront falls back to stub mode automatically.
     */
    public function canAcceptRealPayments(): bool
    {
        return $this->hasConnect()
            && (bool) $this->stripe_connect_charges_enabled
            && (bool) $this->stripe_connect_details_submitted;
    }

    /**
     * Effective platform fee in basis points (1% = 100). Resolution:
     *   1. Tenant-level override (operator-set or support-set).
     *   2. Plan-level default.
     *   3. 0 (no fee) — the safe default while we tune pricing.
     *
     * Clamped to [0, 5000] (50%) so a mis-set value can't accidentally
     * eat every transaction.
     */
    public function effectiveFeeBps(): int
    {
        $bps = $this->platform_fee_bps;
        if ($bps === null) {
            $plan = $this->subscription_plan
                ? \App\Models\Plan::where('slug', $this->subscription_plan)->first()
                : null;
            $bps = (int) ($plan?->platform_fee_bps ?? 0);
        }
        return max(0, min(5000, (int) $bps));
    }

    /** Move the tenant to the next step in the wizard. No-op when already done. */
    public function advanceOnboarding(): void
    {
        $idx = array_search($this->onboarding_step, self::ONBOARDING_STEPS, true);
        if ($idx === false || $idx >= count(self::ONBOARDING_STEPS) - 1) {
            return;
        }
        $this->onboarding_step = self::ONBOARDING_STEPS[$idx + 1];
        $this->save();
    }

    public function store(): HasOne
    {
        return $this->hasOne(Store::class);
    }

    public function website(): HasOne
    {
        return $this->hasOne(Website::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function planLabel(): string
    {
        return self::PLANS[$this->subscription_plan] ?? $this->subscription_plan;
    }

    /**
     * Resolve the configured Plan model for this tenant by slug. Returns null
     * if the slug doesn't match anything (e.g. a plan was renamed/deleted in
     * the SA panel — the tenant's stored slug is then stale).
     */
    public function plan(): ?Plan
    {
        if (! $this->subscription_plan) {
            return null;
        }
        return Plan::where('slug', $this->subscription_plan)->first();
    }

    /**
     * The Stripe Price ID for this tenant's currently selected plan + period,
     * or null if the plan is free / not configured in Stripe yet.
     */
    public function activeStripePriceId(): ?string
    {
        $plan = $this->plan();
        if (! $plan || $plan->isFree()) {
            return null;
        }
        return $plan->stripePriceFor($this->billing_period ?: Plan::PERIOD_MONTHLY);
    }

    /**
     * Cashier's primary subscription identifier. We use 'default' as the name
     * across the platform so all billing helpers can stay on one canonical
     * subscription per tenant.
     */
    public const SUBSCRIPTION_NAME = 'default';

    public function platformSubscription()
    {
        return $this->subscription(self::SUBSCRIPTION_NAME);
    }

    public function platformSubscribed(): bool
    {
        return $this->subscribed(self::SUBSCRIPTION_NAME);
    }
}
