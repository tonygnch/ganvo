<?php

namespace App\Filament\SuperAdmin\Resources\Tenants;

use App\Filament\SuperAdmin\Resources\Tenants\Pages\CreateTenant;
use App\Filament\SuperAdmin\Resources\Tenants\Pages\EditTenant;
use App\Filament\SuperAdmin\Resources\Tenants\Pages\ListTenants;
use App\Filament\SuperAdmin\Resources\Tenants\Pages\ViewTenant;
use App\Filament\SuperAdmin\Resources\Tenants\Schemas\TenantForm;
use App\Filament\SuperAdmin\Resources\Tenants\Tables\TenantsTable;
use App\Models\Tenant;
use App\Services\RoleMatrix;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static string|\UnitEnum|null $navigationGroup = 'Clients';

    protected static ?string $recordTitleAttribute = 'name';

    public static function canViewAny(): bool
    {
        return RoleMatrix::canSee(auth()->user(), RoleMatrix::SEC_TENANTS);
    }

    public static function canCreate(): bool
    {
        return RoleMatrix::canSee(auth()->user(), RoleMatrix::SEC_TENANTS_MANAGE);
    }

    public static function canEdit($record): bool
    {
        return RoleMatrix::canSee(auth()->user(), RoleMatrix::SEC_TENANTS_MANAGE);
    }

    public static function canDelete($record): bool
    {
        return RoleMatrix::canSee(auth()->user(), RoleMatrix::SEC_TENANTS_MANAGE);
    }

    public static function form(Schema $schema): Schema
    {
        return TenantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TenantsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTenants::route('/'),
            'create' => CreateTenant::route('/create'),
            'view' => ViewTenant::route('/{record}'),
            'edit' => EditTenant::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
