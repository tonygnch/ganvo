<?php

namespace App\Filament\StoreAdmin\Resources\Discounts\Schemas;

use App\Models\Discount;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DiscountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identity')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(160)
                            ->helperText('Internal label + what customers see on the receipt.'),
                        TextInput::make('code')
                            ->maxLength(60)
                            ->placeholder('e.g. SUMMER10')
                            ->helperText('Customer-entered code. Leave blank for an auto-discount.')
                            ->unique(
                                table: 'discounts',
                                column: 'code',
                                ignoreRecord: true,
                                modifyRuleUsing: fn ($rule) => $rule->where('tenant_id', auth()->user()?->tenant_id),
                            ),
                    ]),

                Section::make('Discount mechanics')
                    ->columns(2)
                    ->schema([
                        Select::make('type')
                            ->required()
                            ->default(Discount::TYPE_PERCENTAGE)
                            ->options([
                                Discount::TYPE_PERCENTAGE   => 'Percentage off subtotal',
                                Discount::TYPE_FIXED        => 'Fixed amount off subtotal',
                                Discount::TYPE_FREE_SHIPPING => 'Free shipping',
                            ])
                            ->live()
                            ->helperText('Choose how the discount calculates.'),
                        TextInput::make('value')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->required(fn ($get) => $get('type') !== Discount::TYPE_FREE_SHIPPING)
                            ->disabled(fn ($get) => $get('type') === Discount::TYPE_FREE_SHIPPING)
                            ->dehydrateStateUsing(function ($state, $get) {
                                // 'fixed' stores cents; convert from major-unit input.
                                if ($get('type') === Discount::TYPE_FIXED) {
                                    return (int) round(((float) $state) * 100);
                                }
                                // Percentage stays as 0–100 integer; free_shipping uses 0.
                                return (int) $state;
                            })
                            ->formatStateUsing(function ($state, $get) {
                                if ($get('type') === Discount::TYPE_FIXED) {
                                    return $state !== null ? number_format($state / 100, 2, '.', '') : null;
                                }
                                return $state;
                            })
                            ->step(fn ($get) => $get('type') === Discount::TYPE_FIXED ? '0.01' : '1')
                            ->suffix(fn ($get) => $get('type') === Discount::TYPE_PERCENTAGE ? '%' : null)
                            ->prefix(fn ($get) => $get('type') === Discount::TYPE_FIXED
                                ? \App\Services\Money::symbol(auth()->user()?->tenant?->store?->currency ?? 'EUR')
                                : null)
                            ->helperText(fn ($get) => match ($get('type')) {
                                Discount::TYPE_PERCENTAGE => '0–100 percent off the subtotal.',
                                Discount::TYPE_FIXED      => 'Amount in your store currency.',
                                default                   => 'Not applicable for free shipping.',
                            }),
                        TextInput::make('min_subtotal_cents')
                            ->label('Minimum subtotal')
                            ->numeric()
                            ->minValue(0)
                            ->nullable()
                            ->prefix(fn () => \App\Services\Money::symbol(auth()->user()?->tenant?->store?->currency ?? 'EUR'))
                            ->formatStateUsing(fn ($state) => $state !== null ? number_format($state / 100, 2, '.', '') : null)
                            ->dehydrateStateUsing(fn ($state) => ($state === null || $state === '') ? null : (int) round(((float) $state) * 100))
                            ->step('0.01')
                            ->helperText('Cart subtotal must reach this for the discount to apply. Leave blank for no minimum.'),
                    ]),

                Section::make('Validity & limits')
                    ->columns(2)
                    ->schema([
                        DateTimePicker::make('starts_at')
                            ->seconds(false)
                            ->nullable()
                            ->helperText('Earliest moment the discount can be used. Blank = always live.'),
                        DateTimePicker::make('ends_at')
                            ->seconds(false)
                            ->nullable()
                            ->after('starts_at')
                            ->helperText('Last moment the discount can be used. Blank = open-ended.'),
                        TextInput::make('usage_limit')
                            ->label('Total usage limit')
                            ->numeric()
                            ->minValue(0)
                            ->nullable()
                            ->helperText('Cap across all customers. Blank = unlimited.'),
                        TextInput::make('per_customer_limit')
                            ->label('Per-customer limit')
                            ->numeric()
                            ->minValue(0)
                            ->nullable()
                            ->helperText('Cap per logged-in customer. Blank = unlimited. Ignored for guest checkout.'),
                    ]),

                Section::make('Behavior')
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_auto')
                            ->label('Apply automatically (no code needed)')
                            ->default(false)
                            ->helperText('When on, the discount applies silently whenever conditions match — customer does not type a code.'),
                        Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Turn off to retire without deleting.'),
                    ]),
            ]);
    }
}
