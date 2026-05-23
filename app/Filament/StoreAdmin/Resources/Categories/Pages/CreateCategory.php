<?php

namespace App\Filament\StoreAdmin\Resources\Categories\Pages;

use App\Filament\StoreAdmin\Resources\Categories\CategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Categories are tenant-scoped; tenant_id isn't a form field but
        // the resource needs it set. Pull from the authed user.
        $data['tenant_id'] = auth()->user()?->tenant_id;
        return $data;
    }
}
