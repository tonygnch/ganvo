<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Cart;
use App\Themes\ThemeRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function show(): View
    {
        $tenant = app('current_tenant');
        $store = $tenant->store;
        $theme = ThemeRegistry::exists($store->theme) ? $store->theme : 'default';
        $cart = Cart::forCurrent();

        $view = view()->exists("themes.{$theme}.cart") ? "themes.{$theme}.cart" : 'storefront.cart';

        // Pre-compute discount context for the view. Shipping uses the
        // cart's default (no address yet) — checkout recomputes with the
        // real shipping cost. Auto-discounts apply silently here too;
        // only code-applied ones have an `applied_code` value.
        $shipping = $cart->defaultShippingCents();
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
            'applied_code' => $cart->appliedCode(),
        ]);
    }

    /**
     * Apply a discount code (or clear via empty input). Validates that
     * the code resolves to a usable discount for this cart's subtotal —
     * surfaces a friendly flash either way.
     */
    public function applyDiscount(Request $request): RedirectResponse
    {
        $code = trim((string) $request->input('code', ''));
        $cart = Cart::forCurrent();

        if ($code === '') {
            $cart->removeDiscount();
            return redirect('/cart')->with('cart.flash', __('site.cart.discount_removed'));
        }

        $cart->applyCode($code);
        // Resolve immediately so we can tell the customer if the code
        // they typed didn't take. We re-resolve rather than trust the
        // session because applyCode just stores; resolution happens on
        // read.
        $resolved = $cart->appliedDiscount();
        if (! $resolved || strtoupper($code) !== ($resolved->code ?? '')) {
            // Stored their input so they can see what they typed when
            // they get back to the cart, but flag the failure.
            $cart->removeDiscount();
            return redirect('/cart')->with('cart.flash', __('site.cart.discount_invalid'));
        }

        return redirect('/cart')->with('cart.flash', __('site.cart.discount_applied', ['name' => $resolved->name]));
    }

    public function removeDiscount(Request $request): RedirectResponse
    {
        Cart::forCurrent()->removeDiscount();
        return redirect('/cart')->with('cart.flash', __('site.cart.discount_removed'));
    }

    public function add(Request $request): RedirectResponse
    {
        $slug = $request->route('slug');
        $tenant = app('current_tenant');

        $product = Product::where('tenant_id', $tenant->id)
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $variantId = $request->input('variant_id') !== null
            ? (int) $request->input('variant_id')
            : null;

        // Force variant selection when the product has any active
        // variants — otherwise the customer would be ordering an
        // ambiguous "default" version.
        if ($product->hasVariants() && ! $variantId) {
            return back()->with('cart.flash', __('site.storefront.cart.pick_a_variant'));
        }

        // Validate the variant belongs to this product + tenant + is
        // active. Trust nothing from the POST.
        if ($variantId) {
            $variant = ProductVariant::where('id', $variantId)
                ->where('product_id', $product->id)
                ->where('is_active', true)
                ->first();
            if (! $variant) {
                abort(404);
            }
        }

        Cart::forCurrent()->add($product->id, $variantId);

        $flashName = $variantId
            ? sprintf('%s — %s', $product->name, $variant->label)
            : $product->name;

        return back()->with('cart.flash', __('site.storefront.added_to_cart', ['name' => $flashName]));
    }

    public function update(Request $request): RedirectResponse
    {
        $lineId = (string) $request->route('lineId');
        $quantity = (int) $request->input('quantity', 1);
        Cart::forCurrent()->setQuantity($lineId, $quantity);

        return redirect('/cart');
    }

    public function remove(Request $request): RedirectResponse
    {
        $lineId = (string) $request->route('lineId');
        Cart::forCurrent()->remove($lineId);

        return redirect('/cart');
    }
}
