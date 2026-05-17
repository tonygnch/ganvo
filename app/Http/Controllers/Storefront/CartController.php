<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
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

        return view('storefront.cart', [
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

        Cart::forCurrent()->add($product->id);

        return back()->with('cart.flash', __('site.storefront.added_to_cart', ['name' => $product->name]));
    }

    public function update(Request $request): RedirectResponse
    {
        $productId = (int) $request->route('productId');
        $quantity = (int) $request->input('quantity', 1);
        Cart::forCurrent()->setQuantity($productId, $quantity);

        return redirect('/cart');
    }

    public function remove(Request $request): RedirectResponse
    {
        $productId = (int) $request->route('productId');
        Cart::forCurrent()->remove($productId);

        return redirect('/cart');
    }
}
