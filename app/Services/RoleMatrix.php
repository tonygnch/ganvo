<?php

namespace App\Services;

use App\Models\User;

/**
 * Single source of truth for which platform sub-role can see which section
 * of the SuperAdmin panel.
 *
 * We deliberately use fixed sub-roles (not per-permission toggling) so the
 * authorization model stays simple: pick a role for the new admin, and
 * everything they can/can't do follows. If a role's scope needs to change,
 * edit this file — no DB updates, no permission tables to keep in sync.
 *
 * Role design:
 *   - super_admin     — full access; can manage everything including other admins.
 *   - billing_admin   — works the revenue side: plans, orders, tenant directory.
 *   - marketing_admin — runs the public marketing: waitlist + coming-soon copy.
 *   - support_admin   — read-only window into tenants + orders for support work.
 */
class RoleMatrix
{
    public const SUPER_ADMIN = 'super_admin';
    public const BILLING_ADMIN = 'billing_admin';
    public const MARKETING_ADMIN = 'marketing_admin';
    public const SUPPORT_ADMIN = 'support_admin';

    /** Roles that grant access to the SuperAdmin panel at all. */
    public const PLATFORM_ROLES = [
        self::SUPER_ADMIN,
        self::BILLING_ADMIN,
        self::MARKETING_ADMIN,
        self::SUPPORT_ADMIN,
    ];

    public const ROLE_LABELS = [
        self::SUPER_ADMIN     => 'Super admin',
        self::BILLING_ADMIN   => 'Billing admin',
        self::MARKETING_ADMIN => 'Marketing admin',
        self::SUPPORT_ADMIN   => 'Support admin',
    ];

    public const ROLE_DESCRIPTIONS = [
        self::SUPER_ADMIN     => 'Full access to everything, including admin management.',
        self::BILLING_ADMIN   => 'Plans, orders, and tenant directory (read-only on tenants).',
        self::MARKETING_ADMIN => 'Waitlist signups and coming-soon page content.',
        self::SUPPORT_ADMIN   => 'Read-only tenants and orders for customer support.',
    ];

    /**
     * Section IDs the panel uses for capability checks. The strings here
     * are referenced by each Resource/Page's canViewAny() etc.
     *
     * Granularity: per-section, with optional .manage suffix for write
     * actions on sections where read and write split (e.g. support_admin
     * gets `tenants` but NOT `tenants.manage`).
     */
    public const SEC_TENANTS = 'tenants';
    public const SEC_TENANTS_MANAGE = 'tenants.manage';
    public const SEC_PLANS = 'plans';
    public const SEC_PLANS_MANAGE = 'plans.manage';
    public const SEC_ORDERS = 'orders';
    public const SEC_WAITLIST = 'waitlist';
    public const SEC_WAITLIST_MANAGE = 'waitlist.manage';
    public const SEC_CONTENT = 'content';
    public const SEC_PLATFORM_SETTINGS = 'platform_settings';
    public const SEC_ADMINS = 'admins';

    /**
     * Does the given user have access to the given section?
     *
     * super_admin always returns true (god mode). For everyone else, the
     * match expression below is the source of truth.
     */
    public static function canSee(?User $user, string $section): bool
    {
        if (! $user) {
            return false;
        }
        if ($user->hasRole(self::SUPER_ADMIN)) {
            return true;
        }

        return match ($section) {
            // Tenants: billing + support see the list; only super can edit.
            self::SEC_TENANTS         => $user->hasAnyRole([self::BILLING_ADMIN, self::SUPPORT_ADMIN]),
            self::SEC_TENANTS_MANAGE  => false,

            // Plans: billing owns them.
            self::SEC_PLANS,
            self::SEC_PLANS_MANAGE    => $user->hasRole(self::BILLING_ADMIN),

            // Orders: billing + support both see; neither edits (only super).
            self::SEC_ORDERS          => $user->hasAnyRole([self::BILLING_ADMIN, self::SUPPORT_ADMIN]),

            // Marketing surfaces.
            self::SEC_WAITLIST,
            self::SEC_WAITLIST_MANAGE,
            self::SEC_CONTENT         => $user->hasRole(self::MARKETING_ADMIN),

            // Sensitive: only super.
            self::SEC_PLATFORM_SETTINGS,
            self::SEC_ADMINS          => false,

            default => false,
        };
    }

    /** Gate for entering the panel at all. */
    public static function canAccessSuperPanel(User $user): bool
    {
        return $user->hasAnyRole(self::PLATFORM_ROLES);
    }
}
