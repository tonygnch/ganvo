<?php

namespace App\Filament\StoreAdmin\Pages;

use App\Models\Tenant;
use App\Services\Payments\PlatformFee;
use App\Services\Payments\StripeConnectService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Stripe\Exception\ApiErrorException;

/**
 * Payments status page — Stripe Connect onboarding + ongoing management
 * lives here. The page itself just renders the current state; every
 * action button posts to {@see \App\Http\Controllers\StoreAdmin\PaymentsController}
 * which does the actual Stripe round-trips.
 *
 * Status-driven UI:
 *   - NOT_CONNECTED        → primary "Set up Ganvo Payments" CTA
 *   - ONBOARDING           → "Continue setup" + restart option
 *   - PENDING_REVIEW       → status card "Stripe is reviewing…"
 *   - ACTIVE               → green badge + dashboard link + disconnect
 *   - RESTRICTED           → red banner with disabled_reason
 */
class Payments extends Page
{
    protected string $view = 'filament.store-admin.pages.payments';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Payments';

    protected static ?string $title = 'Payments';

    protected static ?int $navigationSort = 91; // right after Billing

    public function getViewData(): array
    {
        $tenant = $this->tenant();

        // Fresh sync from Stripe on every page render gives the operator
        // up-to-date state without waiting for webhooks; cheap enough
        // for an admin page. Soft-fail: a Stripe outage shouldn't 500
        // the page — we just show stale data + a refresh button.
        if ($tenant->hasConnect()) {
            try {
                app(StripeConnectService::class)->syncFromStripe($tenant);
                $tenant->refresh();
            } catch (ApiErrorException) {
                // Carry on with stale state.
            }
        }

        return [
            'tenant' => $tenant,
            'status' => $this->status($tenant),
            'feeRate' => PlatformFee::formatRate($tenant),
            'feeBps' => PlatformFee::bpsFor($tenant),
            // Apple Pay / Google Pay / Link domain status — surfaced so
            // the operator can see at a glance whether the wallet
            // buttons will actually render at checkout.
            'walletStatus' => $this->walletStatus($tenant),
        ];
    }

    /**
     * Look up the PaymentMethodDomain entries Stripe has on file for
     * this tenant + summarize each wallet's verification status.
     * Returns an array shaped for the view:
     *   ['domain' => 'test-stripe…', 'apple_pay' => 'active', …]
     *
     * Returns null when no Connect or no domain registered yet.
     */
    private function walletStatus(\App\Models\Tenant $tenant): ?array
    {
        if (! $tenant->canAcceptRealPayments()) {
            return null;
        }
        try {
            $client = new \Stripe\StripeClient(config('cashier.secret'));
            $domains = $client->paymentMethodDomains->all(
                [],
                ['stripe_account' => $tenant->stripe_account_id],
            );
            if (empty($domains->data)) {
                return ['domain' => null];
            }
            // First entry is the one we created; if multiple, prefer
            // the one matching the storefront slug.
            $expected = $tenant->slug . '.' . config('ganvo.central_domain');
            $picked = null;
            foreach ($domains->data as $d) {
                if ($d->domain_name === $expected) { $picked = $d; break; }
            }
            $picked ??= $domains->data[0];
            return [
                'domain' => $picked->domain_name,
                'apple_pay' => $picked->apple_pay?->status,
                'google_pay' => $picked->google_pay?->status,
                'link' => $picked->link?->status,
                'apple_pay_error' => $picked->apple_pay?->status_details?->error_message,
            ];
        } catch (\Throwable) {
            // Soft-fail: don't break the whole page if Stripe is down.
            return null;
        }
    }

    private function tenant(): Tenant
    {
        return Auth::user()->tenant;
    }

    private function status(Tenant $tenant): string
    {
        if (! $tenant->hasConnect()) {
            return 'not_connected';
        }
        if ($tenant->stripe_connect_disabled_reason && ! $tenant->stripe_connect_charges_enabled) {
            return 'restricted';
        }
        if (! $tenant->stripe_connect_details_submitted) {
            return 'onboarding';
        }
        if (! $tenant->stripe_connect_charges_enabled) {
            return 'pending_review';
        }
        return 'active';
    }
}
