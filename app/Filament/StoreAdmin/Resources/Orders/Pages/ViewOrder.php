<?php

namespace App\Filament\StoreAdmin\Resources\Orders\Pages;

use App\Filament\StoreAdmin\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Notifications\OrderRefunded;
use App\Notifications\OrderShipped;
use App\Services\Payments\StripeConnectService;
use App\Services\Shipping\CarrierRegistry;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Stripe\Exception\ApiErrorException;

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

    // Carrier metadata lives in App\Services\Shipping\CarrierRegistry
    // so admin + email + storefront all read from the same source.
    // Add carriers there and they appear here automatically.

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.orders.section.order'))
                ->columns(3)
                ->schema([
                    TextEntry::make('order_number')->label(__('admin.orders.field.order_number'))->weight('bold'),
                    TextEntry::make('status')
                        ->label(__('admin.orders.field.status'))
                        ->badge()
                        ->formatStateUsing(fn (string $state) => Order::statusOptions()[$state] ?? $state)
                        ->color(fn (string $state): string => match ($state) {
                            'paid' => 'success',
                            'shipped' => 'info',
                            'pending' => 'warning',
                            'refunded', 'cancelled' => 'danger',
                            default => 'gray',
                        }),
                    TextEntry::make('total_cents')
                        ->label(__('admin.orders.field.total'))
                        ->money(fn (Order $r) => $r->currency)
                        ->state(fn (Order $r) => $r->total_cents / 100),
                    TextEntry::make('created_at')->label(__('admin.orders.field.placed'))->dateTime(),
                    TextEntry::make('paid_at')->label(__('admin.orders.field.paid_at'))->dateTime()->placeholder('—'),
                    TextEntry::make('shipped_at')->label(__('admin.orders.field.shipped_at'))->dateTime()->placeholder('—'),
                ]),

            Section::make(__('admin.orders.section.customer'))
                ->columns(2)
                ->schema([
                    TextEntry::make('customer_name')->label(__('admin.shared.field.name')),
                    TextEntry::make('customer_email')->label(__('admin.orders.field.customer_email')),
                    TextEntry::make('customer_phone')->label(__('admin.orders.field.customer_phone'))->placeholder('—'),
                ]),

            Section::make(__('admin.orders.section.shipping_address'))
                ->visible(fn (Order $r) => ! empty($r->shipping_address))
                ->columns(2)
                ->schema([
                    TextEntry::make('shipping_address.line')->label(__('admin.orders.field.street')),
                    TextEntry::make('shipping_address.region')->label(__('admin.orders.field.region'))->placeholder('—'),
                    TextEntry::make('shipping_address.city')->label(__('admin.orders.field.city')),
                    TextEntry::make('shipping_address.postal_code')->label(__('admin.orders.field.postal_code')),
                    TextEntry::make('shipping_address.country')->label(__('admin.orders.field.country')),
                ]),

            Section::make(__('admin.orders.section.fulfillment'))
                ->visible(fn (Order $r) => $r->status === 'shipped' || $r->tracking_number)
                ->columns(3)
                ->schema([
                    TextEntry::make('carrier')
                        ->label(__('admin.orders.field.carrier'))
                        ->placeholder('—')
                        ->formatStateUsing(fn (?string $state) => CarrierRegistry::label($state)),
                    TextEntry::make('tracking_number')->label(__('admin.orders.field.tracking_number'))->placeholder('—')->copyable(),
                    TextEntry::make('tracking_url')
                        ->label(__('admin.orders.field.tracking_url'))
                        ->placeholder('—')
                        ->url(fn (Order $r) => $r->tracking_url)
                        ->openUrlInNewTab(),
                ]),

            // Payment info — surfaces method + Stripe identifiers +
            // fee/refund snapshot. Critical for support lookups.
            Section::make(__('admin.orders.section.payment'))
                ->columns(3)
                ->schema([
                    TextEntry::make('payment_method')
                        ->label(__('admin.orders.field.payment_method'))
                        ->badge()
                        ->color(fn (?string $s) => $s === 'stripe' ? 'success' : 'gray')
                        ->formatStateUsing(fn (?string $s) => Order::paymentMethodOptions()[$s] ?? ($s ?? '—')),
                    TextEntry::make('stripe_payment_intent_id')
                        ->label(__('admin.orders.field.payment_intent'))
                        ->placeholder('—')
                        ->copyable(),
                    TextEntry::make('stripe_charge_id')
                        ->label(__('admin.orders.field.charge'))
                        ->placeholder('—')
                        ->copyable(),
                    TextEntry::make('platform_fee_cents')
                        ->label(__('admin.orders.field.platform_fee'))
                        ->money(fn (Order $r) => $r->currency)
                        ->state(fn (Order $r) => $r->platform_fee_cents / 100),
                    TextEntry::make('refund_amount_cents')
                        ->label(__('admin.orders.field.refunded_amount'))
                        ->visible(fn (Order $r) => $r->refund_amount_cents > 0)
                        ->money(fn (Order $r) => $r->currency)
                        ->state(fn (Order $r) => $r->refund_amount_cents / 100),
                    TextEntry::make('refunded_at')
                        ->label(__('admin.orders.field.refunded_at'))
                        ->dateTime()
                        ->visible(fn (Order $r) => filled($r->refunded_at))
                        ->placeholder('—'),
                ]),

            Section::make(__('admin.orders.section.line_items'))
                ->schema([
                    RepeatableEntry::make('items')
                        ->hiddenLabel()
                        ->columns(4)
                        ->schema([
                            TextEntry::make('product_name')->label(__('admin.orders.field.product'))->columnSpan(2),
                            TextEntry::make('quantity')->label(__('admin.orders.field.qty')),
                            TextEntry::make('subtotal_cents')
                                ->label(__('admin.orders.field.subtotal'))
                                ->state(fn ($record) => number_format($record->subtotal_cents / 100, 2) . ' ' . $record->order->currency),
                        ]),
                ]),

            Section::make(__('admin.orders.section.notes'))
                ->visible(fn (Order $r) => filled($r->notes))
                ->schema([
                    TextEntry::make('notes')->hiddenLabel(),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\EditAction::make(),
            // Mark shipped — initial ship action. Sends OrderShipped
            // email. For already-shipped orders, use Edit tracking
            // (below) instead which only updates fields silently.
            Action::make('markShipped')
                ->label(__('admin.orders.action.mark_shipped'))
                ->icon(Heroicon::OutlinedTruck)
                ->color('info')
                ->visible(fn () => $this->record->isShippable())
                ->schema([
                    Select::make('carrier')
                        ->label(__('admin.orders.field.carrier'))
                        ->options(CarrierRegistry::options())
                        ->required()
                        ->searchable(),
                    TextInput::make('tracking_number')
                        ->label(__('admin.orders.field.tracking_number'))
                        ->required()
                        ->maxLength(255),
                    TextInput::make('tracking_url')
                        ->label(__('admin.orders.field.tracking_url'))
                        ->url()
                        ->placeholder(__('admin.orders.ph.tracking_url'))
                        ->maxLength(500)
                        ->helperText(__('admin.orders.help.tracking_url')),
                ])
                ->action(function (array $data) {
                    // Auto-generate the URL from the registry when the
                    // operator didn't paste one. Falls back to null for
                    // carriers without a public tracking page (e.g. "Other").
                    $url = filled($data['tracking_url'] ?? null)
                        ? $data['tracking_url']
                        : CarrierRegistry::trackingUrlFor($data['carrier'], $data['tracking_number']);

                    $this->record->update([
                        'status' => Order::STATUS_SHIPPED,
                        'carrier' => $data['carrier'],
                        'tracking_number' => $data['tracking_number'],
                        'tracking_url' => $url,
                        'shipped_at' => now(),
                    ]);

                    NotificationFacade::route('mail', $this->record->customer_email)
                        ->notify(new OrderShipped($this->record->fresh()));

                    Notification::make()
                        ->success()
                        ->title(__('admin.orders.notify.shipped_title'))
                        ->body(__('admin.orders.notify.shipped_body', ['email' => $this->record->customer_email]))
                        ->send();
                }),

            // Edit tracking — for already-shipped orders. Updates the
            // carrier/number/URL without re-sending the customer email
            // (e.g. fixing a typo, swapping carriers mid-fulfillment).
            Action::make('editTracking')
                ->label(__('admin.orders.action.edit_tracking'))
                ->icon(Heroicon::OutlinedPencilSquare)
                ->color('gray')
                ->visible(fn () => $this->record->status === Order::STATUS_SHIPPED)
                ->fillForm(fn () => [
                    'carrier' => $this->record->carrier,
                    'tracking_number' => $this->record->tracking_number,
                    'tracking_url' => $this->record->tracking_url,
                ])
                ->schema([
                    Select::make('carrier')->label(__('admin.orders.field.carrier'))->options(CarrierRegistry::options())->required()->searchable(),
                    TextInput::make('tracking_number')->label(__('admin.orders.field.tracking_number'))->required()->maxLength(255),
                    TextInput::make('tracking_url')
                        ->label(__('admin.orders.field.tracking_url'))
                        ->url()
                        ->maxLength(500)
                        ->helperText(__('admin.orders.help.tracking_url_edit')),
                ])
                ->action(function (array $data) {
                    $url = filled($data['tracking_url'] ?? null)
                        ? $data['tracking_url']
                        : CarrierRegistry::trackingUrlFor($data['carrier'], $data['tracking_number']);
                    $this->record->update([
                        'carrier' => $data['carrier'],
                        'tracking_number' => $data['tracking_number'],
                        'tracking_url' => $url,
                    ]);
                    Notification::make()->success()->title(__('admin.orders.notify.tracking_updated'))->send();
                }),

            // Refund — real Stripe path for stripe orders, local-only
            // flip for stub orders. Supports partial amounts.
            Action::make('refund')
                ->label(__('admin.orders.action.refund'))
                ->icon(Heroicon::OutlinedArrowUturnLeft)
                ->color('danger')
                ->visible(fn () => $this->record->isRefundable())
                ->modalDescription(function () {
                    $remaining = $this->record->refundableAmountCents() / 100;
                    $cur = $this->record->currency;
                    return $this->record->isStripePayment()
                        ? __('admin.orders.help.refund_stripe', ['amount' => $remaining, 'currency' => $cur])
                        : __('admin.orders.help.refund_stub');
                })
                ->schema([
                    TextInput::make('amount')
                        ->label(__('admin.orders.field.refund_amount'))
                        ->numeric()
                        ->step('0.01')
                        ->minValue(0.01)
                        ->required()
                        ->default(fn () => number_format($this->record->refundableAmountCents() / 100, 2, '.', ''))
                        ->helperText(fn () => __('admin.orders.help.refund_amount', [
                            'amount' => number_format($this->record->refundableAmountCents() / 100, 2),
                            'currency' => $this->record->currency,
                        ])),
                ])
                ->action(function (array $data) {
                    $amountCents = (int) round(((float) $data['amount']) * 100);
                    $remaining = $this->record->refundableAmountCents();

                    if ($amountCents <= 0 || $amountCents > $remaining) {
                        Notification::make()->danger()
                            ->title(__('admin.orders.notify.invalid_amount_title'))
                            ->body(__('admin.orders.notify.invalid_amount_body', [
                                'max' => number_format($remaining / 100, 2),
                                'currency' => $this->record->currency,
                            ]))
                            ->send();
                        return;
                    }

                    if ($this->record->isStripePayment()) {
                        // Real refund path — Stripe handles the money
                        // movement + the platform fee reversal. The
                        // charge.refunded webhook updates the Order
                        // (status / refund_amount_cents / platform_fee_cents)
                        // and sends OrderRefunded — so we don't update
                        // anything locally here.
                        try {
                            app(StripeConnectService::class)->refundCharge($this->record, $amountCents);
                        } catch (ApiErrorException $e) {
                            Notification::make()->danger()
                                ->title(__('admin.orders.notify.refund_failed_title'))
                                ->body($e->getMessage())
                                ->send();
                            return;
                        }
                        Notification::make()->success()
                            ->title(__('admin.orders.notify.refund_issued_title'))
                            ->body(__('admin.orders.notify.refund_issued_body'))
                            ->send();
                        return;
                    }

                    // Stub path: no Stripe call, just record the refund
                    // + flip status when fully refunded. Notify the
                    // customer ourselves since no webhook fires.
                    $newTotalRefunded = (int) $this->record->refund_amount_cents + $amountCents;
                    $isFull = $newTotalRefunded >= (int) $this->record->total_cents;
                    $this->record->update([
                        'refund_amount_cents' => $newTotalRefunded,
                        'status' => $isFull ? Order::STATUS_REFUNDED : $this->record->status,
                        'refunded_at' => $isFull ? ($this->record->refunded_at ?? now()) : $this->record->refunded_at,
                    ]);
                    NotificationFacade::route('mail', $this->record->customer_email)
                        ->notify(new OrderRefunded($this->record->fresh(), $amountCents));
                    Notification::make()->success()
                        ->title($isFull
                            ? __('admin.orders.notify.refunded_full_title')
                            : __('admin.orders.notify.refunded_partial_title'))
                        ->body(__('admin.orders.notify.customer_notified'))
                        ->send();
                }),

            Action::make('cancel')
                ->label(__('admin.orders.action.cancel'))
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->visible(fn () => $this->record->isCancellable())
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update([
                        'status' => Order::STATUS_CANCELLED,
                        'cancelled_at' => now(),
                    ]);
                    Notification::make()->warning()->title(__('admin.orders.notify.cancelled_title'))->send();
                }),

            Action::make('addNote')
                ->label(__('admin.orders.action.edit_notes'))
                ->icon(Heroicon::OutlinedPencilSquare)
                ->color('gray')
                ->fillForm(fn () => ['notes' => $this->record->notes])
                ->schema([
                    Textarea::make('notes')->rows(5)->hiddenLabel(),
                ])
                ->action(function (array $data) {
                    $this->record->update(['notes' => $data['notes'] ?? null]);
                    Notification::make()->success()->title(__('admin.orders.notify.notes_updated'))->send();
                }),
        ];
    }
}
