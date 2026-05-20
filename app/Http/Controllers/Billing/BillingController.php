<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Platform billing — the merchant pays Ganvo via Stripe Checkout + manages
 * billing via Stripe Customer Portal.
 *
 * Routes live on the central domain (not tenant subdomains) because billing
 * is a SaaS concern, not part of the storefront.
 */
class BillingController extends Controller
{
    /**
     * Start a Stripe Checkout Session for the authenticated merchant. The
     * caller picks the plan slug + period via form fields; we validate they
     * resolve to an active plan with a Stripe price configured, then redirect
     * the browser to Stripe.
     */
    public function checkout(Request $request)
    {
        $tenant = $this->tenant();

        $data = $request->validate([
            'plan_slug' => 'required|string|exists:plans,slug',
            'period' => 'required|in:' . implode(',', Plan::PERIODS),
        ]);

        $plan = Plan::where('slug', $data['plan_slug'])->where('is_active', true)->first();
        if (! $plan) {
            return back()->with('billing_error', __('billing.errors.plan_not_found'));
        }

        // Free plans don't go through Checkout — we just update the tenant.
        if ($plan->isFree()) {
            $tenant->update([
                'subscription_plan' => $plan->slug,
                'billing_period' => $data['period'],
            ]);
            return redirect()->route('billing.show')->with('billing_status', __('billing.status.switched_to_free'));
        }

        $priceId = $plan->stripePriceFor($data['period']);
        if (! $priceId) {
            return back()->with('billing_error', __('billing.errors.stripe_price_missing'));
        }

        // Stash the merchant's pick in the session — the success callback uses
        // it to flip the tenant's plan_slug + period to match what they bought
        // (Cashier writes the Stripe price ID to subscriptions.stripe_price,
        // but our app's display layer keys off the slug, not the price ID).
        $request->session()->put('billing.pending_plan', $plan->slug);
        $request->session()->put('billing.pending_period', $data['period']);

        try {
            $checkout = $tenant
                ->newSubscription(Tenant::SUBSCRIPTION_NAME, $priceId)
                ->checkout([
                    'success_url' => route('billing.checkout.success') . '?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => route('billing.show') . '?canceled=1',
                    // Pre-fill the customer's email so they don't have to retype it.
                    'customer_email' => $tenant->contact_email ?: ($request->user()->email ?? null),
                ]);
        } catch (\Throwable $e) {
            Log::error('Stripe Checkout creation failed', [
                'tenant_id' => $tenant->id,
                'plan' => $plan->slug,
                'period' => $data['period'],
                'error' => $e->getMessage(),
            ]);
            return back()->with('billing_error', __('billing.errors.stripe_unavailable'));
        }

        return redirect($checkout->url);
    }

    /**
     * Stripe → us redirect after Checkout completes. We don't trust this for
     * subscription state (webhooks do that), but we do use it to update the
     * tenant's plan + period to match what they actually bought.
     */
    public function checkoutSuccess(Request $request)
    {
        $tenant = $this->tenant();

        $pendingPlan = $request->session()->pull('billing.pending_plan');
        $pendingPeriod = $request->session()->pull('billing.pending_period');

        if ($pendingPlan && $pendingPeriod) {
            $tenant->update([
                'subscription_plan' => $pendingPlan,
                'billing_period' => $pendingPeriod,
            ]);
        }

        return redirect()->route('billing.show')->with('billing_status', __('billing.status.subscription_active'));
    }

    /**
     * Redirect the merchant into Stripe's Customer Portal where they can
     * change payment methods, view invoices, cancel, etc.
     */
    public function portal(Request $request)
    {
        $tenant = $this->tenant();

        if (! $tenant->stripe_id) {
            return back()->with('billing_error', __('billing.errors.no_customer'));
        }

        return $tenant->redirectToBillingPortal(route('billing.show'));
    }

    /**
     * Show the billing landing page (Filament page handles the actual UI;
     * this is just a fallback redirect target for the unauthenticated case
     * or non-Filament integrations).
     */
    public function show()
    {
        return redirect(config('app.url') . '/store/billing');
    }

    /**
     * Resolve the current tenant from the authenticated merchant user. 403s
     * if there isn't one (super admins don't have a tenant of their own).
     */
    private function tenant(): Tenant
    {
        $user = Auth::user();
        abort_unless($user && $user->tenant_id, 403);

        $tenant = Tenant::find($user->tenant_id);
        abort_unless($tenant, 403);

        return $tenant;
    }
}
