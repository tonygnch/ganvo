<?php

namespace App\Filament\SuperAdmin\Resources\Roles\Schemas;

use App\Services\RoleMatrix;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Spatie\Permission\Models\Role;

class RoleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identity')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->label('Role name')
                            ->required()
                            ->maxLength(80)
                            ->helperText('Used in code references. Lowercase with underscores recommended (e.g. content_editor).')
                            // System role names are immutable — code references them.
                            ->disabled(fn (?Role $record) => $record && RoleMatrix::isSystemRole($record->name))
                            ->dehydrated(fn (?Role $record) => ! ($record && RoleMatrix::isSystemRole($record->name)))
                            ->unique(table: 'roles', column: 'name', ignoreRecord: true),
                        Placeholder::make('display_label')
                            ->label('Display label')
                            ->content(fn (?Role $record) => $record
                                ? (RoleMatrix::ROLE_LABELS[$record->name] ?? \Str::headline($record->name))
                                : 'Set the name above; display label is derived from it.')
                            ->visible(fn (?Role $record) => $record !== null),
                    ]),

                Section::make('Permissions')
                    ->description('Pick which sections of the SuperAdmin panel this role can access. Each checkbox is one capability. Super admin always has every permission, even ones added later — editing it here has no effect.')
                    ->schema([
                        self::permissionPicker(),
                    ]),
            ]);
    }

    /**
     * One CheckboxList grouped by section ("Clients", "Billing", ...).
     * Source of truth is RoleMatrix::permissionCatalog(); adding a new
     * permission there makes it show up here automatically.
     */
    private static function permissionPicker(): CheckboxList
    {
        $options = [];
        $descriptions = [];
        foreach (RoleMatrix::permissionCatalog() as $name => $meta) {
            $options[$name] = '[' . $meta['group'] . '] ' . $meta['label'];
            $descriptions[$name] = $meta['description'];
        }

        return CheckboxList::make('permissions')
            ->label('')
            ->options($options)
            ->descriptions($descriptions)
            ->columns(2)
            ->bulkToggleable()
            ->searchable()
            ->disabled(fn (?Role $record) => $record && $record->name === RoleMatrix::SUPER_ADMIN)
            ->helperText(fn (?Role $record) => $record && $record->name === RoleMatrix::SUPER_ADMIN
                ? 'Super admin always has every permission — this checkbox list is informational only.'
                : null)
            // Persist as Spatie role↔permission attachments, not as a JSON
            // column. Hydration pulls the role's current permissions.
            ->formatStateUsing(fn (?Role $record) => $record
                ? $record->permissions->pluck('name')->all()
                : [])
            ->dehydrated(false) // We sync manually in the page's afterSave.
            ->live();
    }
}
