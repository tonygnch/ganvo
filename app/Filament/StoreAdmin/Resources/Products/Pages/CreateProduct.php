<?php

namespace App\Filament\StoreAdmin\Resources\Products\Pages;

use App\Filament\StoreAdmin\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['tenant_id'] = auth()->user()->tenant_id;
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

        return $data;
    }
}
