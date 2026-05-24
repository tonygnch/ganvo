<?php

namespace App\Filament\StoreAdmin\Resources\Discounts\Pages;

use App\Filament\StoreAdmin\Resources\Discounts\DiscountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDiscount extends CreateRecord
{
    protected static string $resource = DiscountResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Discounts are tenant-scoped; tenant_id isn't a form field, so
        // attach it from the authenticated user before insert.
        $data['tenant_id'] = auth()->user()?->tenant_id;
        return $data;
    }
}
