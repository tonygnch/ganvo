<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use SoftDeletes;

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_SUSPENDED = 'suspended';

    public const STATUSES = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_SUSPENDED => 'Suspended',
    ];

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
        'business_type',
        'contact_email',
        'contact_phone',
        'subscription_plan',
        'billing_period',
        'stripe_account_id',
        'stripe_customer_id',
        'status',
        'onboarding_progress',
        'onboarding_step',
        'onboarded_at',
    ];

    protected $casts = [
        'onboarding_progress' => 'array',
        'onboarded_at' => 'datetime',
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
}
