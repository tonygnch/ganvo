<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * An enquiry sent from a storefront's contact page. Tenant-scoped: each
 * merchant only ever sees their own. Persisted before the notification
 * email is attempted, so the merchant keeps the enquiry even when mail
 * delivery fails.
 */
class StoreMessage extends Model
{
    public const STATUS_NEW = 'new';
    public const STATUS_READ = 'read';
    public const STATUS_REPLIED = 'replied';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_NEW,
        self::STATUS_READ,
        self::STATUS_REPLIED,
        self::STATUS_ARCHIVED,
    ];

    /** Curated subject options offered on the storefront form. */
    public const SUBJECTS = ['general', 'quote', 'order', 'delivery', 'returns'];

    protected $fillable = [
        'tenant_id',
        'customer_id',
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'status',
        'locale',
        'ip',
        'user_agent',
        'read_at',
    ];

    protected $casts = [
        'read_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function markRead(): void
    {
        if ($this->status === self::STATUS_NEW) {
            $this->update(['status' => self::STATUS_READ, 'read_at' => now()]);
        }
    }
}
