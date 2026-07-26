<?php

namespace App\Filament\StoreAdmin\Resources\Orders\Pages;

use App\Filament\StoreAdmin\Resources\Orders\OrderResource;
use App\Filament\StoreAdmin\Resources\Orders\Schemas\OrderForm;
use App\Models\Order;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    public function getTitle(): string
    {
        return __('admin.orders.title.edit', ['number' => $this->getRecord()->order_number]);
    }

    protected function getHeaderActions(): array
    {
        return [ViewAction::make()];
    }

    protected function getRedirectUrl(): ?string
    {
        // Back to the order, not to the list: editing an order is almost always
        // one step in working it, and the next thing wanted is to look at what
        // was just changed.
        return OrderResource::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title(__('admin.orders.notify.saved'));
    }

    /**
     * Stamp the timestamp that goes with a status the order has just reached.
     *
     * Only ever FILLS a null one — moving an order backwards (a mistaken
     * "shipped" corrected to "paid") must not erase the record of when it was
     * paid. The dates are history, not a mirror of the current status.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $order = $this->getRecord();
        $now = now();

        $stampFor = [
            Order::STATUS_PAID => 'paid_at',
            Order::STATUS_SHIPPED => 'shipped_at',
            Order::STATUS_CANCELLED => 'cancelled_at',
            Order::STATUS_REFUNDED => 'refunded_at',
        ];

        $column = $stampFor[$data['status'] ?? ''] ?? null;

        if ($column && $order->{$column} === null) {
            $data[$column] = $now;
        }

        return $data;
    }

    /**
     * Recompute the money AFTER the line items have been written.
     *
     * It has to be afterSave: the items are a relationship repeater, so during
     * mutateFormDataBeforeSave the new quantities do not exist in the database
     * yet and summing there would bank the totals from before the edit.
     */
    protected function afterSave(): void
    {
        $order = $this->getRecord();

        if (OrderForm::isSettled($order)) {
            // Paid: the amounts are a record of a real charge and the form kept
            // them read-only, so there is nothing to recompute and recomputing
            // anyway could only introduce drift.
            return;
        }

        $before = (int) $order->getOriginal('total_cents');

        $itemsTotal = (int) $order->items()->sum('subtotal_cents');
        $total = max(0, $itemsTotal
            + (int) $order->shipping_cents
            - (int) $order->discount_amount_cents);

        $order->total_cents = $total;

        /*
         | display_total_cents is the same total in whatever currency the
         | customer was shopping in. There is no stored exchange rate to redo
         | the conversion with, so the ORIGINAL order's own ratio is reused —
         | which is the rate that actually applied when it was placed, and
         | therefore the right one to keep using for it.
         */
        if ($order->display_currency && $order->display_currency !== $order->currency) {
            $ratio = ($before > 0 && $order->display_total_cents)
                ? $order->display_total_cents / $before
                : null;

            if ($ratio) {
                $order->display_total_cents = (int) round($total * $ratio);
            }
        } else {
            $order->display_total_cents = $total;
        }

        $order->saveQuietly();
    }
}
