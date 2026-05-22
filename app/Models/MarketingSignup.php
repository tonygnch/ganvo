<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Coming-soon page email signup. Captured via the public splash form;
 * surfaced to the platform owner via the SuperAdmin "Waitlist" page so
 * they can export the addresses when it's time to send the launch email.
 */
class MarketingSignup extends Model
{
    protected $fillable = [
        'email',
        'locale',
        'ip',
        'user_agent',
        'notified_at',
    ];

    protected $casts = [
        'notified_at' => 'datetime',
    ];
}
