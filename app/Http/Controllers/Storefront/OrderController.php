<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Notifications\OrderPlaced;
use App\Services\Cart;
use App\Services\Payments\StripeConnectService;
use App\Themes\ThemeRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;
use Stripe\Exception\ApiErrorException;

class OrderController extends Controller
{
    public function show(Request $request): View
    {
        $orderNumber = $request->route('orderNumber');
        $tenant = app('current_tenant');
        $store = $tenant->store;
        $theme = ThemeRegistry::exists($store->theme) ? $store->theme : 'default';

        $order = Order::where('tenant_id', $tenant->id)
            ->where('order_number', $orderNumber)
            ->with('items')
            ->firstOrFail();

        // Stripe-mode race recovery: when the customer lands here right
        // after confirming a Payment Intent, the webhook might not have
        // arrived yet — pull the PI status from Stripe directly and
        // finalize the Order if it's already succeeded. Idempotent with
        // the webhook handler (whichever runs first wins; the other
        // sees status='paid' and no-ops).
        if (
            $order->payment_method === 'stripe'
            && $order->status === 'pending'
            && $order->stripe_payment_intent_id
            && $tenant->hasConnect()
        ) {
            $this->reconcilePendingStripeOrder($order, $tenant);
            $order->refresh();
        }

        // Clear the cart on successful arrival at the order page —
        // safer here than in the webhook because we don't have the
        // customer's session in the webhook.
        if ($order->status === 'paid' || $order->status === 'failed') {
            Cart::forCurrent()->clear();
        }

        // Theme-specific order confirmation when the theme provides one,
        // generic otherwise (same override pattern as cart / checkout).
        $view = view()->exists("themes.{$theme}.order")
            ? "themes.{$theme}.order"
            : 'storefront.order';

        return view($view, [
            'tenant' => $tenant,
            'store' => $store,
            'theme' => $theme,
            'order' => $order,
        ]);
    }

    /**
     * Soft sync: ask Stripe for the latest PaymentIntent state +
     * mirror onto the Order if Stripe has already moved past pending.
     * Failures don't 500 the page — they just leave the Order pending
     * and let the webhook do the work.
     */
    private function reconcilePendingStripeOrder(Order $order, $tenant): void
    {
        try {
            $pi = app(StripeConnectService::class)->retrievePaymentIntent(
                $order->stripe_payment_intent_id,
                $tenant
            );
        } catch (ApiErrorException) {
            return;
        }

        if ($pi->status === 'succeeded') {
            $order->update([
                'status' => 'paid',
                'paid_at' => now(),
                'stripe_charge_id' => $pi->latest_charge ?? null,
                'platform_fee_cents' => (int) ($pi->application_fee_amount ?? 0),
            ]);
            // Send the receipt — webhook handler is idempotent on
            // status='paid' so it won't double-fire if it arrives after.
            Notification::route('mail', $order->customer_email)
                ->notify(new OrderPlaced($order->fresh('items')));
        } elseif (in_array($pi->status, ['canceled', 'requires_payment_method'], true)) {
            // requires_payment_method = the customer's card got declined
            // and Stripe is waiting for a new one. Treat as failed for
            // our purposes; the customer can return to /cart and retry.
            $order->update(['status' => 'failed']);
        }
        // Other statuses (requires_action, processing) → leave pending;
        // the next pageview or the webhook will resolve.
    }
}
