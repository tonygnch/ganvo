<?php

namespace App\Filament\StoreAdmin\Resources\Discounts\Pages;

use App\Filament\StoreAdmin\Resources\Discounts\DiscountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDiscount extends EditRecord
{
    protected static string $resource = DiscountResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
