<?php

namespace App\Filament\SuperAdmin\Resources\Plans;

use App\Filament\SuperAdmin\Resources\Plans\Pages\CreatePlan;
use App\Filament\SuperAdmin\Resources\Plans\Pages\EditPlan;
use App\Filament\SuperAdmin\Resources\Plans\Pages\ListPlans;
use App\Filament\SuperAdmin\Resources\Plans\Schemas\PlanForm;
use App\Filament\SuperAdmin\Resources\Plans\Tables\PlansTable;
use App\Models\Plan;
use App\Services\RoleMatrix;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PlanResource extends Resource
{
    protected static ?string $model = Plan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Billing';

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?int $navigationSort = 30;

    public static function canViewAny(): bool
    {
        return RoleMatrix::canSee(auth()->user(), RoleMatrix::SEC_PLANS);
    }

    public static function canCreate(): bool
    {
        return RoleMatrix::canSee(auth()->user(), RoleMatrix::SEC_PLANS_MANAGE);
    }

    public static function canEdit($record): bool
    {
        return RoleMatrix::canSee(auth()->user(), RoleMatrix::SEC_PLANS_MANAGE);
    }

    public static function canDelete($record): bool
    {
        return RoleMatrix::canSee(auth()->user(), RoleMatrix::SEC_PLANS_MANAGE);
    }

    public static function form(Schema $schema): Schema
    {
        return PlanForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PlansTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListPlans::route('/'),
            'create' => CreatePlan::route('/create'),
            'edit'   => EditPlan::route('/{record}/edit'),
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
