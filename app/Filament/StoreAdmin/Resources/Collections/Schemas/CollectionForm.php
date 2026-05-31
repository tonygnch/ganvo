<?php

namespace App\Filament\StoreAdmin\Resources\Collections\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CollectionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identity')
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(160)
                            ->live(onBlur: true)
                            // Auto-populate slug from title — but only while
                            // slug is empty, so an explicit override sticks.
                            ->afterStateUpdated(fn ($state, $set, $get) => $get('slug')
                                ? null
                                : $set('slug', Str::slug((string) $state))),
                        TextInput::make('slug')
                            ->required()
                            ->maxLength(160)
                            ->unique(
                                table: 'collections',
                                column: 'slug',
                                ignoreRecord: true,
                                modifyRuleUsing: fn ($rule) => $rule->where('tenant_id', auth()->user()?->tenant_id),
                            )
                            ->helperText('URL part: /collections/{slug}.'),
                        Textarea::make('description')
                            ->rows(3)
                            ->maxLength(2000)
                            ->columnSpanFull()
                            ->helperText('Optional. Shown on the collection page header.'),
                    ]),

                Section::make('Banner')
                    ->schema([
                        FileUpload::make('banner_path')
                            ->label('Banner image')
                            ->image()
                            ->disk('public')
                            ->directory('collections')
                            ->maxSize(4096)
                            ->imageEditor()
                            ->helperText('Wide rectangle works best (e.g. 1600 × 600). Shown at the top of the collection page; themes can also use it as a strip backdrop on the home.'),
                    ]),

                Section::make('Products')
                    ->description('Pick which products belong to this collection — and use drag-handles in the storefront grid order (set in the next field) to control how they appear. A product can sit in any number of collections.')
                    ->schema([
                        Select::make('products')
                            ->label('Products in this collection')
                            ->multiple()
                            ->relationship(
                                name: 'products',
                                titleAttribute: 'name',
                                // Scope the picker to the merchant's own products.
                                modifyQueryUsing: fn ($query) => $query->where('tenant_id', auth()->user()?->tenant_id),
                            )
                            ->preload()
                            ->searchable()
                            ->helperText('Order of selection sets the storefront order; you can re-order after saving via drag in this picker.'),
                    ]),

                Section::make('Display')
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_featured')
                            ->label('Show as a strip on the homepage')
                            ->default(false)
                            ->helperText('When on, this collection renders as a named row of products on the storefront homepage.'),
                        Toggle::make('is_active')
                            ->label('Visible to customers')
                            ->default(true)
                            ->helperText('When off, the collection and its /collections/{slug} page are hidden — products inside still appear elsewhere.'),
                        Toggle::make('show_in_menu')
                            ->label('Show in header navigation')
                            ->default(true)
                            ->helperText('When the merchant has set up a Collections dropdown in their header menu, this collection appears as a link inside it. Turn off to keep the collection accessible by URL but hide it from the nav.'),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->helperText('Lower numbers appear first when multiple collections are featured on the homepage.'),
                    ]),
            ]);
    }
}
