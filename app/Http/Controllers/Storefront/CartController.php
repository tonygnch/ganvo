<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Cart;
use App\Services\Money;
use App\Themes\ThemeRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    /**
     * Build the recomputed cart state for an async (fetch) response. Money
     * is pre-formatted server-side using the request's display currency +
     * FX rate so the client never has to reimplement currency formatting.
     *
     * @return array<string, mixed>
     */
    private function cartState(Cart $cart, ?string $flash = null): array
    {
        $rate = $cart->displayRate();
        $currency = $cart->displayCurrency();
        $fmt = fn (int $cents) => Money::display($cents, $rate, $currency);

        $items = $cart->items();
        $subtotal = $cart->subtotalCents();
        // Cart-page shipping is the store default (no address yet); the
        // discount engine needs it to evaluate free-shipping style rules.
        $shipping = $cart->defaultShippingCents();
        $discount = $cart->appliedDiscount($shipping);
        $discountCents = $cart->discountAmountCents($shipping);
        $grand = max(0, $subtotal - $discountCents);

        return [
            'ok'         => true,
            'empty'      => $items->isEmpty(),
            'item_count' => $cart->itemCount(),
            'lines'      => $items->map(fn ($row) => [
                'line_id'         => $row['line_id'],
                'quantity'        => $row['quantity'],
                'subtotal'        => $fmt($row['subtotal_cents']),
                // Extended fields for the slide-out cart drawer; the cart
                // page's own JS patches by line_id and ignores these.
                'name'            => $row['product']->name,
                'variant'         => $row['variant']->label ?? null,
                'unit'            => $fmt($row['unit_price_cents']),
                'image'           => $row['product']->image_path
                    ? \Illuminate\Support\Facades\Storage::url($row['product']->image_path)
                    : null,
                'url'             => '/products/' . $row['product']->slug,
            ])->values()->all(),
            'subtotal'        => $fmt($subtotal),
            'discount'        => ($discount && $discountCents > 0) ? [
                'name'   => $discount->name,
                'amount' => '−' . $fmt($discountCents),
            ] : null,
            'applied_code' => $cart->appliedCode(),
            'total'        => $fmt($grand),
            'flash'        => $flash,
        ];
    }
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
    public function applyDiscount(Request $request): RedirectResponse|JsonResponse
    {
        $code = trim((string) $request->input('code', ''));
        $cart = Cart::forCurrent();

        if ($code === '') {
            $cart->removeDiscount();
            return $this->discountResponse($request, $cart, __('site.cart.discount_removed'));
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
            return $this->discountResponse($request, $cart, __('site.cart.discount_invalid'), false);
        }

        return $this->discountResponse($request, $cart, __('site.cart.discount_applied', ['name' => $resolved->name]));
    }

    public function removeDiscount(Request $request): RedirectResponse|JsonResponse
    {
        $cart = Cart::forCurrent();
        $cart->removeDiscount();
        return $this->discountResponse($request, $cart, __('site.cart.discount_removed'));
    }

    /**
     * Shared response shaping for the discount endpoints. Async callers
     * get the recomputed cart state (the JS re-renders the discount-form
     * region from `applied_code`); classic form posts get the redirect +
     * flash they always had.
     */
    private function discountResponse(Request $request, Cart $cart, string $flash, bool $ok = true): RedirectResponse|JsonResponse
    {
        if ($request->wantsJson()) {
            return response()->json($this->cartState($cart, $flash) + ['ok' => $ok]);
        }
        return redirect('/cart')->with('cart.flash', $flash);
    }

    public function add(Request $request): RedirectResponse|JsonResponse
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
            if ($request->wantsJson()) {
                return response()->json([
                    'ok'    => false,
                    'flash' => __('site.storefront.cart.pick_a_variant'),
                ], 422);
            }

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

        $cart = Cart::forCurrent();
        $cart->add($product->id, $variantId);

        $flashName = $variantId
            ? sprintf('%s — %s', $product->name, $variant->label)
            : $product->name;
        $flash = __('site.storefront.added_to_cart', ['name' => $flashName]);

        // Async path — the slide-out cart drawer adds without navigation.
        if ($request->wantsJson()) {
            return response()->json($this->cartState($cart, $flash));
        }

        return back()->with('cart.flash', $flash);
    }

    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $lineId = (string) $request->route('lineId');
        $quantity = (int) $request->input('quantity', 1);
        $cart = Cart::forCurrent();
        $cart->setQuantity($lineId, $quantity);

        if ($request->wantsJson()) {
            // Tell the client whether this specific line survived (qty 0
            // removes it) so the JS can fade the row out rather than just
            // re-rendering a stale subtotal.
            $stillPresent = collect($cart->items())->contains('line_id', $lineId);
            return response()->json($this->cartState($cart) + ['line_removed' => ! $stillPresent, 'line_id' => $lineId]);
        }

        return redirect('/cart');
    }

    public function remove(Request $request): RedirectResponse|JsonResponse
    {
        $lineId = (string) $request->route('lineId');
        $cart = Cart::forCurrent();
        $cart->remove($lineId);

        if ($request->wantsJson()) {
            return response()->json($this->cartState($cart) + ['line_removed' => true, 'line_id' => $lineId]);
        }

        return redirect('/cart');
    }
}
