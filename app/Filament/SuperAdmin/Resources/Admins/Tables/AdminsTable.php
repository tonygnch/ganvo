<?php

namespace App\Filament\SuperAdmin\Resources\Admins\Tables;

use App\Models\User;
use App\Services\RoleMatrix;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AdminsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('email')
                    ->searchable()
                    ->copyable()
                    ->color('gray'),
                TextColumn::make('platform_role')
                    ->label('Role')
                    ->badge()
                    ->state(function (User $r) {
                        foreach (RoleMatrix::PLATFORM_ROLES as $role) {
                            if ($r->hasRole($role)) {
                                return RoleMatrix::ROLE_LABELS[$role] ?? $role;
                            }
                        }
                        return '—';
                    })
                    ->color(function (User $r) {
                        return match (true) {
                            $r->hasRole(RoleMatrix::SUPER_ADMIN)     => 'danger',
                            $r->hasRole(RoleMatrix::BILLING_ADMIN)   => 'warning',
                            $r->hasRole(RoleMatrix::MARKETING_ADMIN) => 'info',
                            $r->hasRole(RoleMatrix::SUPPORT_ADMIN)   => 'success',
                            default => 'gray',
                        };
                    }),
                TextColumn::make('created_at')
                    ->label('Added')
                    ->since()
                    ->sortable()
                    ->tooltip(fn (User $r) => $r->created_at?->toDayDateTimeString()),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Role')
                    ->options(collect(RoleMatrix::PLATFORM_ROLES)
                        ->mapWithKeys(fn ($r) => [$r => RoleMatrix::ROLE_LABELS[$r] ?? $r])
                        ->all())
                    ->query(function ($query, array $data) {
                        if (! filled($data['value'] ?? null)) {
                            return $query;
                        }
                        return $query->whereHas('roles', fn ($q) => $q->where('name', $data['value']));
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    // Prevent deleting yourself or the last super admin —
                    // we'd lock the platform out of its own admin panel.
                    ->before(function (User $record) {
                        if ($record->id === auth()->id()) {
                            throw new \RuntimeException("You can't delete your own admin account.");
                        }
                        if ($record->hasRole(RoleMatrix::SUPER_ADMIN)) {
                            $remaining = User::role(RoleMatrix::SUPER_ADMIN)
                                ->where('id', '!=', $record->id)
                                ->count();
                            if ($remaining === 0) {
                                throw new \RuntimeException("Can't delete the last super admin — promote someone else first.");
                            }
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('No platform admins yet')
            ->emptyStateDescription('Create one with the button above. Super admin is full access; the other roles see a subset of the panel.');
    }
}
