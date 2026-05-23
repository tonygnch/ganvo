<?php

namespace App\Filament\StoreAdmin\Resources\Collections\Pages;

use App\Filament\StoreAdmin\Resources\Collections\CollectionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCollection extends CreateRecord
{
    protected static string $resource = CollectionResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Collections are tenant-scoped; tenant_id is not a form field
        // so we attach it from the authenticated user before insert.
        $data['tenant_id'] = auth()->user()?->tenant_id;
        return $data;
    }
}
