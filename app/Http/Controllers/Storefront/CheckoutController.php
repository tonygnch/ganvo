<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Notifications\OrderPlaced;
use App\Services\Cart;
use App\Services\Countries;
use App\Themes\ThemeRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

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

        // Shipping methods the operator has set up (or built-in defaults).
        // First method is pre-selected so the totals row has a number
        // to render without forcing a JS choice up front.
        $shippingMethods = $store->shippingMethods();
        $subtotal = $cart->subtotalCents();
        $defaultMethodId = $shippingMethods[0]['id'] ?? null;
        $shippingResolved = $defaultMethodId
            ? $store->resolveShippingMethod($defaultMethodId, $subtotal)
            : null;
        $shipping = $shippingResolved['cost_cents'] ?? 0;

        // Re-resolve the discount against the real shipping cost the
        // checkout flow computes (the cart page uses its own default).
        $discount = $cart->appliedDiscount($shipping);
        $discountCents = $cart->discountAmountCents($shipping);

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
                // Pre-compute each method's cost given the current
                // subtotal so the radio labels can show the actual €
                // (incl. "FREE" when the threshold is met).
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
        ]);
    }

    public function process(Request $request): RedirectResponse
    {
        $cart = Cart::forCurrent();
        if ($cart->isEmpty()) {
            return redirect('/cart');
        }

        $tenant = app('current_tenant');
        $store = $tenant->store;
        if ($store->requiresAccountCheckout() && ! Auth::guard('customer')->check()) {
            return redirect('/account/login');
        }

        // Allow-list of method ids the store currently offers — drives
        // the validation rule below so customers can't post arbitrary
        // method names.
        $methodIds = array_column($store->shippingMethods(), 'id');

        $data = $request->validate([
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

        $customer = Auth::guard('customer')->user();
        $items = $cart->items();
        $subtotal = $cart->subtotalCents();

        // Resolve the picked shipping method against THIS cart — applies
        // the free-over-threshold rule + falls back to the first method
        // if (somehow) the picked one disappeared between page load and
        // submit. validateOrThrow above usually catches that case but
        // belt+braces.
        $resolved = $store->resolveShippingMethod($data['shipping_method'], $subtotal)
            ?? $store->resolveShippingMethod($methodIds[0] ?? '', $subtotal);
        $shipping = $resolved['cost_cents'] ?? 0;
        $shippingLabel = $resolved['label'] ?? null;

        // Discount resolved at order-placement time so we never trust a
        // stale session amount.
        $discount = $cart->appliedDiscount($shipping);
        $discountAmount = $cart->discountAmountCents($shipping);
        $grandTotal = max(0, $subtotal + $shipping - $discountAmount);

        // Capture what the customer was viewing prices in at this moment, so the
        // order receipt forever shows the same number — even if FX rates move.
        $displayCurrency = $cart->displayCurrency();
        $displayRate = $cart->displayRate();
        $displayTotal = \App\Services\Money::convert($grandTotal, $displayRate);

        $order = DB::transaction(function () use (
            $tenant, $store, $customer, $items, $data,
            $grandTotal, $displayCurrency, $displayTotal,
            $discount, $discountAmount,
            $shipping, $shippingLabel
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
                // Snapshot the chosen shipping method's display label +
                // computed cost. Label stays readable even if the
                // operator later renames the method.
                'shipping_method_label' => $shippingLabel,
                'shipping_cents' => $shipping,
                // Discount snapshot.
                'discount_id' => $discount?->id,
                'discount_code' => $discount?->code,
                'discount_name' => $discount?->name,
                'discount_amount_cents' => $discountAmount,
                'status' => 'paid', // stub payment — always succeeds
                'shipping_address' => [
                    'line' => $data['address_line'],
                    'region' => $data['address_region'] ?? null,
                    'city' => $data['city'],
                    'postal_code' => $data['postal_code'],
                    'country' => $data['country'],
                ],
                'notes' => $data['notes'] ?? null,
                'paid_at' => now(),
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

            // Remember the address + phone + opt-in on the customer for
            // next time. Marketing opt-in is stored as a timestamp on
            // Customer (marketing_optin_at) — set on first opt-in and
            // left set even if the customer later unchecks it on a
            // subsequent order. Audit trail beats overwrite.
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

        $cart->clear();

        Notification::route('mail', $order->customer_email)
            ->notify(new OrderPlaced($order->fresh('items')));

        return redirect('/orders/' . $order->order_number);
    }
}
