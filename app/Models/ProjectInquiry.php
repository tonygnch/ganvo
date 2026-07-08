<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * A "Start a project" lead from the marketing homepage. Captured via the
 * public inquiry form; surfaced to the studio owner in SuperAdmin →
 * Marketing → Inquiries where it moves through a small status pipeline.
 */
class ProjectInquiry extends Model
{
    public const STATUS_NEW = 'new';
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_CONTACTED = 'contacted';
    public const STATUS_ARCHIVED = 'archived';

    public const STATUSES = [
        self::STATUS_NEW,
        self::STATUS_REVIEWED,
        self::STATUS_CONTACTED,
        self::STATUS_ARCHIVED,
    ];

    protected $fillable = [
        'name',
        'email',
        'company',
        'project_type',
        'budget',
        'message',
        'status',
        'locale',
        'ip',
        'user_agent',
    ];
}
