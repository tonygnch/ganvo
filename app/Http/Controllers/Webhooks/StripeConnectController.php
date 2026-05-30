<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Tenant;
use App\Notifications\OrderPlaced;
use App\Notifications\OrderRefunded;
use App\Services\Payments\StripeConnectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

/**
 * Stripe Connect webhook handler — separate from the Cashier
 * subscription webhook because:
 *   - Stripe issues a DIFFERENT webhook signing secret for each
 *     endpoint, so a single endpoint can't verify both signatures.
 *   - Connect events arrive scoped to the connected account; Cashier
 *     events are scoped to the platform account.
 *   - Keeping them separate makes debugging painless ("which endpoint
 *     fired?" answers itself).
 *
 * Handles four event types:
 *   account.updated                   → mirror Connect flags onto Tenant
 *   account.application.deauthorized  → tenant disconnected; clear state
 *   payment_intent.succeeded          → finalize Order (status=paid)
 *   payment_intent.payment_failed     → mark Order failed
 *
 * Every handler is idempotent — Stripe retries failures aggressively
 * and may send the same event multiple times. We key off Order.status
 * + the event payload's natural ids so re-processing is a no-op.
 */
class StripeConnectController extends Controller
{
    public function __construct(private readonly StripeConnectService $connect)
    {
    }

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $secret = config('services.stripe.connect_webhook_secret');

        if (! $secret) {
            Log::warning('Connect webhook hit but no STRIPE_CONNECT_WEBHOOK_SECRET configured');
            return response()->json(['error' => 'no_secret'], 500);
        }

        try {
            $event = Webhook::constructEvent($payload, $signature ?? '', $secret);
        } catch (SignatureVerificationException $e) {
            return response()->json(['error' => 'invalid_signature'], 400);
        } catch (\Throwable $e) {
            Log::error('Connect webhook parse error', ['e' => $e->getMessage()]);
            return response()->json(['error' => 'parse_error'], 400);
        }

        // Connect events carry the connected account id on event.account;
        // platform-level events (account.updated, etc.) also carry it.
        // We dispatch on event type.
        try {
            match ($event->type) {
                'account.updated'                    => $this->handleAccountUpdated($event),
                'account.application.deauthorized'   => $this->handleAccountDeauthorized($event),
                'payment_intent.succeeded'           => $this->handlePaymentIntentSucceeded($event),
                'payment_intent.payment_failed'      => $this->handlePaymentIntentFailed($event),
                'charge.refunded'                    => $this->handleChargeRefunded($event),
                default                              => null, // ignore others gracefully
            };
        } catch (\Throwable $e) {
            // 500 tells Stripe to retry — only do that for transient
            // issues. For known-bad payloads (Order not found,
            // tenant not found) we still return 200 so Stripe doesn't
            // hammer us for stale events.
            Log::error('Connect webhook handler error', [
                'event' => $event->type,
                'event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);
            return response()->json(['error' => 'handler_error'], 500);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Mirror account state onto the Tenant. Idempotent: same call
     * yields same result regardless of how many times it runs.
     */
    private function handleAccountUpdated(\Stripe\Event $event): void
    {
        $account = $event->data->object;
        $tenant = Tenant::where('stripe_account_id', $account->id)->first();
        if (! $tenant) {
            return; // not one of ours — ignore
        }
        $wasReady = $tenant->canAcceptRealPayments();
        $tenant->update([
            'stripe_connect_charges_enabled' => (bool) ($account->charges_enabled ?? false),
            'stripe_connect_payouts_enabled' => (bool) ($account->payouts_enabled ?? false),
            'stripe_connect_details_submitted' => (bool) ($account->details_submitted ?? false),
            'stripe_connect_disabled_reason' => $account->requirements?->disabled_reason ?? null,
        ]);

        // Edge-triggered: the moment a tenant becomes able to accept
        // real payments, register their storefront domain with Stripe
        // so Apple Pay / Google Pay show on checkout. Idempotent —
        // Stripe re-validates if already registered.
        $isReady = $tenant->fresh()->canAcceptRealPayments();
        if (! $wasReady && $isReady) {
            try {
                $this->connect->registerPaymentMethodDomain($tenant);
            } catch (\Throwable) {
                // Soft-fail: the operator can hit "Refresh status" to retry.
            }
        }
    }

    /**
     * Merchant disconnected Ganvo from their Stripe account (or
     * Stripe terminated the relationship). Clear the Connect state
     * so the storefront falls back to stub mode.
     */
    private function handleAccountDeauthorized(\Stripe\Event $event): void
    {
        $accountId = $event->account ?? null;
        if (! $accountId) {
            return;
        }
        $tenant = Tenant::where('stripe_account_id', $accountId)->first();
        if ($tenant) {
            $this->connect->disconnect($tenant);
        }
    }

    /**
     * Stripe says the charge succeeded — flip the pending Order to
     * paid, snapshot Stripe ids, send the receipt email.
     * Idempotent via status check (re-runs are no-ops).
     */
    private function handlePaymentIntentSucceeded(\Stripe\Event $event): void
    {
        $pi = $event->data->object;
        $order = Order::where('stripe_payment_intent_id', $pi->id)->first();
        if (! $order) {
            return;
        }
        if ($order->status === 'paid') {
            return; // already finalized — Stripe redelivery
        }

        // Capture the charge id + application fee id for refund flow.
        // Stripe API ≥ 2024-10-28 surfaces them on the PI via
        // latest_charge (string) — we don't need to follow that link
        // to get details, just to record the id.
        $chargeId = $pi->latest_charge ?? null;
        $applicationFeeId = $pi->application_fee_amount > 0
            ? ($pi->charges?->data[0]->application_fee ?? null)
            : null;
        $feeCents = (int) ($pi->application_fee_amount ?? 0);

        $order->update([
            'status' => 'paid',
            'paid_at' => now(),
            'stripe_charge_id' => $chargeId,
            'stripe_application_fee_id' => $applicationFeeId,
            'platform_fee_cents' => $feeCents,
        ]);

        // Send the receipt email — same notification as stub mode,
        // just fired async by the webhook.
        Notification::route('mail', $order->customer_email)
            ->notify(new OrderPlaced($order->fresh('items')));

        // Clear the customer's cart if they're still in their session
        // — we couldn't do this synchronously because the cart lives
        // in the session of the request that created the PI, not
        // this webhook's. Storefront-side: the order page can clear
        // it on view (small UX patch, see OrderController).
    }

    /**
     * Charge failed (card declined, etc.). Mark the Order failed so
     * the page surfaces an error + the customer can retry from /cart.
     */
    private function handlePaymentIntentFailed(\Stripe\Event $event): void
    {
        $pi = $event->data->object;
        $order = Order::where('stripe_payment_intent_id', $pi->id)->first();
        if (! $order) {
            return;
        }
        if ($order->status === 'failed') {
            return;
        }
        $order->update(['status' => 'failed']);
    }

    /**
     * Stripe says the charge was refunded (full or partial). Mirror
     * cumulative refund state onto the Order + reduce the platform
     * fee snapshot proportional to the amount that came back.
     *
     * Idempotent: the cumulative amounts come straight from Stripe's
     * event, so re-processing the same event is a no-op write.
     *
     * Side-effect: send OrderRefunded notification ONCE per refund
     * event. Stripe issues a separate charge.refunded event per
     * Refund record, so a 3-part partial refund yields 3 emails.
     */
    private function handleChargeRefunded(\Stripe\Event $event): void
    {
        $charge = $event->data->object;
        $order = Order::where('stripe_charge_id', $charge->id)->first();
        if (! $order) {
            return;
        }

        // Stripe's `amount_refunded` is cumulative (sum of all refunds
        // against this charge). The delta is what was refunded in THIS
        // event.
        $previousRefund = (int) $order->refund_amount_cents;
        $totalRefunded = (int) ($charge->amount_refunded ?? 0);
        $delta = max(0, $totalRefunded - $previousRefund);

        if ($delta <= 0) {
            return; // already accounted for — Stripe redelivery
        }

        // Application fee reverses proportionally. We don't compute it
        // ourselves — Stripe's `application_fee_amount` on the latest
        // Refund record carries the exact figure, but to keep this
        // handler self-contained we recompute the final platform fee
        // from the original charge's app fee scaled by the un-refunded
        // share.
        $originalFee = (int) ($charge->application_fee_amount ?? 0);
        $unrefundedShare = max(0, (int) $order->total_cents - $totalRefunded);
        $newFee = $order->total_cents > 0
            ? (int) round($originalFee * ($unrefundedShare / $order->total_cents))
            : 0;

        $fullyRefunded = $totalRefunded >= (int) $order->total_cents;
        $order->update([
            'refund_amount_cents' => $totalRefunded,
            'platform_fee_cents' => $newFee,
            'status' => $fullyRefunded ? Order::STATUS_REFUNDED : $order->status,
            'refunded_at' => $fullyRefunded ? ($order->refunded_at ?? now()) : $order->refunded_at,
        ]);

        Notification::route('mail', $order->customer_email)
            ->notify(new OrderRefunded($order->fresh(), $delta));
    }
}
