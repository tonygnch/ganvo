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
                Section::make(__('admin.discounts.section.identity'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('admin.shared.field.name'))
                            ->required()
                            ->maxLength(160)
                            ->helperText(__('admin.discounts.help.name')),
                        TextInput::make('code')
                            ->label(__('admin.discounts.field.code'))
                            ->maxLength(60)
                            ->placeholder(__('admin.discounts.ph.code'))
                            ->helperText(__('admin.discounts.help.code'))
                            ->unique(
                                table: 'discounts',
                                column: 'code',
                                ignoreRecord: true,
                                modifyRuleUsing: fn ($rule) => $rule->where('tenant_id', auth()->user()?->tenant_id),
                            ),
                    ]),

                Section::make(__('admin.discounts.section.mechanics'))
                    ->columns(2)
                    ->schema([
                        Select::make('type')
                            ->label(__('admin.discounts.field.type'))
                            ->required()
                            ->default(Discount::TYPE_PERCENTAGE)
                            ->options([
                                Discount::TYPE_PERCENTAGE   => __('admin.discounts.opt.type_percentage'),
                                Discount::TYPE_FIXED        => __('admin.discounts.opt.type_fixed'),
                                Discount::TYPE_FREE_SHIPPING => __('admin.discounts.opt.type_free_shipping'),
                            ])
                            ->live()
                            ->helperText(__('admin.discounts.help.type')),
                        TextInput::make('value')
                            ->label(__('admin.discounts.field.value'))
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
                                Discount::TYPE_PERCENTAGE => __('admin.discounts.help.value_percentage'),
                                Discount::TYPE_FIXED      => __('admin.discounts.help.value_fixed'),
                                default                   => __('admin.discounts.help.value_free_shipping'),
                            }),
                        TextInput::make('min_subtotal_cents')
                            ->label(__('admin.discounts.field.min_subtotal'))
                            ->numeric()
                            ->minValue(0)
                            ->nullable()
                            ->prefix(fn () => \App\Services\Money::symbol(auth()->user()?->tenant?->store?->currency ?? 'EUR'))
                            ->formatStateUsing(fn ($state) => $state !== null ? number_format($state / 100, 2, '.', '') : null)
                            ->dehydrateStateUsing(fn ($state) => ($state === null || $state === '') ? null : (int) round(((float) $state) * 100))
                            ->step('0.01')
                            ->helperText(__('admin.discounts.help.min_subtotal')),
                    ]),

                Section::make(__('admin.discounts.section.validity'))
                    ->columns(2)
                    ->schema([
                        DateTimePicker::make('starts_at')
                            ->label(__('admin.discounts.field.starts_at'))
                            ->seconds(false)
                            ->nullable()
                            ->helperText(__('admin.discounts.help.starts_at')),
                        DateTimePicker::make('ends_at')
                            ->label(__('admin.discounts.field.ends_at'))
                            ->seconds(false)
                            ->nullable()
                            ->after('starts_at')
                            ->helperText(__('admin.discounts.help.ends_at')),
                        TextInput::make('usage_limit')
                            ->label(__('admin.discounts.field.usage_limit'))
                            ->numeric()
                            ->minValue(0)
                            ->nullable()
                            ->helperText(__('admin.discounts.help.usage_limit')),
                        TextInput::make('per_customer_limit')
                            ->label(__('admin.discounts.field.per_customer_limit'))
                            ->numeric()
                            ->minValue(0)
                            ->nullable()
                            ->helperText(__('admin.discounts.help.per_customer_limit')),
                    ]),

                Section::make(__('admin.discounts.section.behavior'))
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_auto')
                            ->label(__('admin.discounts.field.is_auto'))
                            ->default(false)
                            ->helperText(__('admin.discounts.help.is_auto')),
                        Toggle::make('is_active')
                            ->label(__('admin.shared.field.active'))
                            ->default(true)
                            ->helperText(__('admin.discounts.help.is_active')),
                    ]),
            ]);
    }
}
