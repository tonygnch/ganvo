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
     *
     * If the merchant ALREADY has an active subscription, we route to
     * {@see swap()} instead — Stripe Checkout creates a new subscription
     * each time, which would result in double-billing. swap() updates the
     * existing subscription in place with proration.
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
            // If they had an active paid subscription, cancel it; otherwise
            // they'd keep being billed despite "downgrading" to free.
            if ($tenant->platformSubscribed()) {
                try {
                    $tenant->platformSubscription()->cancel();
                } catch (\Throwable $e) {
                    Log::warning('Cancel-to-free failed', ['tenant_id' => $tenant->id, 'error' => $e->getMessage()]);
                }
            }
            $tenant->update([
                'subscription_plan' => $plan->slug,
                'billing_period' => $data['period'],
            ]);
            return redirect()->route('billing.show')->with('billing_status', __('billing.status.switched_to_free'));
        }

        // Already subscribed → swap with proration instead of double-charging.
        if ($tenant->platformSubscribed()) {
            return $this->swap($request);
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
            // No customer_email here — once Cashier has created a Stripe
            // customer for the tenant (stored as stripe_id), it auto-passes
            // `customer` and Stripe rejects with "You may only specify one
            // of these parameters: customer, customer_email." Stripe
            // Checkout collects the email from the user if no customer
            // exists yet.
            $checkout = $tenant
                ->newSubscription(Tenant::SUBSCRIPTION_NAME, $priceId)
                ->checkout([
                    'success_url' => route('billing.checkout.success') . '?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => route('billing.show') . '?canceled=1',
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
     * Swap an existing subscription to a different plan/period with
     * automatic proration. Used when a tenant changes plans from the
     * Billing page — Cashier's swapAndInvoice():
     *   - Updates the Stripe subscription to the new price.
     *   - Stripe automatically credits unused time on the old price.
     *   - Stripe automatically charges prorated cost of the new price
     *     for the remaining cycle.
     *   - Issues an immediate invoice for the net difference (positive
     *     amount = charged today; negative = customer balance credit
     *     applied to next invoice).
     *
     * Net effect:
     *   - Upgrade  (Pro → Business)  → charged immediately for the gap.
     *   - Downgrade (Business → Pro) → credit applied forward against
     *     next month's invoice. No refund to card (Stripe + industry
     *     norm; refunds are risky/fee-heavy and best handled manually
     *     by support if a merchant really insists).
     *
     * Period swaps (monthly ↔ yearly) work identically — Stripe treats
     * it as a price change.
     */
    public function swap(Request $request)
    {
        $tenant = $this->tenant();

        $data = $request->validate([
            'plan_slug' => 'required|string|exists:plans,slug',
            'period' => 'required|in:' . implode(',', Plan::PERIODS),
        ]);

        if (! $tenant->platformSubscribed()) {
            return back()->with('billing_error', __('billing.errors.no_subscription'));
        }

        $plan = Plan::where('slug', $data['plan_slug'])->where('is_active', true)->first();
        if (! $plan) {
            return back()->with('billing_error', __('billing.errors.plan_not_found'));
        }

        if ($plan->isFree()) {
            // Switching from a paid plan to free = cancel the subscription.
            // We don't immediately delete the Stripe subscription; cancel()
            // sets ends_at to the current period end, so the merchant
            // keeps access until they've used what they paid for.
            try {
                $tenant->platformSubscription()->cancel();
            } catch (\Throwable $e) {
                Log::error('Cancel-on-downgrade-to-free failed', [
                    'tenant_id' => $tenant->id, 'error' => $e->getMessage(),
                ]);
                return back()->with('billing_error', __('billing.errors.stripe_unavailable'));
            }
            $tenant->update([
                'subscription_plan' => $plan->slug,
                'billing_period' => $data['period'],
            ]);
            return redirect()->route('billing.show')
                ->with('billing_status', __('billing.status.scheduled_downgrade_to_free'));
        }

        $priceId = $plan->stripePriceFor($data['period']);
        if (! $priceId) {
            return back()->with('billing_error', __('billing.errors.stripe_price_missing'));
        }

        // No-op if they're already on this exact price — Stripe accepts
        // the call but it's wasted; surface a friendlier message.
        $current = $tenant->platformSubscription();
        if ($current && $current->stripe_price === $priceId) {
            return back()->with('billing_status', __('billing.status.already_on_plan'));
        }

        try {
            // swapAndInvoice = swap + immediately issue invoice for the
            // proration delta. The Stripe webhook (invoice.paid +
            // customer.subscription.updated) will refresh our local
            // subscription row with the new stripe_price afterward.
            $tenant->platformSubscription()->swapAndInvoice($priceId);
        } catch (\Laravel\Cashier\Exceptions\IncompletePayment $e) {
            // SCA / 3D Secure flow. Stripe needs the customer to confirm
            // the proration charge (test mode + EU cards both commonly
            // trigger this on the second charge to a saved payment
            // method). Cashier exposes the Payment intent with a hosted
            // confirmation URL — bounce the merchant there. Once they
            // confirm, Stripe webhooks fire and the subscription leaves
            // past_due automatically.
            $tenant->update([
                'subscription_plan' => $plan->slug,
                'billing_period' => $data['period'],
            ]);
            return redirect()->route('cashier.payment', [
                $e->payment->id,
                'redirect' => route('billing.show'),
            ]);
        } catch (\Throwable $e) {
            Log::error('Plan swap failed', [
                'tenant_id' => $tenant->id,
                'from' => $current?->stripe_price,
                'to' => $priceId,
                'error' => $e->getMessage(),
            ]);
            return back()->with('billing_error', __('billing.errors.stripe_unavailable'));
        }

        $tenant->update([
            'subscription_plan' => $plan->slug,
            'billing_period' => $data['period'],
        ]);

        return redirect()->route('billing.show')
            ->with('billing_status', __('billing.status.plan_swapped'));
    }

    /**
     * Compute (but don't apply) the proration invoice for a hypothetical
     * swap. Used by the Billing page's confirmation modal so the merchant
     * sees the exact charge amount + line breakdown before committing.
     *
     * Returns JSON for the modal's fetch() call; never charges anything.
     */
    public function swapPreview(Request $request)
    {
        $tenant = $this->tenant();

        $data = $request->validate([
            'plan_slug' => 'required|string|exists:plans,slug',
            'period' => 'required|in:' . implode(',', Plan::PERIODS),
        ]);

        if (! $tenant->platformSubscribed()) {
            return response()->json(['ok' => false, 'message' => __('billing.errors.no_subscription')], 400);
        }

        $plan = Plan::where('slug', $data['plan_slug'])->where('is_active', true)->first();
        if (! $plan) {
            return response()->json(['ok' => false, 'message' => __('billing.errors.plan_not_found')], 400);
        }

        // Free plan = cancel the paid subscription at period end. Show a
        // dedicated message instead of a proration breakdown.
        if ($plan->isFree()) {
            $current = $tenant->platformSubscription();
            $endDate = null;
            if ($current?->stripe_id) {
                try {
                    $sub = \Laravel\Cashier\Cashier::stripe()->subscriptions->retrieve($current->stripe_id);
                    $endTs = $sub->items->data[0]->current_period_end ?? $sub->current_period_end ?? null;
                    $endDate = $endTs ? \Carbon\Carbon::createFromTimestamp($endTs)->toFormattedDateString() : null;
                } catch (\Throwable $e) { /* shrug — modal still works */ }
            }
            return response()->json([
                'ok' => true,
                'is_cancel' => true,
                'plan_label' => $plan->translated('name'),
                'end_date' => $endDate,
            ]);
        }

        $priceId = $plan->stripePriceFor($data['period']);
        if (! $priceId) {
            return response()->json(['ok' => false, 'message' => __('billing.errors.stripe_price_missing')], 400);
        }

        $current = $tenant->platformSubscription();
        if ($current && $current->stripe_price === $priceId) {
            return response()->json([
                'ok' => true,
                'already_on_plan' => true,
                'message' => __('billing.status.already_on_plan'),
            ]);
        }

        try {
            $stripe = \Laravel\Cashier\Cashier::stripe();

            // Need the subscription item ID (different from the subscription
            // ID itself) — that's what Stripe wants to override.
            $sub = $stripe->subscriptions->retrieve($current->stripe_id);
            $itemId = $sub->items->data[0]->id ?? null;
            if (! $itemId) {
                return response()->json(['ok' => false, 'message' => __('billing.errors.stripe_unavailable')], 500);
            }

            // createPreview is the modern Stripe API (replaced the older
            // invoices/upcoming endpoint in API version 2024-10-28). Returns
            // a full Invoice object that hasn't been persisted — perfect
            // for "what would happen if we swapped right now."
            $preview = $stripe->invoices->createPreview([
                'customer' => $tenant->stripe_id,
                'subscription' => $current->stripe_id,
                'subscription_details' => [
                    'items' => [[
                        'id' => $itemId,
                        'price' => $priceId,
                    ]],
                    'proration_behavior' => 'create_prorations',
                ],
            ]);

            $currency = strtoupper($preview->currency);
            $lines = [];
            foreach ($preview->lines->data as $line) {
                $lines[] = [
                    'description' => $line->description ?: ($line->price->nickname ?? 'Line item'),
                    'amount_cents' => $line->amount,
                    'formatted' => \App\Services\Money::format($line->amount, $currency),
                ];
            }

            $isCharge = $preview->total > 0;
            return response()->json([
                'ok' => true,
                'total_cents' => $preview->total,
                'total_formatted' => \App\Services\Money::format(abs($preview->total), $currency),
                'currency' => $currency,
                'is_charge' => $isCharge,                  // false → credit applied forward
                'plan_label' => $plan->translated('name'),
                'period' => $data['period'],
                'lines' => $lines,
            ]);
        } catch (\Throwable $e) {
            Log::error('Swap preview failed', [
                'tenant_id' => $tenant->id,
                'to' => $priceId,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['ok' => false, 'message' => __('billing.errors.stripe_unavailable')], 500);
        }
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
