<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

/**
 * Session-backed shopping cart. Storage shape is an array of lines
 * keyed by a synthetic "line id" string of the form "{productId}:{variantId|0}"
 * — that way a customer can have two distinct cart lines for the same
 * product when they pick different variants. Variant-less products
 * keep the "0" suffix so the key format is uniform.
 */
class Cart
{
    public function __construct(private readonly Tenant $tenant)
    {
    }

    public static function forCurrent(): self
    {
        return new self(app('current_tenant'));
    }

    public function add(int $productId, ?int $variantId = null, int $quantity = 1): void
    {
        $items = $this->rawItems();
        $key = $this->lineKey($productId, $variantId);
        $items[$key] = ($items[$key] ?? 0) + $quantity;
        $this->save($items);
    }

    public function setQuantity(string $lineId, int $quantity): void
    {
        $items = $this->rawItems();
        // Defensive: only operate on keys we know about. Avoids planting
        // arbitrary keys via tampered form input.
        if (! array_key_exists($lineId, $items)) {
            return;
        }
        if ($quantity <= 0) {
            unset($items[$lineId]);
        } else {
            $items[$lineId] = $quantity;
        }
        $this->save($items);
    }

    public function remove(string $lineId): void
    {
        $items = $this->rawItems();
        unset($items[$lineId]);
        $this->save($items);
    }

    public function clear(): void
    {
        Session::forget($this->key());
        Session::forget($this->discountKey());
    }

    /**
     * Hydrate the cart into a Collection of rich rows. Drops lines
     * whose product / variant no longer exists or has been deactivated
     * (silent prune — keeps stale sessions from breaking checkout).
     *
     * @return Collection<int, array{
     *     line_id: string,
     *     product: Product,
     *     variant: ?ProductVariant,
     *     unit_price_cents: int,
     *     quantity: int,
     *     subtotal_cents: int,
     * }>
     */
    public function items(): Collection
    {
        $raw = $this->rawItems();
        if (empty($raw)) {
            return collect();
        }

        // Parse keys back into (productId, variantId) tuples.
        $productIds = [];
        $variantIds = [];
        foreach (array_keys($raw) as $lineId) {
            [$pid, $vid] = $this->parseLineKey($lineId);
            $productIds[$pid] = true;
            if ($vid !== null) {
                $variantIds[$vid] = true;
            }
        }

        $products = Product::where('tenant_id', $this->tenant->id)
            ->whereIn('id', array_keys($productIds))
            ->where('is_active', true)
            ->get()
            ->keyBy('id');

        $variants = empty($variantIds)
            ? collect()
            : ProductVariant::whereIn('id', array_keys($variantIds))
                ->where('is_active', true)
                ->get()
                ->keyBy('id');

        $rows = collect();
        foreach ($raw as $lineId => $qty) {
            [$pid, $vid] = $this->parseLineKey($lineId);
            $product = $products->get($pid);
            if (! $product) {
                continue;
            }
            $variant = $vid ? $variants->get($vid) : null;
            // If the line carried a variant_id but the variant has
            // since been deactivated/deleted, drop the line entirely
            // — falling back to the bare product would lose the
            // customer's selection without warning.
            if ($vid && ! $variant) {
                continue;
            }
            $unit = $variant
                ? (int) ($variant->price_cents ?? $product->price_cents)
                : (int) $product->price_cents;

            $rows->push([
                'line_id' => $lineId,
                'product' => $product,
                'variant' => $variant,
                'unit_price_cents' => $unit,
                'quantity' => $qty,
                'subtotal_cents' => $unit * $qty,
            ]);
        }

        return $rows->values();
    }

    /**
     * Sum of line subtotals (price × qty) — does NOT include discount
     * or shipping. Kept as `totalCents()` for backward compatibility
     * with views/controllers that pre-date the discount engine.
     * subtotalCents() is the canonical name going forward.
     */
    public function totalCents(): int
    {
        return $this->subtotalCents();
    }

    public function subtotalCents(): int
    {
        return $this->items()->sum('subtotal_cents');
    }

    public function itemCount(): int
    {
        return array_sum($this->rawItems());
    }

    public function isEmpty(): bool
    {
        return $this->itemCount() === 0;
    }

    /* -----------------------------------------------------------------
     | Discounts
     |
     | One discount applies at a time (manual code wins, else the best
     | auto). Code is stored in session keyed per tenant so the customer
     | can navigate around without losing it. Resolution + amount-off
     | computation lives in DiscountEngine — Cart just owns the session
     | bit + shipping context.
     ------------------------------------------------------------------*/

    /** Default shipping cents the cart assumes when no explicit value
     *  is passed (e.g. when shown on the cart page before checkout).
     *  Matches the rule the checkout uses: free over €50, else 500c. */
    public function defaultShippingCents(): int
    {
        return $this->subtotalCents() >= 5000 ? 0 : 500;
    }

    /** Set the manually-entered discount code. Pass null/'' to clear. */
    public function applyCode(?string $code): void
    {
        $code = $code ? strtoupper(trim($code)) : null;
        if ($code) {
            Session::put($this->discountKey(), $code);
        } else {
            Session::forget($this->discountKey());
        }
    }

    public function appliedCode(): ?string
    {
        return Session::get($this->discountKey());
    }

    public function removeDiscount(): void
    {
        Session::forget($this->discountKey());
    }

    /**
     * Resolve which discount currently applies to this cart given the
     * caller-provided shipping (defaults to {@see defaultShippingCents()}).
     * Returns null when nothing applies.
     */
    public function appliedDiscount(?int $shippingCents = null): ?\App\Models\Discount
    {
        return \App\Services\DiscountEngine::forCurrent()->resolve(
            $this->appliedCode(),
            $this->subtotalCents(),
            $shippingCents ?? $this->defaultShippingCents()
        );
    }

    public function discountAmountCents(?int $shippingCents = null): int
    {
        $d = $this->appliedDiscount($shippingCents);
        if (! $d) {
            return 0;
        }
        return $d->amountOff(
            $this->subtotalCents(),
            $shippingCents ?? $this->defaultShippingCents()
        );
    }

    /**
     * Currency code the customer is currently viewing prices in. Defaults to
     * the store's base currency when no display preference is set.
     */
    public function displayCurrency(): string
    {
        if (app()->bound('display_currency')) {
            return app('display_currency');
        }
        return strtoupper($this->tenant->store->currency ?? 'EUR');
    }

    /** FX rate from base currency to the customer's display currency. */
    public function displayRate(): float
    {
        return $this->tenant->store->fxRateFor($this->displayCurrency());
    }

    /** Base-currency total converted into the customer's display currency. */
    public function displayTotalCents(): int
    {
        return \App\Services\Money::convert($this->totalCents(), $this->displayRate());
    }

    /** Build a line key from a product + (optional) variant id. */
    public function lineKey(int $productId, ?int $variantId): string
    {
        return $productId . ':' . ($variantId ?: 0);
    }

    /**
     * Split a line key back into [productId, variantId|null]. Returns
     * variantId = null when the suffix is "0" (variant-less line).
     *
     * @return array{0:int, 1:?int}
     */
    private function parseLineKey(string $lineId): array
    {
        $parts = explode(':', $lineId, 2);
        $pid = (int) ($parts[0] ?? 0);
        $vid = (int) ($parts[1] ?? 0);
        return [$pid, $vid > 0 ? $vid : null];
    }

    /** @return array<string,int> [lineKey => quantity] */
    private function rawItems(): array
    {
        $items = Session::get($this->key(), []);
        // Migrate legacy session shape: integer keys ⇒ "productId:0".
        // Keeps old sessions from breaking after the variants rollout.
        if (! empty($items) && is_int(array_key_first($items))) {
            $migrated = [];
            foreach ($items as $pid => $qty) {
                $migrated[$pid . ':0'] = $qty;
            }
            $items = $migrated;
            Session::put($this->key(), $items);
        }
        return $items;
    }

    private function save(array $items): void
    {
        Session::put($this->key(), $items);
    }

    private function key(): string
    {
        return "cart.tenant_{$this->tenant->id}";
    }

    /**
     * Discount session key. Lives at a SIBLING path to {@see key()} —
     * not as a nested child — because Laravel's Session::put uses dot
     * notation for Arr::set, so "cart.tenant_1.discount_code" would
     * write into the cart items array and corrupt array_sum() / array
     * iteration over line keys.
     */
    private function discountKey(): string
    {
        return "cart_discount.tenant_{$this->tenant->id}";
    }
}
