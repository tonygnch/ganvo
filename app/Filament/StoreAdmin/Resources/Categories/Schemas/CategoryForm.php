<?php

namespace App\Filament\StoreAdmin\Resources\Categories\Schemas;

use App\Models\Category;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.categories.section.identity'))
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            // Explicit even though Filament would derive
                            // "Name" from the attribute — that derivation is
                            // English-only, and it is what left the panel
                            // half-translated.
                            ->label(__('admin.shared.field.name'))
                            ->required()
                            ->maxLength(120)
                            ->live(onBlur: true)
                            // Auto-fill slug from name as the operator types,
                            // but only when slug is blank (don't overwrite
                            // an explicit override).
                            ->afterStateUpdated(fn ($state, $set, $get) => $get('slug')
                                ? null
                                : $set('slug', Str::slug((string) $state))),
                        TextInput::make('slug')
                            ->label(__('admin.shared.field.slug'))
                            ->required()
                            ->maxLength(120)
                            ->unique(
                                table: 'categories',
                                column: 'slug',
                                ignoreRecord: true,
                                modifyRuleUsing: fn ($rule) => $rule->where('tenant_id', auth()->user()?->tenant_id),
                            )
                            ->helperText(__('admin.categories.help.slug')),
                        Textarea::make('description')
                            ->label(__('admin.shared.field.description'))
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ]),

                Section::make(__('admin.categories.section.hierarchy'))
                    ->columns(2)
                    ->schema([
                        Select::make('parent_id')
                            ->label(__('admin.categories.field.parent'))
                            ->placeholder(__('admin.categories.ph.parent'))
                            ->options(function ($record) {
                                $query = Category::query()
                                    ->where('tenant_id', auth()->user()?->tenant_id)
                                    ->orderBy('name');
                                // Don't let a category become its own parent
                                // (that'd create a cycle in the tree).
                                if ($record) {
                                    $query->where('id', '!=', $record->id);
                                }
                                return $query->pluck('name', 'id');
                            })
                            ->searchable()
                            ->nullable()
                            ->helperText(__('admin.categories.help.parent')),
                        TextInput::make('sort_order')
                            ->label(__('admin.shared.field.sort_order'))
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->helperText(__('admin.shared.help.sort_order')),
                        FileUpload::make('image_path')
                            ->label(__('admin.categories.field.image'))
                            ->image()
                            ->directory('categories')
                            ->disk('public')
                            ->maxSize(2048)
                            ->columnSpanFull()
                            ->helperText(__('admin.categories.help.image')),
                    ]),

                Section::make(__('admin.shared.section.visibility'))
                    ->schema([
                        Toggle::make('is_active')
                            ->label(__('admin.categories.field.is_active'))
                            ->default(true)
                            ->helperText(__('admin.categories.help.is_active')),
                        Toggle::make('show_in_menu')
                            ->label(__('admin.categories.field.show_in_menu'))
                            ->default(true)
                            ->helperText(__('admin.categories.help.show_in_menu')),
                    ]),
            ]);
    }
}
