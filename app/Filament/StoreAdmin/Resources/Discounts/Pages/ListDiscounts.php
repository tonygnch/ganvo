<?php

namespace App\Filament\StoreAdmin\Resources\Discounts\Pages;

use App\Filament\StoreAdmin\Resources\Discounts\DiscountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDiscounts extends ListRecords
{
    protected static string $resource = DiscountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Add discount'),
        ];
    }
}
