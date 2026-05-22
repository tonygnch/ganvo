<?php

namespace App\Filament\StoreAdmin\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Details')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, $set, $get) => $get('slug')
                                ? null
                                : $set('slug', Str::slug((string) $state))),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->helperText('URL fragment. Auto-filled from name.'),
                        Textarea::make('description')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                Section::make('Pricing & inventory')
                    ->description('Prices are in your store\'s base currency, set in Store Settings.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('price_cents')
                            ->label('Price')
                            ->required()
                            ->numeric()
                            ->step('0.01')
                            ->prefix(fn () => \App\Services\Money::symbol(
                                auth()->user()?->tenant?->store?->currency ?? 'EUR'
                            ))
                            ->formatStateUsing(fn ($state) => $state !== null ? number_format($state / 100, 2, '.', '') : null)
                            ->dehydrateStateUsing(fn ($state) => (int) round(((float) $state) * 100)),
                        TextInput::make('stock_quantity')
                            ->required()
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                    ]),

                Section::make('Image')
                    ->schema([
                        FileUpload::make('image_path')
                            ->label('Product image')
                            ->image()
                            ->disk('public')
                            ->directory('products')
                            ->maxSize(2048)
                            ->imageEditor()
                            ->columnSpanFull(),
                    ]),

                Section::make('Visibility')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Visible in storefront')
                            ->default(true),
                    ]),
            ]);
    }
}
