<?php

namespace App\Filament\StoreAdmin\Resources\Messages\Pages;

use App\Filament\StoreAdmin\Resources\Messages\MessageResource;
use Filament\Resources\Pages\ListRecords;

class ListMessages extends ListRecords
{
    protected static string $resource = MessageResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
