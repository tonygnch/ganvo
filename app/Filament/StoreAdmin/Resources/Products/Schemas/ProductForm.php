<?php

namespace App\Filament\StoreAdmin\Resources\Products\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
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

                Section::make('Images')
                    ->description('The primary image shows on cards, in the cart, and as the main image on the product page. Gallery extras appear as thumbnails next to it; the customer can click a thumb to swap it into the main slot.')
                    ->schema([
                        FileUpload::make('image_path')
                            ->label('Primary image')
                            ->image()
                            ->disk('public')
                            ->directory('products')
                            ->maxSize(2048)
                            ->imageEditor()
                            ->columnSpanFull(),

                        // Gallery extras as a Repeater bound to the
                        // gallery() hasMany relation. Each row is one
                        // ProductImage. orderColumn auto-syncs the
                        // repeater's drag order to the sort_order
                        // column so the order in the editor matches
                        // the storefront.
                        Repeater::make('gallery')
                            ->label('Gallery extras')
                            ->relationship()
                            ->orderColumn('sort_order')
                            ->collapsed()
                            ->itemLabel(fn (array $state): ?string => $state['alt_text'] ?? null)
                            ->addActionLabel('Add image')
                            ->reorderableWithDragAndDrop()
                            ->defaultItems(0)
                            ->schema([
                                FileUpload::make('path')
                                    ->label('Image')
                                    ->image()
                                    ->disk('public')
                                    ->directory('products/gallery')
                                    ->maxSize(2048)
                                    ->imageEditor()
                                    ->required(),
                                TextInput::make('alt_text')
                                    ->label('Alt text')
                                    ->maxLength(160)
                                    ->helperText('For screen readers + SEO. Optional but recommended.'),
                            ])
                            ->columnSpanFull(),
                    ]),

                Section::make('Categories')
                    ->description('Pick one or more categories this product belongs to. Customers browse by these on the storefront.')
                    ->schema([
                        Select::make('categories')
                            ->label('')
                            ->multiple()
                            ->relationship(
                                name: 'categories',
                                titleAttribute: 'name',
                                // Scope to the merchant's own categories.
                                modifyQueryUsing: fn ($query) => $query->where('tenant_id', auth()->user()?->tenant_id),
                            )
                            ->preload()
                            ->searchable(),
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
