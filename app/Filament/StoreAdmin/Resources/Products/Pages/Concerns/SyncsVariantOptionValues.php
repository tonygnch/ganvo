<?php

namespace App\Filament\StoreAdmin\Resources\Products\Pages\Concerns;

use App\Filament\StoreAdmin\Resources\Products\Schemas\ProductForm;
use App\Models\Product;
use Illuminate\Support\Arr;

/**
 * Filament repeaters save columns, not pivots. The per-axis Selects on a
 * combination row therefore never reach the database on their own — this
 * replays them into product_variant_option_values once the variants exist.
 *
 * The rows have to be grabbed BEFORE the save: on edit, Filament re-hydrates
 * the repeater from the database the instant it has finished saving
 * relationships, at which point the Selects read back whatever the pivot said
 * a moment ago — precisely the thing we are here to overwrite.
 */
trait SyncsVariantOptionValues
{
    /** @var array<int, array<string, mixed>>|null */
    protected ?array $capturedVariantRows = null;

    protected function beforeCreate(): void
    {
        $this->captureVariantRows();
    }

    protected function beforeSave(): void
    {
        $this->captureVariantRows();
    }

    protected function afterCreate(): void
    {
        $this->syncVariantOptionValues();
    }

    protected function afterSave(): void
    {
        $this->syncVariantOptionValues();
    }

    protected function captureVariantRows(): void
    {
        $state = $this->form->getRawState();

        $this->capturedVariantRows = array_values(Arr::wrap($state['variants'] ?? []));
    }

    protected function syncVariantOptionValues(): void
    {
        $product = $this->getRecord();

        if (! $product instanceof Product || $this->capturedVariantRows === null) {
            return;
        }

        // Re-read the axes as they stand AFTER this save: an option the
        // merchant deleted in the same submit must not leave us pointing a
        // pivot row at a value that no longer exists.
        $optionOfValue = [];

        foreach ($product->options()->with('values')->get() as $option) {
            foreach ($option->values as $value) {
                $optionOfValue[(int) $value->id] = (int) $option->id;
            }
        }

        if (! $optionOfValue) {
            return;
        }

        // The repeater numbers sort_order from the row order it just wrote, so
        // position is the link back from a saved variant to the row that
        // produced it — new rows are still keyed by a browser-side uuid here.
        $variants = $product->variants()->orderBy('sort_order')->get()->keyBy('sort_order');

        foreach ($this->capturedVariantRows as $index => $row) {
            $selection = ProductForm::selectedValueIds(Arr::wrap($row));

            // No axis fields on the row at all: a flat variant, nothing pinned.
            // Never sync([]) here — that would detach a pairing the form simply
            // was not rendering.
            if (! $selection) {
                continue;
            }

            $variant = $variants[$index + 1] ?? null;

            if (! $variant) {
                continue;
            }

            $pivot = [];

            foreach ($selection as $optionId => $valueId) {
                if (($optionOfValue[$valueId] ?? null) !== $optionId) {
                    continue;
                }

                $pivot[$valueId] = ['product_option_id' => $optionId];
            }

            $variant->optionValues()->sync($pivot);
        }
    }
}
