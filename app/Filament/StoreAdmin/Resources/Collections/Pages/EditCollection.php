<?php

namespace App\Filament\StoreAdmin\Resources\Collections\Pages;

use App\Filament\StoreAdmin\Resources\Collections\CollectionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCollection extends EditRecord
{
    protected static string $resource = CollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
