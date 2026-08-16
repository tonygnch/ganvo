<?php

namespace App\Services;

use App\Models\Discount;
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
    /**
     * Resolved lines for THIS request, or null before the first resolve.
     *
     * items() runs two queries and is now what itemCount() is built on, so the
     * header badge alone would have cost every page two extra round trips.
     * Cleared by save(), so a mutation is never served a stale snapshot.
     */
    private ?Collection $resolved = null;

    public function __construct(private readonly Tenant $tenant) {}

    public static function forCurrent(): self
    {
        return new self(app('current_tenant'));
    }

    /**
     * @param  float|null  $measure  How much the shopper asked for, in the
     *                               product's own unit — 20 for "20 m²". Carried
     *                               to the enquiry unchanged; it does not price
     *                               the line, because a yard quoting by hand is
     *                               the one who decides what 20 m² costs.
     */
    public function add(int $productId, ?int $variantId = null, int $quantity = 1, ?float $measure = null): void
    {
        $items = $this->rawItems();
        $key = $this->lineKey($productId, $variantId);
        $line = $items[$key] ?? ['qty' => 0, 'measure' => null];

        $line['qty'] = (int) $line['qty'] + $quantity;

        // Adding the same size again REPLACES the amount rather than summing
        // it: "I need 20 m²" then "actually 30" is a correction, not thirty
        // more on top of twenty.
        if ($measure !== null && $measure > 0) {
            $line['measure'] = $measure;
        }

        $items[$key] = $line;
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
            $items[$lineId]['qty'] = $quantity;
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
        /*
         | The resolved snapshot has to go with it. This is the one mutation
         | that does not run through save(), so it was the one that could leave
         | itemCount() answering from a basket that no longer exists — emptied
         | in the same request, and still reporting what was in it.
         */
        $this->resolved = null;
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
     *     measure: float|null,
     *     subtotal_cents: int,
     * }>
     */
    public function items(): Collection
    {
        if ($this->resolved !== null) {
            return $this->resolved;
        }

        $raw = $this->rawItems();
        if (empty($raw)) {
            return $this->resolved = collect();
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

        /*
         | Only products that are still BOTH shown and orderable. A merchant who
         | switches ordering off for a board has said it cannot be bought, and a
         | basket someone filled ten minutes earlier is not an exception — the
         | line drops the same way an unpublished product's already does, and
         | items() prunes it from the session below.
         */
        $products = Product::where('tenant_id', $this->tenant->id)
            ->whereIn('id', array_keys($productIds))
            ->where('is_active', true)
            ->where('is_orderable', true)
            ->get()
            ->keyBy('id');

        $variants = empty($variantIds)
            ? collect()
            : ProductVariant::whereIn('id', array_keys($variantIds))
                ->where('is_active', true)
                ->get()
                ->keyBy('id');

        $rows = collect();
        $orphans = [];
        foreach ($raw as $lineId => $line) {
            $qty = (int) $line['qty'];
            $measure = $line['measure'] ?? null;
            [$pid, $vid] = $this->parseLineKey($lineId);
            $product = $products->get($pid);
            if (! $product) {
                $orphans[] = $lineId;

                continue;
            }
            $variant = $vid ? $variants->get($vid) : null;
            // If the line carried a variant_id but the variant has
            // since been deactivated/deleted, drop the line entirely
            // — falling back to the bare product would lose the
            // customer's selection without warning.
            if ($vid && ! $variant) {
                $orphans[] = $lineId;

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
                'measure' => $measure,
                'subtotal_cents' => $unit * $qty,
            ]);
        }

        /*
         | A DROPPED LINE HAS TO LEAVE THE SESSION, not just this list.
         |
         | Lines above are skipped when their product or variant has gone —
         | deactivated, deleted, or replaced when a merchant regenerated a size
         | matrix. Skipping them silently left them in the session for good,
         | where itemCount() still counted them: the bag in the header read 10
         | while the cart page showed nothing, and no amount of shopping could
         | clear it because nothing ever removed the lines.
         |
         | So the read heals the cart. It is a write from a getter, which is
         | usually worth avoiding — but the alternative is a customer stuck with
         | a permanent phantom basket, and only a read can tell that a line has
         | become unresolvable.
         */
        if ($orphans !== []) {
            $this->save(array_diff_key($raw, array_flip($orphans)));
        }

        return $this->resolved = $rows->values();
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

    /**
     * How many things are in the bag — counted from the RESOLVED lines, so the
     * badge in the header can never disagree with the cart page. Counting the
     * raw session instead is what produced "10 in the bag, nothing in the cart".
     */
    public function itemCount(): int
    {
        return (int) $this->items()->sum('quantity');
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
    public function appliedDiscount(?int $shippingCents = null): ?Discount
    {
        return DiscountEngine::forCurrent()->resolve(
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
        return Money::convert($this->totalCents(), $this->displayRate());
    }

    /** Build a line key from a product + (optional) variant id. */
    public function lineKey(int $productId, ?int $variantId): string
    {
        return $productId.':'.($variantId ?: 0);
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
                $migrated[$pid.':0'] = $qty;
            }
            $items = $migrated;
            Session::put($this->key(), $items);
        }

        /*
         | A line used to be a bare integer. It is now ['qty' => n, 'measure' =>
         | f|null], because a shopper buying by area states an amount that has
         | to reach the enquiry. Normalised on every read rather than migrated
         | once: a cart already open in someone's browser is a live session, and
         | it must not start throwing when the shape changes underneath it.
         */
        foreach ($items as $key => $line) {
            if (! is_array($line)) {
                $items[$key] = ['qty' => (int) $line, 'measure' => null];
            } else {
                $items[$key] = [
                    'qty' => (int) ($line['qty'] ?? 0),
                    'measure' => isset($line['measure']) ? (float) $line['measure'] : null,
                ];
            }
        }

        return $items;
    }

    private function save(array $items): void
    {
        Session::put($this->key(), $items);
        // Anything that writes has just invalidated the resolved snapshot.
        $this->resolved = null;
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
