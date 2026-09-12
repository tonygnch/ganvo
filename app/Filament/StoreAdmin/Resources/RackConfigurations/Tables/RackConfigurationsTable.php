<?php

namespace App\Filament\StoreAdmin\Resources\RackConfigurations\Tables;

use App\Filament\StoreAdmin\Resources\RackConfigurations\RackConfigurationResource;
use App\Models\RackConfiguration;
use App\Services\Money;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RackConfigurationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('code')
                    ->label(__('admin.rack_configs.field.code'))
                    ->searchable()
                    ->copyable()
                    ->copyMessage(__('admin.rack_configs.notify.code_copied'))
                    ->weight('bold')
                    ->fontFamily('mono'),

                TextColumn::make('height_cm')
                    ->label(__('admin.rack_configs.field.size'))
                    ->state(fn (RackConfiguration $r) => $r->height_cm.' × '.$r->depth_cm.' cm')
                    ->description(fn (RackConfiguration $r) => trans_choice(
                        'site.storefront.sankevi.cfg_levels_count', $r->levels, ['count' => $r->levels]
                    )),

                TextColumn::make('segments')
                    ->label(__('admin.rack_configs.field.sections'))
                    // The bays as the customer laid them out, in order —
                    // "100+100+80" says more than "3 секции" about what the
                    // yard would actually be cutting.
                    ->state(fn (RackConfiguration $r) => implode(' + ', $r->segmentWidths()))
                    ->description(fn (RackConfiguration $r) => number_format($r->totalLengthCm() / 100, 2).' m'),

                TextColumn::make('total_cents')
                    ->label(__('admin.rack_configs.field.total'))
                    ->alignEnd()
                    ->sortable()
                    ->state(fn (RackConfiguration $r) => Money::format($r->total_cents, $r->currency ?: 'EUR'))
                    ->description(fn (RackConfiguration $r) => __('admin.rack_configs.text.incl_vat', [
                        'rate' => rtrim(rtrim(number_format($r->vat_rate_bp / 100, 2, '.', ''), '0'), '.'),
                    ])),

                TextColumn::make('order_items_count')
                    ->label(__('admin.rack_configs.field.state'))
                    ->badge()
                    ->state(fn (RackConfiguration $r) => $r->order_items_count > 0
                        ? __('admin.rack_configs.opt.ordered')
                        : __('admin.rack_configs.opt.saved_only'))
                    ->color(fn (RackConfiguration $r) => $r->order_items_count > 0 ? 'success' : 'gray'),

                TextColumn::make('created_at')
                    ->label(__('admin.rack_configs.field.created'))
                    ->since()
                    ->sortable()
                    ->tooltip(fn (RackConfiguration $r) => $r->created_at?->toDayDateTimeString()),
            ])
            ->filters([
                TernaryFilter::make('ordered')
                    ->label(__('admin.rack_configs.field.state'))
                    ->placeholder(__('admin.rack_configs.opt.any'))
                    ->trueLabel(__('admin.rack_configs.opt.ordered'))
                    ->falseLabel(__('admin.rack_configs.opt.saved_only'))
                    ->queries(
                        true: fn (Builder $q) => $q->has('orderItems'),
                        false: fn (Builder $q) => $q->doesntHave('orderItems'),
                        blank: fn (Builder $q) => $q,
                    ),
                Filter::make('big')
                    ->label(__('admin.rack_configs.filter.big'))
                    ->query(fn (Builder $q) => $q->where('total_cents', '>=', 100000)),
            ])
            ->recordActions([
                Action::make('open')
                    ->label(__('admin.rack_configs.action.open'))
                    ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                    ->url(fn (RackConfiguration $r) => RackConfigurationResource::storefrontBase().'/configurator/'.$r->code)
                    ->openUrlInNewTab(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ])
            ->emptyStateHeading(__('admin.rack_configs.empty.heading'))
            ->emptyStateDescription(__('admin.rack_configs.empty.description'));
    }
}
