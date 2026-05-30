<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Notifications\OrderPlaced;
use App\Services\Cart;
use App\Services\Countries;
use App\Services\Payments\StripeConnectService;
use App\Themes\ThemeRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Stripe\Exception\ApiErrorException;

class CheckoutController extends Controller
{
    public function show(): View|RedirectResponse
    {
        $cart = Cart::forCurrent();
        if ($cart->isEmpty()) {
            return redirect('/cart');
        }

        $tenant = app('current_tenant');
        $store = $tenant->store;

        // Account-required stores: bounce to login.
        if ($store->requiresAccountCheckout() && ! Auth::guard('customer')->check()) {
            session()->put('url.intended', '/checkout');
            return redirect('/account/login')
                ->with('cart.flash', __('site.storefront.sign_in_for_checkout'));
        }

        $theme = ThemeRegistry::exists($store->theme) ? $store->theme : 'default';
        $customer = Auth::guard('customer')->user();

        $view = view()->exists("themes.{$theme}.checkout") ? "themes.{$theme}.checkout" : 'storefront.checkout';

        $shippingMethods = $store->shippingMethods();
        $subtotal = $cart->subtotalCents();
        $defaultMethodId = $shippingMethods[0]['id'] ?? null;
        $shippingResolved = $defaultMethodId
            ? $store->resolveShippingMethod($defaultMethodId, $subtotal)
            : null;
        $shipping = $shippingResolved['cost_cents'] ?? 0;

        $discount = $cart->appliedDiscount($shipping);
        $discountCents = $cart->discountAmountCents($shipping);

        // Stripe-mode flag drives both the form behavior + the
        // Stripe.js include in the view. Stays false for tenants in
        // stub mode (the default until they complete Connect onboarding).
        $paymentMode = $tenant->canAcceptRealPayments() ? 'stripe' : 'stub';

        return view($view, [
            'tenant' => $tenant,
            'store' => $store,
            'theme' => $theme,
            'items' => $cart->items(),
            'total_cents' => $cart->totalCents(),
            'discount' => $discount,
            'discount_cents' => $discountCents,
            'shipping_cents' => $shipping,
            'shipping_methods' => $shippingMethods,
            'shipping_methods_for_subtotal' => array_map(
                fn (array $m) => $m + [
                    'cost_cents' => ($m['free_threshold_cents'] !== null && $subtotal >= $m['free_threshold_cents'])
                        ? 0
                        : $m['price_cents'],
                ],
                $shippingMethods
            ),
            'default_shipping_method_id' => $defaultMethodId,
            'countries' => Countries::all(),
            'customer' => $customer,
            // Stripe context — only set when payment_mode = 'stripe'.
            // The view checks $payment_mode to decide whether to mount
            // the Payment Element + JS confirm flow.
            'payment_mode' => $paymentMode,
            'stripe_publishable_key' => $paymentMode === 'stripe' ? config('cashier.key') : null,
            'stripe_account_id' => $paymentMode === 'stripe' ? $tenant->stripe_account_id : null,
        ]);
    }

    /**
     * POST /checkout
     *
     * Branches on the tenant's payment mode:
     *
     *   STUB MODE (canAcceptRealPayments == false):
     *     Same as before — validate form, create paid Order in one
     *     transaction, send email, HTTP redirect to /orders/{number}.
     *
     *   STRIPE MODE (canAcceptRealPayments == true):
     *     Validate form, create a PENDING Order (status='pending',
     *     payment_method='stripe'), create a PaymentIntent on the
     *     connected account, return JSON the JS uses to drive the
     *     Payment Element confirmation. The webhook finalizes the
     *     order once Stripe says the charge succeeded.
     */
    public function process(Request $request): RedirectResponse|JsonResponse
    {
        $cart = Cart::forCurrent();
        if ($cart->isEmpty()) {
            return $this->jsonOrRedirect($request, ['error' => 'empty_cart'], '/cart', 410);
        }

        $tenant = app('current_tenant');
        $store = $tenant->store;
        if ($store->requiresAccountCheckout() && ! Auth::guard('customer')->check()) {
            return $this->jsonOrRedirect($request, ['error' => 'login_required'], '/account/login', 401);
        }

        $data = $this->validatePayload($request, $store);

        $customer = Auth::guard('customer')->user();
        $items = $cart->items();
        $subtotal = $cart->subtotalCents();

        $methodIds = array_column($store->shippingMethods(), 'id');
        $resolved = $store->resolveShippingMethod($data['shipping_method'], $subtotal)
            ?? $store->resolveShippingMethod($methodIds[0] ?? '', $subtotal);
        $shipping = $resolved['cost_cents'] ?? 0;
        $shippingLabel = $resolved['label'] ?? null;

        $discount = $cart->appliedDiscount($shipping);
        $discountAmount = $cart->discountAmountCents($shipping);
        $grandTotal = max(0, $subtotal + $shipping - $discountAmount);

        $displayCurrency = $cart->displayCurrency();
        $displayRate = $cart->displayRate();
        $displayTotal = \App\Services\Money::convert($grandTotal, $displayRate);

        // The same Order build in both modes — only `status` +
        // `payment_method` differ.
        $isStripe = $tenant->canAcceptRealPayments();

        $order = DB::transaction(function () use (
            $tenant, $store, $customer, $items, $data,
            $grandTotal, $displayCurrency, $displayTotal,
            $discount, $discountAmount,
            $shipping, $shippingLabel,
            $isStripe
        ) {
            $order = Order::create([
                'tenant_id' => $tenant->id,
                'customer_id' => $customer?->id,
                'order_number' => strtoupper(Str::random(10)),
                'customer_email' => $data['customer_email'],
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'] ?? null,
                'marketing_opt_in' => (bool) ($data['marketing_opt_in'] ?? false),
                'total_cents' => $grandTotal,
                'currency' => strtoupper($store->currency ?? 'EUR'),
                'display_currency' => $displayCurrency,
                'display_total_cents' => $displayTotal,
                'shipping_method_label' => $shippingLabel,
                'shipping_cents' => $shipping,
                'discount_id' => $discount?->id,
                'discount_code' => $discount?->code,
                'discount_name' => $discount?->name,
                'discount_amount_cents' => $discountAmount,
                // Branch: stub orders are paid instantly; stripe orders
                // wait for the webhook to flip them to 'paid'.
                'status' => $isStripe ? 'pending' : 'paid',
                'payment_method' => $isStripe ? 'stripe' : 'stub',
                'shipping_address' => [
                    'line' => $data['address_line'],
                    'region' => $data['address_region'] ?? null,
                    'city' => $data['city'],
                    'postal_code' => $data['postal_code'],
                    'country' => $data['country'],
                ],
                'notes' => $data['notes'] ?? null,
                // Only stamp paid_at in stub mode — the webhook does
                // it for stripe orders.
                'paid_at' => $isStripe ? null : now(),
            ]);

            if ($discount) {
                $discount->increment('times_used');
            }

            foreach ($items as $row) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $row['product']->id,
                    'product_variant_id' => $row['variant']?->id,
                    'product_name' => $row['product']->name,
                    'variant_label' => $row['variant']?->label,
                    'unit_price_cents' => $row['unit_price_cents'],
                    'quantity' => $row['quantity'],
                    'subtotal_cents' => $row['subtotal_cents'],
                ]);
            }

            if ($customer) {
                $update = ['default_shipping_address' => $order->shipping_address];
                if (! empty($data['customer_phone'])) {
                    $update['phone'] = $data['customer_phone'];
                }
                if (! empty($data['marketing_opt_in']) && ! $customer->marketing_optin_at) {
                    $update['marketing_optin_at'] = now();
                }
                $customer->update($update);
            }

            return $order;
        });

        if ($isStripe) {
            // Stripe path: create the PaymentIntent on the connected
            // account, attach the PI id to the Order, hand the
            // client_secret back to JS. The cart STAYS until the
            // webhook confirms success so an abandoned payment can be
            // retried without losing the cart state.
            try {
                $intent = app(StripeConnectService::class)->createPaymentIntent($order, $tenant);
            } catch (ApiErrorException $e) {
                // Surface so JS can retry / show error.
                $order->update(['status' => 'failed']);
                return response()->json([
                    'error' => 'stripe_error',
                    'message' => $e->getMessage(),
                ], 502);
            }

            $order->update(['stripe_payment_intent_id' => $intent->id]);

            return response()->json([
                'mode' => 'stripe',
                'client_secret' => $intent->client_secret,
                'publishable_key' => config('cashier.key'),
                'stripe_account_id' => $tenant->stripe_account_id,
                'return_url' => url('/orders/' . $order->order_number),
                'order_number' => $order->order_number,
            ]);
        }

        // Stub path: cart cleared + email sent inline. Email for
        // stripe orders is sent from the webhook handler once the
        // charge actually succeeds.
        $cart->clear();
        Notification::route('mail', $order->customer_email)
            ->notify(new OrderPlaced($order->fresh('items')));

        return redirect('/orders/' . $order->order_number);
    }

    /**
     * Validation rules shared by both stub + stripe paths so we don't
     * drift over time.
     *
     * @return array<string, mixed>
     */
    private function validatePayload(Request $request, $store): array
    {
        $methodIds = array_column($store->shippingMethods(), 'id');

        return $request->validate([
            'customer_email'    => ['required', 'email', 'max:255'],
            'customer_name'     => ['required', 'string', 'max:255'],
            'customer_phone'    => ['nullable', 'string', 'max:60'],
            'address_line'      => ['required', 'string', 'max:255'],
            'address_region'    => ['nullable', 'string', 'max:120'],
            'city'              => ['required', 'string', 'max:120'],
            'postal_code'       => ['required', 'string', 'max:30'],
            'country'           => ['required', 'string', 'size:2', Rule::in(array_keys(Countries::LIST))],
            'shipping_method'   => ['required', 'string', Rule::in($methodIds)],
            'notes'             => ['nullable', 'string', 'max:2000'],
            'marketing_opt_in'  => ['nullable', 'boolean'],
        ]);
    }

    /**
     * Respond to error conditions before payment intent creation
     * appropriately for the request type.
     */
    private function jsonOrRedirect(Request $request, array $body, string $redirectTo, int $status = 422): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson() || $request->isXmlHttpRequest()) {
            return response()->json($body, $status);
        }
        return redirect($redirectTo);
    }
}
