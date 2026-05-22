<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class Cart
{
    public function __construct(private readonly Tenant $tenant)
    {
    }

    public static function forCurrent(): self
    {
        return new self(app('current_tenant'));
    }

    public function add(int $productId, int $quantity = 1): void
    {
        $items = $this->rawItems();
        $items[$productId] = ($items[$productId] ?? 0) + $quantity;
        $this->save($items);
    }

    public function setQuantity(int $productId, int $quantity): void
    {
        $items = $this->rawItems();
        if ($quantity <= 0) {
            unset($items[$productId]);
        } else {
            $items[$productId] = $quantity;
        }
        $this->save($items);
    }

    public function remove(int $productId): void
    {
        $items = $this->rawItems();
        unset($items[$productId]);
        $this->save($items);
    }

    public function clear(): void
    {
        Session::forget($this->key());
    }

    /**
     * @return Collection<int, array{product: Product, quantity: int, subtotal_cents: int}>
     */
    public function items(): Collection
    {
        $raw = $this->rawItems();
        if (empty($raw)) {
            return collect();
        }

        return Product::where('tenant_id', $this->tenant->id)
            ->whereIn('id', array_keys($raw))
            ->where('is_active', true)
            ->get()
            ->map(fn (Product $p) => [
                'product' => $p,
                'quantity' => $raw[$p->id],
                'subtotal_cents' => $p->price_cents * $raw[$p->id],
            ])
            ->values();
    }

    public function totalCents(): int
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

    /** @return array<int,int> [productId => quantity] */
    private function rawItems(): array
    {
        return Session::get($this->key(), []);
    }

    private function save(array $items): void
    {
        Session::put($this->key(), $items);
    }

    private function key(): string
    {
        return "cart.tenant_{$this->tenant->id}";
    }
}
