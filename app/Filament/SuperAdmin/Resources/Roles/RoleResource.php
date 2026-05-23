<?php

namespace App\Filament\SuperAdmin\Resources\Roles;

use App\Filament\SuperAdmin\Resources\Roles\Pages\CreateRole;
use App\Filament\SuperAdmin\Resources\Roles\Pages\EditRole;
use App\Filament\SuperAdmin\Resources\Roles\Pages\ListRoles;
use App\Filament\SuperAdmin\Resources\Roles\Schemas\RoleForm;
use App\Filament\SuperAdmin\Resources\Roles\Tables\RolesTable;
use App\Services\RoleMatrix;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Permission\Models\Role;

/**
 * SuperAdmin editor for roles + their permission grants.
 *
 * Lets the platform owner create custom roles ("Content Editor",
 * "Read-Only Analyst", ...) and adjust what each role can do without
 * touching code. System roles are protected from rename/delete because
 * application code references them by name; their permissions remain
 * editable.
 *
 * The store_admin role (tenant-scoped, lives outside the SA panel)
 * is hidden from this list so the operator only sees platform-side roles.
 */
class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'Roles';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?string $modelLabel = 'Role';

    protected static ?string $pluralModelLabel = 'Roles';

    protected static ?int $navigationSort = 120;

    public static function canViewAny(): bool
    {
        return RoleMatrix::canSee(auth()->user(), RoleMatrix::SEC_ROLES);
    }

    public static function canCreate(): bool
    {
        return RoleMatrix::canSee(auth()->user(), RoleMatrix::SEC_ROLES);
    }

    public static function canEdit($record): bool
    {
        return RoleMatrix::canSee(auth()->user(), RoleMatrix::SEC_ROLES);
    }

    public static function canDelete($record): bool
    {
        if (! RoleMatrix::canSee(auth()->user(), RoleMatrix::SEC_ROLES)) {
            return false;
        }
        // System roles can NEVER be deleted — code references them by name.
        return ! RoleMatrix::isSystemRole($record->name);
    }

    public static function form(Schema $schema): Schema
    {
        return RoleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RolesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'edit'   => EditRole::route('/{record}/edit'),
        ];
    }

    /**
     * Hide store_admin from this list — it's a tenant-scoped role that
     * doesn't grant any platform permissions, so editing it here would
     * be misleading.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('name', '!=', 'store_admin');
    }
}
