<?php

namespace App\Filament\SuperAdmin\Resources\Tenants\Tables;

use App\Models\Tenant;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class TenantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (Tenant $r) => $r->slug),
                TextColumn::make('contact_email')
                    ->label('Contact')
                    ->searchable()
                    ->description(fn (Tenant $r) => $r->contact_phone),
                TextColumn::make('subscription_plan')
                    ->label('Plan')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Tenant::PLANS[$state] ?? $state)
                    ->color('info'),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => Tenant::STATUSES[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'pending' => 'warning',
                        'suspended' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('users_count')
                    ->label('Users')
                    ->counts('users')
                    ->sortable(),
                TextColumn::make('orders_count')
                    ->label('Orders')
                    ->counts('orders')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Joined')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(Tenant::STATUSES),
                SelectFilter::make('subscription_plan')
                    ->label('Plan')
                    ->options(Tenant::PLANS),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
