<?php

namespace App\Filament\StoreAdmin\Resources\Orders\Pages;

use App\Filament\StoreAdmin\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Notifications\OrderRefunded;
use App\Notifications\OrderShipped;
use App\Services\Payments\StripeConnectService;
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

    /** Carrier slug → display label. Used by both the form select + email. */
    private const CARRIERS = [
        // EU + BG-relevant carriers first since most early merchants are EU.
        'dpd'       => 'DPD',
        'gls'       => 'GLS',
        'dhl'       => 'DHL',
        'postnl'    => 'PostNL',
        'econt'     => 'Econt',
        'speedy'    => 'Speedy',
        // North America
        'ups'       => 'UPS',
        'usps'      => 'USPS',
        'fedex'     => 'FedEx',
        // Catch-all
        'other'     => 'Other',
    ];

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Order')
                ->columns(3)
                ->schema([
                    TextEntry::make('order_number')->label('Order')->weight('bold'),
                    TextEntry::make('status')
                        ->badge()
                        ->formatStateUsing(fn (string $state) => Order::STATUSES[$state] ?? $state)
                        ->color(fn (string $state): string => match ($state) {
                            'paid' => 'success',
                            'shipped' => 'info',
                            'pending' => 'warning',
                            'refunded', 'cancelled' => 'danger',
                            default => 'gray',
                        }),
                    TextEntry::make('total_cents')
                        ->label('Total')
                        ->money(fn (Order $r) => $r->currency)
                        ->state(fn (Order $r) => $r->total_cents / 100),
                    TextEntry::make('created_at')->label('Placed')->dateTime(),
                    TextEntry::make('paid_at')->dateTime()->placeholder('—'),
                    TextEntry::make('shipped_at')->dateTime()->placeholder('—'),
                ]),

            Section::make('Customer')
                ->columns(2)
                ->schema([
                    TextEntry::make('customer_name'),
                    TextEntry::make('customer_email'),
                    TextEntry::make('customer_phone')->placeholder('—'),
                ]),

            Section::make('Shipping address')
                ->visible(fn (Order $r) => ! empty($r->shipping_address))
                ->columns(2)
                ->schema([
                    TextEntry::make('shipping_address.line')->label('Street'),
                    TextEntry::make('shipping_address.region')->label('Region')->placeholder('—'),
                    TextEntry::make('shipping_address.city')->label('City'),
                    TextEntry::make('shipping_address.postal_code')->label('Postal code'),
                    TextEntry::make('shipping_address.country')->label('Country'),
                ]),

            Section::make('Fulfillment')
                ->visible(fn (Order $r) => $r->status === 'shipped' || $r->tracking_number)
                ->columns(3)
                ->schema([
                    TextEntry::make('carrier')
                        ->placeholder('—')
                        ->formatStateUsing(fn (?string $state) => self::CARRIERS[$state] ?? $state),
                    TextEntry::make('tracking_number')->placeholder('—')->copyable(),
                    TextEntry::make('tracking_url')
                        ->placeholder('—')
                        ->url(fn (Order $r) => $r->tracking_url)
                        ->openUrlInNewTab(),
                ]),

            // Payment info — surfaces method + Stripe identifiers +
            // fee/refund snapshot. Critical for support lookups.
            Section::make('Payment')
                ->columns(3)
                ->schema([
                    TextEntry::make('payment_method')
                        ->label('Method')
                        ->badge()
                        ->color(fn (?string $s) => $s === 'stripe' ? 'success' : 'gray')
                        ->formatStateUsing(fn (?string $s) => match ($s) {
                            'stripe' => 'Stripe',
                            'stub'   => 'Demo (stub)',
                            default  => $s ?? '—',
                        }),
                    TextEntry::make('stripe_payment_intent_id')
                        ->label('Payment Intent')
                        ->placeholder('—')
                        ->copyable(),
                    TextEntry::make('stripe_charge_id')
                        ->label('Charge')
                        ->placeholder('—')
                        ->copyable(),
                    TextEntry::make('platform_fee_cents')
                        ->label('Platform fee')
                        ->money(fn (Order $r) => $r->currency)
                        ->state(fn (Order $r) => $r->platform_fee_cents / 100),
                    TextEntry::make('refund_amount_cents')
                        ->label('Refunded')
                        ->visible(fn (Order $r) => $r->refund_amount_cents > 0)
                        ->money(fn (Order $r) => $r->currency)
                        ->state(fn (Order $r) => $r->refund_amount_cents / 100),
                    TextEntry::make('refunded_at')
                        ->dateTime()
                        ->visible(fn (Order $r) => filled($r->refunded_at))
                        ->placeholder('—'),
                ]),

            Section::make('Line items')
                ->schema([
                    RepeatableEntry::make('items')
                        ->hiddenLabel()
                        ->columns(4)
                        ->schema([
                            TextEntry::make('product_name')->columnSpan(2),
                            TextEntry::make('quantity')->label('Qty'),
                            TextEntry::make('subtotal_cents')
                                ->label('Subtotal')
                                ->state(fn ($record) => number_format($record->subtotal_cents / 100, 2) . ' ' . $record->order->currency),
                        ]),
                ]),

            Section::make('Internal notes')
                ->visible(fn (Order $r) => filled($r->notes))
                ->schema([
                    TextEntry::make('notes')->hiddenLabel(),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            // Mark shipped — initial ship action. Sends OrderShipped
            // email. For already-shipped orders, use Edit tracking
            // (below) instead which only updates fields silently.
            Action::make('markShipped')
                ->label('Mark shipped')
                ->icon(Heroicon::OutlinedTruck)
                ->color('info')
                ->visible(fn () => $this->record->isShippable())
                ->schema([
                    Select::make('carrier')
                        ->options(self::CARRIERS)
                        ->required()
                        ->searchable(),
                    TextInput::make('tracking_number')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('tracking_url')
                        ->url()
                        ->placeholder('https://...')
                        ->maxLength(500)
                        ->helperText('Optional. Paste the carrier\'s tracking page URL.'),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => Order::STATUS_SHIPPED,
                        'carrier' => $data['carrier'],
                        'tracking_number' => $data['tracking_number'],
                        'tracking_url' => $data['tracking_url'] ?? null,
                        'shipped_at' => now(),
                    ]);

                    NotificationFacade::route('mail', $this->record->customer_email)
                        ->notify(new OrderShipped($this->record->fresh()));

                    Notification::make()
                        ->success()
                        ->title('Order marked shipped')
                        ->body('Shipping notification sent to ' . $this->record->customer_email)
                        ->send();
                }),

            // Edit tracking — for already-shipped orders. Updates the
            // carrier/number/URL without re-sending the customer email
            // (e.g. fixing a typo, swapping carriers mid-fulfillment).
            Action::make('editTracking')
                ->label('Edit tracking')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->color('gray')
                ->visible(fn () => $this->record->status === Order::STATUS_SHIPPED)
                ->fillForm(fn () => [
                    'carrier' => $this->record->carrier,
                    'tracking_number' => $this->record->tracking_number,
                    'tracking_url' => $this->record->tracking_url,
                ])
                ->schema([
                    Select::make('carrier')->options(self::CARRIERS)->required()->searchable(),
                    TextInput::make('tracking_number')->required()->maxLength(255),
                    TextInput::make('tracking_url')->url()->maxLength(500),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'carrier' => $data['carrier'],
                        'tracking_number' => $data['tracking_number'],
                        'tracking_url' => $data['tracking_url'] ?? null,
                    ]);
                    Notification::make()->success()->title('Tracking updated')->send();
                }),

            // Refund — real Stripe path for stripe orders, local-only
            // flip for stub orders. Supports partial amounts.
            Action::make('refund')
                ->label('Refund')
                ->icon(Heroicon::OutlinedArrowUturnLeft)
                ->color('danger')
                ->visible(fn () => $this->record->isRefundable())
                ->modalDescription(function () {
                    $remaining = $this->record->refundableAmountCents() / 100;
                    $cur = $this->record->currency;
                    return $this->record->isStripePayment()
                        ? "Issues a real Stripe refund — returns the funds to the customer's card and reverses the platform fee proportionally. Up to {$remaining} {$cur} remaining."
                        : "Marks this stub-payment order as refunded. No real transaction is reversed (the original wasn't real).";
                })
                ->schema([
                    TextInput::make('amount')
                        ->label('Amount to refund')
                        ->numeric()
                        ->step('0.01')
                        ->minValue(0.01)
                        ->required()
                        ->default(fn () => number_format($this->record->refundableAmountCents() / 100, 2, '.', ''))
                        ->helperText(fn () => 'Max ' . number_format($this->record->refundableAmountCents() / 100, 2) . ' ' . $this->record->currency . '. Leave at the default for a full refund.'),
                ])
                ->action(function (array $data) {
                    $amountCents = (int) round(((float) $data['amount']) * 100);
                    $remaining = $this->record->refundableAmountCents();

                    if ($amountCents <= 0 || $amountCents > $remaining) {
                        Notification::make()->danger()
                            ->title('Invalid amount')
                            ->body('Amount must be between 0.01 and ' . number_format($remaining / 100, 2) . ' ' . $this->record->currency)
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
                                ->title('Stripe refund failed')
                                ->body($e->getMessage())
                                ->send();
                            return;
                        }
                        Notification::make()->success()
                            ->title('Refund issued')
                            ->body('Stripe is processing — Order will update via webhook shortly.')
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
                        ->title($isFull ? 'Order refunded' : 'Partial refund recorded')
                        ->body('Customer notified.')
                        ->send();
                }),

            Action::make('cancel')
                ->label('Cancel order')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->visible(fn () => $this->record->isCancellable())
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->update([
                        'status' => Order::STATUS_CANCELLED,
                        'cancelled_at' => now(),
                    ]);
                    Notification::make()->warning()->title('Order cancelled')->send();
                }),

            Action::make('addNote')
                ->label('Edit notes')
                ->icon(Heroicon::OutlinedPencilSquare)
                ->color('gray')
                ->fillForm(fn () => ['notes' => $this->record->notes])
                ->schema([
                    Textarea::make('notes')->rows(5)->hiddenLabel(),
                ])
                ->action(function (array $data) {
                    $this->record->update(['notes' => $data['notes'] ?? null]);
                    Notification::make()->success()->title('Notes updated')->send();
                }),
        ];
    }
}
