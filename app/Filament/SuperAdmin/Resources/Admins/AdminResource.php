<?php

namespace App\Filament\SuperAdmin\Resources\Admins;

use App\Filament\SuperAdmin\Resources\Admins\Pages\CreateAdmin;
use App\Filament\SuperAdmin\Resources\Admins\Pages\EditAdmin;
use App\Filament\SuperAdmin\Resources\Admins\Pages\ListAdmins;
use App\Filament\SuperAdmin\Resources\Admins\Schemas\AdminForm;
use App\Filament\SuperAdmin\Resources\Admins\Tables\AdminsTable;
use App\Models\User;
use App\Services\RoleMatrix;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Manages platform admin users — anyone with one of the SUPER /BILLING /
 * MARKETING /SUPPORT roles defined in {@see RoleMatrix}.
 *
 * Store admins (tenant-scoped users with the store_admin role) are
 * explicitly NOT shown here — they're managed implicitly via the Tenant
 * resource. This page is purely for inviting / removing internal staff.
 */
class AdminResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $navigationLabel = 'Admins';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?string $modelLabel = 'Admin';

    protected static ?string $pluralModelLabel = 'Admins';

    protected static ?int $navigationSort = 110;

    public static function canViewAny(): bool
    {
        return RoleMatrix::canSee(auth()->user(), RoleMatrix::SEC_ADMINS);
    }

    public static function canCreate(): bool
    {
        return RoleMatrix::canSee(auth()->user(), RoleMatrix::SEC_ADMINS);
    }

    public static function canEdit($record): bool
    {
        return RoleMatrix::canSee(auth()->user(), RoleMatrix::SEC_ADMINS);
    }

    public static function canDelete($record): bool
    {
        return RoleMatrix::canSee(auth()->user(), RoleMatrix::SEC_ADMINS);
    }

    public static function form(Schema $schema): Schema
    {
        return AdminForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdminsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListAdmins::route('/'),
            'create' => CreateAdmin::route('/create'),
            'edit'   => EditAdmin::route('/{record}/edit'),
        ];
    }

    /**
     * Scope the resource to platform admins only. Any user who is NOT
     * tenant-scoped and has at least one role other than store_admin is
     * considered a platform admin — this lets custom roles created via
     * the Roles UI show up here automatically without us needing to
     * maintain an allowlist.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereNull('tenant_id')
            ->whereHas('roles', fn (Builder $q) => $q->where('name', '!=', 'store_admin'));
    }
}
