<?php

namespace App\Filament\SuperAdmin\Resources\Roles\Tables;

use App\Services\RoleMatrix;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Spatie\Permission\Models\Role;

class RolesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('name')
            ->columns([
                TextColumn::make('display_name')
                    ->label('Role')
                    ->state(fn (Role $r) => RoleMatrix::ROLE_LABELS[$r->name] ?? \Str::headline($r->name))
                    ->description(fn (Role $r) => $r->name)
                    ->searchable(query: fn ($query, string $search) => $query->where('name', 'like', "%{$search}%"))
                    ->weight('bold'),
                IconColumn::make('system')
                    ->label('System')
                    ->boolean()
                    ->trueIcon('heroicon-o-lock-closed')
                    ->falseIcon('heroicon-o-pencil')
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->tooltip(fn (Role $r) => RoleMatrix::isSystemRole($r->name)
                        ? 'System role — can\'t be renamed or deleted, but permissions are editable.'
                        : 'Custom role — fully editable.')
                    ->getStateUsing(fn (Role $r) => RoleMatrix::isSystemRole($r->name)),
                TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->counts('permissions')
                    ->badge()
                    ->color(fn ($state) => $state >= count(RoleMatrix::permissionCatalog()) ? 'success' : 'info'),
                TextColumn::make('users_count')
                    ->label('Admins')
                    ->counts('users')
                    ->badge()
                    ->color('gray'),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->visible(fn (Role $r) => ! RoleMatrix::isSystemRole($r->name))
                    ->before(function (Role $record) {
                        if ($record->users()->exists()) {
                            throw new \RuntimeException(
                                "Can't delete a role that's still assigned to users. Reassign those admins first."
                            );
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No roles yet')
            ->emptyStateDescription('Use "Add role" to create one.');
    }
}
