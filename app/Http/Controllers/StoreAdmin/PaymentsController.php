<?php

namespace App\Http\Controllers\StoreAdmin;

use App\Http\Controllers\Controller;
use App\Services\Payments\StripeConnectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stripe\Exception\ApiErrorException;

/**
 * AJAX/redirect endpoints backing the StoreAdmin Payments page.
 *
 * The Filament page itself just renders status — these are the
 * action endpoints it links to (kick off onboarding, return from
 * Stripe, refresh expired links, open the Express dashboard,
 * disconnect).
 */
class PaymentsController extends Controller
{
    public function __construct(private readonly StripeConnectService $connect)
    {
    }

    /**
     * POST /store/payments/connect/express
     *
     * Creates the Connect account if it doesn't exist, mints a fresh
     * onboarding AccountLink, and redirects the operator into Stripe's
     * hosted onboarding form.
     */
    public function startExpressOnboarding(Request $request): RedirectResponse
    {
        $tenant = Auth::user()?->tenant;
        if (! $tenant) {
            abort(403);
        }

        try {
            $link = $this->connect->createOnboardingLink(
                $tenant,
                returnUrl: route('store.payments.return'),
                refreshUrl: route('store.payments.refresh'),
            );
        } catch (ApiErrorException $e) {
            return back()->with('flash', [
                'type' => 'error',
                'message' => 'Stripe error: ' . $e->getMessage(),
            ]);
        }

        return redirect()->away($link->url);
    }

    /**
     * GET /store/payments/return
     *
     * Where Stripe sends the operator after they complete (or pause
     * partway through) onboarding. We sync the latest Account state +
     * bounce back to the Payments page. The page reads the freshly-
     * synced flags and shows the right next step.
     */
    public function handleReturn(Request $request): RedirectResponse
    {
        $tenant = Auth::user()?->tenant;
        if (! $tenant) {
            abort(403);
        }

        try {
            $this->connect->syncFromStripe($tenant);
        } catch (ApiErrorException $e) {
            // Soft-fail: the redirect still happens; the page will
            // surface the stale state + offer a "Refresh status" button.
        }

        return redirect('/store/payments');
    }

    /**
     * GET /store/payments/refresh
     *
     * Stripe redirects here when the onboarding link expires before
     * the operator finishes. We just re-mint and bounce them straight
     * back into a fresh onboarding flow.
     */
    public function handleRefresh(Request $request): RedirectResponse
    {
        return $this->startExpressOnboarding($request);
    }

    /**
     * POST /store/payments/dashboard
     *
     * One-time Express dashboard URL — for ongoing payout + dispute
     * management. Opens in a new tab from the Payments page.
     */
    public function openDashboard(Request $request): RedirectResponse
    {
        $tenant = Auth::user()?->tenant;
        if (! $tenant?->hasConnect()) {
            return back();
        }

        try {
            $url = $this->connect->createDashboardLink($tenant);
        } catch (ApiErrorException $e) {
            return back()->with('flash', [
                'type' => 'error',
                'message' => 'Stripe error: ' . $e->getMessage(),
            ]);
        }

        return redirect()->away($url);
    }

    /**
     * POST /store/payments/sync
     *
     * Operator-triggered "refresh my status" — used when Stripe took
     * a moment to update the account (compliance review finishing)
     * and the operator wants a manual pull rather than waiting for
     * the next webhook.
     */
    public function syncStatus(Request $request): RedirectResponse
    {
        $tenant = Auth::user()?->tenant;
        if (! $tenant) {
            abort(403);
        }

        try {
            $this->connect->syncFromStripe($tenant);
        } catch (ApiErrorException $e) {
            return back()->with('flash', [
                'type' => 'error',
                'message' => 'Stripe error: ' . $e->getMessage(),
            ]);
        }

        return redirect('/store/payments');
    }

    /**
     * POST /store/payments/disconnect
     *
     * Cuts the link between the tenant and their Connect account. The
     * Stripe account itself stays — the merchant can re-connect later
     * (creating a fresh account) or never. Storefront reverts to stub
     * mode immediately.
     */
    public function disconnect(Request $request): RedirectResponse
    {
        $tenant = Auth::user()?->tenant;
        if (! $tenant) {
            abort(403);
        }
        $this->connect->disconnect($tenant);

        return redirect('/store/payments');
    }
}
