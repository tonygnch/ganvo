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
        'customer_phone',
        'marketing_opt_in',
        'shipping_method_label',
        'shipping_cents',
        'total_cents',
        'currency',
        'display_currency',
        'display_total_cents',
        // Snapshot of any applied discount — kept on the order so the
        // receipt + admin views read accurately even if the discount
        // is later renamed / deleted.
        'discount_id',
        'discount_code',
        'discount_name',
        'discount_amount_cents',
        'status',
        // 'stub' = legacy/demo (auto-paid) / 'stripe' = real Connect
        // PaymentIntent. Lets the system run both modes side-by-side
        // during rollout + indefinitely for tenants without Connect.
        'payment_method',
        'carrier',
        'tracking_number',
        'tracking_url',
        'notes',
        'stripe_payment_intent_id',
        'stripe_charge_id',
        'stripe_application_fee_id',
        // Snapshot of the platform fee collected at order time.
        // Stays accurate even if the rate changes later.
        'platform_fee_cents',
        // Cumulative refund amount in cents. 0 = no refund, total_cents
        // = fully refunded (status flips to 'refunded'), between =
        // partially refunded (status stays paid/shipped).
        'refund_amount_cents',
        'shipping_address',
        'paid_at',
        'shipped_at',
        'cancelled_at',
        'refunded_at',
    ];

    protected $casts = [
        'shipping_address' => 'array',
        'marketing_opt_in' => 'boolean',
        'shipping_cents' => 'integer',
        'platform_fee_cents' => 'integer',
        'refund_amount_cents' => 'integer',
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
        // Can refund as long as the order was actually paid AND there's
        // still some amount left un-refunded. Partial refunds chip away
        // at the remaining balance until it hits 0 → status flips.
        return in_array($this->status, [self::STATUS_PAID, self::STATUS_SHIPPED], true)
            && $this->refundableAmountCents() > 0;
    }

    public function isCancellable(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PAID], true);
    }

    /** Whether some — but not all — of the order has been refunded. */
    public function isPartiallyRefunded(): bool
    {
        return $this->refund_amount_cents > 0
            && $this->refund_amount_cents < $this->total_cents;
    }

    /** Cents still available to refund (total minus what's already gone back). */
    public function refundableAmountCents(): int
    {
        return max(0, (int) $this->total_cents - (int) $this->refund_amount_cents);
    }

    /** Was this order paid through Stripe Connect (vs the legacy stub path)? */
    public function isStripePayment(): bool
    {
        return $this->payment_method === 'stripe' && filled($this->stripe_charge_id);
    }
}
