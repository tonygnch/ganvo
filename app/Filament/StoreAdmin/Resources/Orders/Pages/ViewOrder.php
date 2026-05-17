<?php

namespace App\Filament\StoreAdmin\Resources\Orders\Pages;

use App\Filament\StoreAdmin\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Notifications\OrderShipped;
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

class ViewOrder extends ViewRecord
{
    protected static string $resource = OrderResource::class;

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
                ]),

            Section::make('Shipping address')
                ->visible(fn (Order $r) => ! empty($r->shipping_address))
                ->columns(2)
                ->schema([
                    TextEntry::make('shipping_address.line')->label('Street'),
                    TextEntry::make('shipping_address.city')->label('City'),
                    TextEntry::make('shipping_address.postal_code')->label('Postal code'),
                    TextEntry::make('shipping_address.country')->label('Country'),
                ]),

            Section::make('Fulfillment')
                ->visible(fn (Order $r) => $r->status === 'shipped' || $r->tracking_number)
                ->columns(3)
                ->schema([
                    TextEntry::make('carrier')->placeholder('—'),
                    TextEntry::make('tracking_number')->placeholder('—')->copyable(),
                    TextEntry::make('tracking_url')
                        ->placeholder('—')
                        ->url(fn (Order $r) => $r->tracking_url)
                        ->openUrlInNewTab(),
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
            Action::make('markShipped')
                ->label('Mark shipped')
                ->icon(Heroicon::OutlinedTruck)
                ->color('info')
                ->visible(fn () => $this->record->isShippable())
                ->schema([
                    Select::make('carrier')
                        ->options([
                            'usps' => 'USPS',
                            'ups' => 'UPS',
                            'fedex' => 'FedEx',
                            'dhl' => 'DHL',
                            'other' => 'Other',
                        ])
                        ->required(),
                    TextInput::make('tracking_number')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('tracking_url')
                        ->url()
                        ->placeholder('https://...')
                        ->maxLength(255),
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

            Action::make('refund')
                ->label('Refund')
                ->icon(Heroicon::OutlinedArrowUturnLeft)
                ->color('danger')
                ->visible(fn () => $this->record->isRefundable())
                ->requiresConfirmation()
                ->modalDescription('Mark this order as refunded. (No real refund is issued — payments are stubbed.)')
                ->action(function () {
                    $this->record->update([
                        'status' => Order::STATUS_REFUNDED,
                        'refunded_at' => now(),
                    ]);
                    Notification::make()->warning()->title('Order refunded')->send();
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
