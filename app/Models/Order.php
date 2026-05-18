<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PAID = 'paid';
    public const STATUS_SHIPPED = 'shipped';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_PAID => 'Paid',
        self::STATUS_SHIPPED => 'Shipped',
        self::STATUS_REFUNDED => 'Refunded',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'order_number',
        'customer_email',
        'customer_name',
        'total_cents',
        'currency',
        'display_currency',
        'display_total_cents',
        'status',
        'carrier',
        'tracking_number',
        'tracking_url',
        'notes',
        'stripe_payment_intent_id',
        'shipping_address',
        'paid_at',
        'shipped_at',
        'cancelled_at',
        'refunded_at',
    ];

    protected $casts = [
        'shipping_address' => 'array',
        'paid_at' => 'datetime',
        'shipped_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function isShippable(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

    public function isRefundable(): bool
    {
        return in_array($this->status, [self::STATUS_PAID, self::STATUS_SHIPPED], true);
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PAID], true);
    }
}
