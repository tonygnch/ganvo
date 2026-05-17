<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Store extends Model
{
    public const CHECKOUT_GUEST = 'guest';
    public const CHECKOUT_ACCOUNT = 'account';
    public const CHECKOUT_BOTH = 'both';

    public const CHECKOUT_MODES = [
        self::CHECKOUT_GUEST => 'Guest checkout only',
        self::CHECKOUT_ACCOUNT => 'Account required',
        self::CHECKOUT_BOTH => 'Guest or account (recommended)',
    ];

    protected $fillable = [
        'tenant_id',
        'theme',
        'logo_path',
        'primary_color',
        'secondary_color',
        'font_family',
        'custom_domain',
        'custom_domain_verification_token',
        'custom_domain_verified_at',
        'theme_settings',
        'is_live',
        'checkout_mode',
        'allow_registration',
    ];

    protected $casts = [
        'theme_settings' => 'array',
        'is_live' => 'boolean',
        'custom_domain_verified_at' => 'datetime',
        'allow_registration' => 'boolean',
    ];

    public function allowsGuestCheckout(): bool
    {
        return in_array($this->checkout_mode, [self::CHECKOUT_GUEST, self::CHECKOUT_BOTH], true);
    }

    public function requiresAccountCheckout(): bool
    {
        return $this->checkout_mode === self::CHECKOUT_ACCOUNT;
    }

    public function showsAccountUi(): bool
    {
        return in_array($this->checkout_mode, [self::CHECKOUT_ACCOUNT, self::CHECKOUT_BOTH], true);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function hasVerifiedCustomDomain(): bool
    {
        return filled($this->custom_domain) && $this->custom_domain_verified_at !== null;
    }

    public function ensureVerificationToken(): string
    {
        if (! $this->custom_domain_verification_token) {
            $this->custom_domain_verification_token = 'ganvo-verification=' . Str::random(24);
            $this->save();
        }
        return $this->custom_domain_verification_token;
    }
}
