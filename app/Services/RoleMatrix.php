<?php

namespace App\Services;

use App\Models\User;

/**
 * Authorization contract between code and DB.
 *
 * What lives here:
 *   - SECTION constants — stable permission names that the panel checks
 *     against. Renaming one is a breaking change; adding new ones is fine.
 *   - SYSTEM_ROLES list — the 4 built-in roles that the seeder creates
 *     and protects from rename/delete via the UI. Their permissions ARE
 *     editable; only their existence + names are fixed.
 *   - Defaults — which permissions the seeder grants each system role
 *     when first creating them (and via "Reset to defaults" in the UI).
 *
 * What does NOT live here anymore:
 *   - The runtime role→permission mapping. That's in the DB (Spatie's
 *     permission_role pivot), so the operator can edit it via
 *     SuperAdmin → Roles without code changes.
 */
class RoleMatrix
{
    // ---- System role names (stable; code references these) ----------------
    public const SUPER_ADMIN = 'super_admin';
    public const BILLING_ADMIN = 'billing_admin';
    public const MARKETING_ADMIN = 'marketing_admin';
    public const SUPPORT_ADMIN = 'support_admin';

    /** Roles created + protected by the seeder. Custom roles aren't in this list. */
    public const SYSTEM_ROLES = [
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
        self::SUPER_ADMIN     => 'Full access to everything, including admin + role management.',
        self::BILLING_ADMIN   => 'Plans, orders, and tenant directory (read-only on tenants).',
        self::MARKETING_ADMIN => 'Waitlist signups and coming-soon page content.',
        self::SUPPORT_ADMIN   => 'Read-only tenants and orders for customer support.',
    ];

    // ---- Section / permission names ---------------------------------------
    // Each one corresponds to a Spatie\Permission\Models\Permission row.
    public const SEC_TENANTS = 'tenants.view';
    public const SEC_TENANTS_MANAGE = 'tenants.manage';
    public const SEC_PLANS = 'plans.view';
    public const SEC_PLANS_MANAGE = 'plans.manage';
    public const SEC_ORDERS = 'orders.view';
    public const SEC_WAITLIST = 'waitlist.view';
    public const SEC_WAITLIST_MANAGE = 'waitlist.manage';
    public const SEC_CONTENT = 'content.manage';
    public const SEC_PLATFORM_SETTINGS = 'platform_settings.manage';
    public const SEC_ADMINS = 'admins.manage';
    public const SEC_ROLES = 'roles.manage';

    /**
     * Every permission the platform knows about, with a human label + group.
     * Drives the Roles edit form (renders as a grouped CheckboxList) and the
     * seeder (which creates each permission row).
     *
     * @return array<string, array{label: string, group: string, description: string}>
     */
    public static function permissionCatalog(): array
    {
        return [
            self::SEC_TENANTS => [
                'label' => 'View tenants',
                'group' => 'Clients',
                'description' => 'See the list of merchant tenants and their details.',
            ],
            self::SEC_TENANTS_MANAGE => [
                'label' => 'Manage tenants',
                'group' => 'Clients',
                'description' => 'Create, edit, suspend, and delete tenants.',
            ],
            self::SEC_PLANS => [
                'label' => 'View plans',
                'group' => 'Billing',
                'description' => 'See subscription plans + their pricing.',
            ],
            self::SEC_PLANS_MANAGE => [
                'label' => 'Manage plans',
                'group' => 'Billing',
                'description' => 'Create + edit plans and Stripe price mapping.',
            ],
            self::SEC_ORDERS => [
                'label' => 'View orders',
                'group' => 'Billing',
                'description' => 'See platform-wide orders across all tenants.',
            ],
            self::SEC_WAITLIST => [
                'label' => 'View waitlist',
                'group' => 'Marketing',
                'description' => 'See coming-soon page signups.',
            ],
            self::SEC_WAITLIST_MANAGE => [
                'label' => 'Manage waitlist',
                'group' => 'Marketing',
                'description' => 'Delete + mark-as-notified on waitlist entries.',
            ],
            self::SEC_CONTENT => [
                'label' => 'Edit page content',
                'group' => 'Marketing',
                'description' => 'Edit coming-soon copy (and future page content).',
            ],
            self::SEC_PLATFORM_SETTINGS => [
                'label' => 'Platform settings',
                'group' => 'System',
                'description' => 'See Stripe credential status + platform diagnostics.',
            ],
            self::SEC_ADMINS => [
                'label' => 'Manage admins',
                'group' => 'System',
                'description' => 'Create + edit + delete platform admin users.',
            ],
            self::SEC_ROLES => [
                'label' => 'Manage roles',
                'group' => 'System',
                'description' => 'Create + edit roles and their permissions.',
            ],
        ];
    }

    /**
     * Default permission grant for each system role. Used by the seeder on
     * first install AND by the "Reset to defaults" action on each system
     * role's edit page.
     *
     * super_admin is intentionally absent here — it's handled separately
     * because it always gets EVERY permission, including ones added later.
     *
     * @return array<string, array<int, string>>  role => list of permission names
     */
    public static function defaultRolePermissions(): array
    {
        return [
            self::BILLING_ADMIN => [
                self::SEC_TENANTS,
                self::SEC_PLANS,
                self::SEC_PLANS_MANAGE,
                self::SEC_ORDERS,
            ],
            self::MARKETING_ADMIN => [
                self::SEC_WAITLIST,
                self::SEC_WAITLIST_MANAGE,
                self::SEC_CONTENT,
            ],
            self::SUPPORT_ADMIN => [
                self::SEC_TENANTS,
                self::SEC_ORDERS,
            ],
        ];
    }

    /**
     * Capability check. Delegates to Spatie's `can()` which walks the user's
     * roles → permissions chain. super_admin keeps god-mode via Spatie's
     * Gate::before in AuthServiceProvider.
     */
    public static function canSee(?User $user, string $section): bool
    {
        return $user?->can($section) ?? false;
    }

    /** Gate for entering the SuperAdmin panel. */
    public static function canAccessSuperPanel(User $user): bool
    {
        // Any user with at least one role gets in; what they see inside is
        // permission-driven. (Bare users with no role at all are excluded
        // so a half-configured account can't accidentally see the dashboard.)
        return $user->roles()->exists();
    }

    /** True when the role name is one of the protected built-ins. */
    public static function isSystemRole(string $name): bool
    {
        return in_array($name, self::SYSTEM_ROLES, true);
    }
}
