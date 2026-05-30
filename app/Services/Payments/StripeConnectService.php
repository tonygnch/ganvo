<?php

namespace App\Services\Payments;

use App\Models\Tenant;
use App\Models\Order;
use Stripe\Account;
use Stripe\AccountLink;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent;
use Stripe\StripeClient;

/**
 * Thin facade over the subset of the Stripe Connect API the platform
 * touches. Lives behind one service so the rest of the app never sees
 * a raw Stripe call, and so the Express ↔ Standard distinction is a
 * one-method-add later.
 *
 * Express flow (this phase):
 *   1. createExpressAccount($tenant) — creates an acct_ on Stripe,
 *      stores the id on the tenant.
 *   2. createOnboardingLink($tenant, $return, $refresh) — generates
 *      a one-time AccountLink URL the operator follows to Stripe's
 *      hosted onboarding form.
 *   3. syncFromStripe($tenant) — pulls the latest Account state and
 *      mirrors charges_enabled / payouts_enabled / details_submitted
 *      onto the tenant. Called on return from onboarding + whenever
 *      we want a fresh check.
 *   4. createDashboardLink($tenant) — generates a one-time Express
 *      dashboard URL for ongoing payout/dispute management.
 *
 * Standard flow (future):
 *   - createOAuthAuthUrl($tenant)
 *   - exchangeOAuthCode($tenant, $code)
 *   …both will set stripe_connect_account_type='standard' so the rest
 *    of the system (webhook, checkout) keeps treating both uniformly.
 */
class StripeConnectService
{
    private StripeClient $stripe;

    public function __construct(?string $secretKey = null)
    {
        // Default to config; tests can pass an explicit secret.
        $this->stripe = new StripeClient($secretKey ?? config('cashier.secret'));
    }

    /**
     * Create an Express connected account for this tenant + record
     * the account id on the tenant row. Idempotent: if the tenant
     * already has a Connect account, returns it as-is rather than
     * creating a duplicate (Stripe would happily make duplicates).
     *
     * @throws ApiErrorException on Stripe API failures
     */
    public function createExpressAccount(Tenant $tenant): Account
    {
        if ($tenant->hasConnect()) {
            return $this->stripe->accounts->retrieve($tenant->stripe_account_id);
        }

        $account = $this->stripe->accounts->create([
            'type' => 'express',
            'email' => $tenant->contact_email,
            'business_profile' => [
                'name' => $tenant->name,
                // The customer-facing storefront URL — helps Stripe's
                // compliance review understand what the merchant sells.
                'url' => $this->storefrontUrl($tenant),
            ],
            // Light capability set: the storefront only needs card
            // payments + transfers (for application_fee_amount). Add
            // more (klarna_payments, ideal_payments, etc.) per merchant
            // if/when we expose those options.
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers' => ['requested' => true],
            ],
            'metadata' => [
                'ganvo_tenant_id' => (string) $tenant->id,
                'ganvo_tenant_slug' => $tenant->slug,
            ],
        ]);

        $tenant->update([
            'stripe_account_id' => $account->id,
            'stripe_connect_account_type' => 'express',
            // Flags start false; we sync them from the Account object
            // on return + on every account.updated webhook.
            'stripe_connect_charges_enabled' => (bool) $account->charges_enabled,
            'stripe_connect_payouts_enabled' => (bool) $account->payouts_enabled,
            'stripe_connect_details_submitted' => (bool) $account->details_submitted,
            'stripe_connect_disabled_reason' => $account->requirements?->disabled_reason,
        ]);

        return $account;
    }

    /**
     * Generate a one-time onboarding URL the operator follows to fill
     * Stripe's hosted form (Ganvo-branded for Express). The URL
     * expires after one use OR ~24h, so we always mint a fresh one on
     * demand rather than caching.
     *
     * @throws ApiErrorException
     */
    public function createOnboardingLink(Tenant $tenant, string $returnUrl, string $refreshUrl): AccountLink
    {
        if (! $tenant->hasConnect()) {
            $this->createExpressAccount($tenant);
            $tenant->refresh();
        }

        return $this->stripe->accountLinks->create([
            'account' => $tenant->stripe_account_id,
            // refresh_url = where to send them if the link expires
            //               before they finish (we just re-mint).
            // return_url  = where to send them after they finish.
            'refresh_url' => $refreshUrl,
            'return_url' => $returnUrl,
            'type' => 'account_onboarding',
        ]);
    }

    /**
     * Express dashboard URL — one-time link the operator clicks to
     * see their Stripe Express dashboard (payouts, balances, basic
     * disputes). Expires after one use OR a few minutes.
     *
     * Only valid for accounts that have completed onboarding;
     * Stripe returns an error otherwise.
     *
     * @throws ApiErrorException
     */
    public function createDashboardLink(Tenant $tenant): string
    {
        if (! $tenant->hasConnect()) {
            throw new \RuntimeException('Tenant has no Connect account yet.');
        }
        $link = $this->stripe->accounts->createLoginLink($tenant->stripe_account_id);
        return $link->url;
    }

    /**
     * Pull the latest Account state from Stripe and mirror onto the
     * tenant. Idempotent. Called on:
     *   - Return from onboarding (so the UI updates immediately).
     *   - The account.updated webhook (so we react to ongoing changes).
     *   - Operator hitting "Refresh status" button on the Payments page.
     *
     * @throws ApiErrorException
     */
    public function syncFromStripe(Tenant $tenant): ?Account
    {
        if (! $tenant->hasConnect()) {
            return null;
        }
        $account = $this->stripe->accounts->retrieve($tenant->stripe_account_id);

        $tenant->update([
            'stripe_connect_charges_enabled' => (bool) $account->charges_enabled,
            'stripe_connect_payouts_enabled' => (bool) $account->payouts_enabled,
            'stripe_connect_details_submitted' => (bool) $account->details_submitted,
            // disabled_reason is null when nothing's wrong. Examples:
            // 'requirements.past_due' / 'rejected.fraud' / etc.
            'stripe_connect_disabled_reason' => $account->requirements?->disabled_reason,
        ]);

        return $account;
    }

    /**
     * Create a PaymentIntent on the tenant's connected Stripe account
     * for the given Order. Money flow:
     *   Customer  ── total_cents ──►  Merchant's Connect account
     *                                       │
     *                                       └── application_fee ──►  Ganvo
     *
     * Stripe handles the split automatically when we set
     * application_fee_amount on a PI created on a connected account.
     *
     * @throws ApiErrorException
     */
    public function createPaymentIntent(Order $order, Tenant $tenant): PaymentIntent
    {
        if (! $tenant->canAcceptRealPayments()) {
            throw new \RuntimeException(
                'Tenant cannot accept real payments yet (Connect not ready).'
            );
        }

        $feeCents = PlatformFee::compute($tenant, (int) $order->total_cents);

        $params = [
            'amount' => (int) $order->total_cents,
            'currency' => strtolower($order->currency ?: 'eur'),
            // Auto payment methods = let Stripe pick the right
            // surface (card, Apple Pay, Google Pay, SEPA, etc.)
            // based on customer + region.
            'automatic_payment_methods' => ['enabled' => true],
            // Receipt email surfaces nicely in the Stripe dashboard
            // + can power the auto-receipt feature if enabled.
            'receipt_email' => $order->customer_email,
            // Metadata is the breadcrumb trail the webhook handler
            // uses to find the Order without a Stripe-side lookup.
            'metadata' => [
                'ganvo_order_id' => (string) $order->id,
                'ganvo_order_number' => (string) $order->order_number,
                'ganvo_tenant_id' => (string) $tenant->id,
            ],
        ];

        // Only include application_fee_amount when there's actually a
        // fee — Stripe rejects null/0 with a generic "Invalid integer:"
        // error and refuses the whole PI when the key is set but empty.
        if ($feeCents > 0) {
            $params['application_fee_amount'] = $feeCents;
        }

        return $this->stripe->paymentIntents->create(
            $params,
            // ── Crucial: when this option is set, the PI is created
            //    on the connected account, not on Ganvo's platform
            //    account. The money lands in the merchant's balance.
            ['stripe_account' => $tenant->stripe_account_id],
        );
    }

    /**
     * Refresh a PaymentIntent from Stripe — used by the order page
     * when the customer lands on it before our webhook has fired.
     *
     * @throws ApiErrorException
     */
    public function retrievePaymentIntent(string $paymentIntentId, Tenant $tenant): PaymentIntent
    {
        return $this->stripe->paymentIntents->retrieve(
            $paymentIntentId,
            [],
            ['stripe_account' => $tenant->stripe_account_id],
        );
    }

    /**
     * Disconnect a tenant from their Connect account. Used for support
     * cases ("merchant wants to start fresh") + automatically when
     * Stripe sends account.application.deauthorized.
     *
     * Does NOT delete the Stripe account itself — that's the merchant's
     * to keep / discard. We just stop using it.
     */
    public function disconnect(Tenant $tenant): void
    {
        $tenant->update([
            'stripe_account_id' => null,
            'stripe_connect_account_type' => null,
            'stripe_connect_charges_enabled' => false,
            'stripe_connect_payouts_enabled' => false,
            'stripe_connect_details_submitted' => false,
            'stripe_connect_disabled_reason' => null,
        ]);
    }

    /**
     * Customer-facing URL of the tenant's storefront. Used as the
     * business_profile.url on the Stripe account; helps Stripe's
     * compliance review understand what we're selling.
     */
    private function storefrontUrl(Tenant $tenant): string
    {
        $domain = config('ganvo.central_domain');
        $scheme = request()->isSecure() ? 'https' : 'http';
        $port = request()->getPort();
        $portSuffix = ($port && $port !== 80 && $port !== 443) ? ':' . $port : '';
        return sprintf('%s://%s.%s%s', $scheme, $tenant->slug, $domain, $portSuffix);
    }
}
