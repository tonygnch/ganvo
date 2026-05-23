<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Notifications\OrderPlaced;
use App\Services\Cart;
use App\Themes\ThemeRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
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

        return view($view, [
            'tenant' => $tenant,
            'store' => $store,
            'theme' => $theme,
            'items' => $cart->items(),
            'total_cents' => $cart->totalCents(),
            'customer' => $customer,
        ]);
    }

    public function process(Request $request): RedirectResponse
    {
        $cart = Cart::forCurrent();
        if ($cart->isEmpty()) {
            return redirect('/cart');
        }

        $store = app('current_tenant')->store;
        if ($store->requiresAccountCheckout() && ! Auth::guard('customer')->check()) {
            return redirect('/account/login');
        }

        $data = $request->validate([
            'customer_email' => ['required', 'email', 'max:255'],
            'customer_name' => ['required', 'string', 'max:255'],
            'address_line' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'postal_code' => ['required', 'string', 'max:30'],
            'country' => ['required', 'string', 'max:2'],
        ]);

        $tenant = app('current_tenant');
        $customer = Auth::guard('customer')->user();
        $store = $tenant->store;
        $items = $cart->items();
        $subtotal = $cart->totalCents();
        $shipping = $subtotal >= 5000 ? 0 : 500;
        $grandTotal = $subtotal + $shipping;

        // Capture what the customer was viewing prices in at this moment, so the
        // order receipt forever shows the same number — even if FX rates move.
        $displayCurrency = $cart->displayCurrency();
        $displayRate = $cart->displayRate();
        $displayTotal = \App\Services\Money::convert($grandTotal, $displayRate);

        $order = DB::transaction(function () use ($tenant, $store, $customer, $items, $data, $grandTotal, $displayCurrency, $displayTotal) {
            $order = Order::create([
                'tenant_id' => $tenant->id,
                'customer_id' => $customer?->id,
                'order_number' => strtoupper(Str::random(10)),
                'customer_email' => $data['customer_email'],
                'customer_name' => $data['customer_name'],
                'total_cents' => $grandTotal,
                'currency' => strtoupper($store->currency ?? 'EUR'),
                'display_currency' => $displayCurrency,
                'display_total_cents' => $displayTotal,
                'status' => 'paid', // stub payment — always succeeds
                'shipping_address' => [
                    'line' => $data['address_line'],
                    'city' => $data['city'],
                    'postal_code' => $data['postal_code'],
                    'country' => $data['country'],
                ],
                'paid_at' => now(),
            ]);

            foreach ($items as $row) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $row['product']->id,
                    'product_variant_id' => $row['variant']?->id,
                    'product_name' => $row['product']->name,
                    // Snapshot the variant label — keeps receipts accurate
                    // even after the variant is renamed or deleted.
                    'variant_label' => $row['variant']?->label,
                    // Use the per-line unit price the cart computed (already
                    // resolves variant override → product fallback).
                    'unit_price_cents' => $row['unit_price_cents'],
                    'quantity' => $row['quantity'],
                    'subtotal_cents' => $row['subtotal_cents'],
                ]);
            }

            // Remember the address on the customer for next time.
            if ($customer) {
                $customer->update(['default_shipping_address' => $order->shipping_address]);
            }

            return $order;
        });

        $cart->clear();

        Notification::route('mail', $order->customer_email)
            ->notify(new OrderPlaced($order->fresh('items')));

        return redirect('/orders/' . $order->order_number);
    }
}
