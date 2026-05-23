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

        return view($view, [
            'tenant' => $tenant,
            'store' => $store,
            'theme' => $theme,
            'items' => $cart->items(),
            'total_cents' => $cart->totalCents(),
        ]);
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
