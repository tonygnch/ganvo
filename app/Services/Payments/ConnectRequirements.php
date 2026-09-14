<?php

namespace App\Services\Payments;

use App\Models\Tenant;
use Stripe\StripeObject;

/**
 * What Stripe is waiting for on a restricted Connect account, in words a shop
 * owner can act on.
 *
 * Stripe reports a restriction as a code ('requirements.past_due') and the
 * fields behind it as API paths ('individual.dob.day', 'external_account').
 * The Payments page used to print the code as-is and offer only the Stripe
 * dashboard, Refresh and Disconnect, so an owner whose details failed
 * verification had no button that led back to the form.
 * This turns both into readable text and decides whether "Continue setup"
 * (a fresh account_onboarding link, which Stripe documents as the form for
 * outstanding requirements) is the way out.
 */
class ConnectRequirements
{
    /** Restriction codes an owner clears by supplying information. */
    private const NEEDS_INFORMATION = [
        'requirements.past_due',
        'action_required.requested_capabilities',
    ];

    /** Top-level requirement paths → admin.payments.requirement.* group. */
    private const GROUPS = [
        'external_account' => 'bank_account',
        'tos_acceptance' => 'terms',
        'business_profile' => 'business_profile',
        'settings' => 'business_profile',
        'business_type' => 'business_type',
        'company' => 'company',
        'owners' => 'people',
        'directors' => 'people',
        'executives' => 'people',
    ];

    /** Fields under individual.* / representative.* / person_xxx.* → group. */
    private const PERSON_FIELDS = [
        'first_name' => 'name',
        'last_name' => 'name',
        'full_name_aliases' => 'name',
        'maiden_name' => 'name',
        'dob' => 'dob',
        'address' => 'address',
        'registered_address' => 'address',
        'phone' => 'phone',
        'email' => 'email',
        'id_number' => 'id_number',
        'id_number_secondary' => 'id_number',
        'ssn_last_4' => 'id_number',
        'verification' => 'id_document',
        'nationality' => 'nationality',
        'relationship' => 'people',
    ];

    /**
     * The part of Stripe's requirements hash worth keeping on the tenant.
     *
     * @return array{due: list<string>, failed: list<string>}|null
     */
    public static function snapshot(?StripeObject $requirements): ?array
    {
        if ($requirements === null) {
            return null;
        }

        $r = $requirements->toArray();

        return [
            'due' => array_values(array_unique([...($r['past_due'] ?? []), ...($r['currently_due'] ?? [])])),
            'failed' => array_values(array_unique(array_filter(
                array_map(fn ($error) => $error['requirement'] ?? null, $r['errors'] ?? [])
            ))),
        ];
    }

    /**
     * Everything the restricted card on the Payments page shows.
     *
     * @return array{reason: string, missing: list<string>, failed: bool, can_continue: bool}
     */
    public static function forTenant(Tenant $tenant): array
    {
        $code = $tenant->stripe_connect_disabled_reason;
        $stored = (array) ($tenant->stripe_connect_requirements ?? []);
        $due = array_values((array) ($stored['due'] ?? []));

        // A rejected or platform-paused account cannot be fixed by filling in a
        // form, whatever Stripe still lists as due.
        $closed = $code !== null && (str_starts_with($code, 'rejected.') || $code === 'platform_paused');

        return [
            'reason' => self::reason($code),
            'missing' => self::labels($due),
            'failed' => ! empty($stored['failed']),
            'can_continue' => ! $closed && ($due !== [] || in_array($code, self::NEEDS_INFORMATION, true)),
        ];
    }

    public static function reason(?string $code): string
    {
        if ($code === null || $code === '') {
            return __('admin.payments.text.reason_fallback');
        }

        $key = str_starts_with($code, 'rejected.') ? 'rejected' : str_replace('.', '_', $code);
        $line = __("admin.payments.reason.{$key}");

        return $line === "admin.payments.reason.{$key}" ? __('admin.payments.text.reason_fallback') : $line;
    }

    /**
     * Requirement paths as readable, de-duplicated labels in first-seen order:
     * nine identity fields collapse into "name, date of birth, address, phone".
     *
     * @param  list<string>  $fields
     * @return list<string>
     */
    public static function labels(array $fields): array
    {
        $groups = array_values(array_unique(array_map(self::groupOf(...), $fields)));

        return array_map(fn (string $group) => __("admin.payments.requirement.{$group}"), $groups);
    }

    public static function groupOf(string $field): string
    {
        $parts = explode('.', $field);
        $head = $parts[0];

        if ($head === 'individual' || $head === 'representative' || str_starts_with($head, 'person_')) {
            return self::PERSON_FIELDS[$parts[1] ?? ''] ?? 'other';
        }

        return self::GROUPS[$head] ?? 'other';
    }
}
