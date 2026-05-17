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
        'stripe_account_id',
        'stripe_customer_id',
        'status',
        'onboarding_progress',
        'onboarded_at',
    ];

    protected $casts = [
        'onboarding_progress' => 'array',
        'onboarded_at' => 'datetime',
    ];

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
}
