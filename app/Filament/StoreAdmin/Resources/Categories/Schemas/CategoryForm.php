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
                Section::make('Identity')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
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
                            ->required()
                            ->maxLength(120)
                            ->unique(
                                table: 'categories',
                                column: 'slug',
                                ignoreRecord: true,
                                modifyRuleUsing: fn ($rule) => $rule->where('tenant_id', auth()->user()?->tenant_id),
                            )
                            ->helperText('URL part: /categories/{slug}. Lowercase letters, numbers, dashes.'),
                        Textarea::make('description')
                            ->rows(3)
                            ->maxLength(1000)
                            ->columnSpanFull(),
                    ]),

                Section::make('Hierarchy + display')
                    ->columns(2)
                    ->schema([
                        Select::make('parent_id')
                            ->label('Parent category')
                            ->placeholder('— root —')
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
                            ->helperText('Leave empty for a top-level category.'),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->helperText('Lower numbers appear first.'),
                        FileUpload::make('image_path')
                            ->label('Category image')
                            ->image()
                            ->directory('categories')
                            ->disk('public')
                            ->maxSize(2048)
                            ->columnSpanFull()
                            ->helperText('Optional. Shown on the category card + page header.'),
                    ]),

                Section::make('Visibility')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Visible in storefront')
                            ->default(true)
                            ->helperText('When off, the category is hidden from the storefront but products inside it still appear if assigned to other visible categories or queried directly.'),
                    ]),
            ]);
    }
}
