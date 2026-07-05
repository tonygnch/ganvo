<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Http;

/**
 * A custom client website hosted OUTSIDE the platform. Ganvo is the hub:
 * it holds the client relationship (tenant), billing, and this registry
 * record — the site's code and hosting stay in their own repo/server.
 */
class Website extends Model
{
    protected $fillable = [
        'tenant_id',
        'url',
        'repo_url',
        'stack',
        'notes',
        'last_status',
        'last_checked_at',
    ];

    protected $casts = [
        'last_checked_at' => 'datetime',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /** Ping the live URL and record up/down. Cheap HEAD-then-GET check. */
    public function checkNow(): string
    {
        $status = 'unknown';
        if ($this->url) {
            try {
                $res = Http::timeout(8)->head($this->url);
                if ($res->status() === 405) { // some hosts reject HEAD
                    $res = Http::timeout(8)->get($this->url);
                }
                $status = $res->successful() || $res->redirect() ? 'up' : 'down';
            } catch (\Throwable) {
                $status = 'down';
            }
        }
        $this->update(['last_status' => $status, 'last_checked_at' => now()]);

        return $status;
    }
}
