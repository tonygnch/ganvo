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
                Section::make(__('admin.collections.section.identity'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('title')
                            // Explicit even where Filament would derive the
                            // same English word from the attribute — that
                            // derivation never translates.
                            ->label(__('admin.collections.field.title'))
                            ->required()
                            ->maxLength(160)
                            ->live(onBlur: true)
                            // Auto-populate slug from title — but only while
                            // slug is empty, so an explicit override sticks.
                            ->afterStateUpdated(fn ($state, $set, $get) => $get('slug')
                                ? null
                                : $set('slug', Str::slug((string) $state))),
                        TextInput::make('slug')
                            ->label(__('admin.shared.field.slug'))
                            ->required()
                            ->maxLength(160)
                            ->unique(
                                table: 'collections',
                                column: 'slug',
                                ignoreRecord: true,
                                modifyRuleUsing: fn ($rule) => $rule->where('tenant_id', auth()->user()?->tenant_id),
                            )
                            ->helperText(__('admin.collections.help.slug')),
                        Textarea::make('description')
                            ->label(__('admin.shared.field.description'))
                            ->rows(3)
                            ->maxLength(2000)
                            ->columnSpanFull()
                            ->helperText(__('admin.collections.help.description')),
                    ]),

                Section::make(__('admin.collections.section.banner'))
                    ->schema([
                        FileUpload::make('banner_path')
                            ->label(__('admin.collections.field.banner'))
                            ->image()
                            ->disk('public')
                            ->directory('collections')
                            ->maxSize(4096)
                            ->imageEditor()
                            ->helperText(__('admin.collections.help.banner')),
                    ]),

                Section::make(__('admin.collections.section.products'))
                    ->description(__('admin.collections.section_help.products'))
                    ->schema([
                        Select::make('products')
                            ->label(__('admin.collections.field.products'))
                            ->multiple()
                            ->relationship(
                                name: 'products',
                                titleAttribute: 'name',
                                // Scope the picker to the merchant's own products.
                                modifyQueryUsing: fn ($query) => $query->where('tenant_id', auth()->user()?->tenant_id),
                            )
                            ->preload()
                            ->searchable()
                            ->helperText(__('admin.collections.help.products')),
                    ]),

                Section::make(__('admin.collections.section.display'))
                    ->columns(2)
                    ->schema([
                        Toggle::make('is_featured')
                            ->label(__('admin.collections.field.is_featured'))
                            ->default(false)
                            ->helperText(__('admin.collections.help.is_featured')),
                        Toggle::make('is_active')
                            ->label(__('admin.collections.field.is_active'))
                            ->default(true)
                            ->helperText(__('admin.collections.help.is_active')),
                        Toggle::make('show_in_menu')
                            ->label(__('admin.collections.field.show_in_menu'))
                            ->default(true)
                            ->helperText(__('admin.collections.help.show_in_menu')),
                        TextInput::make('sort_order')
                            ->label(__('admin.shared.field.sort_order'))
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->helperText(__('admin.collections.help.sort_order')),
                    ]),
            ]);
    }
}
