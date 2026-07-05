<?php

namespace App\Filament\SuperAdmin\Resources\Websites\Pages;

use App\Filament\SuperAdmin\Resources\Websites\WebsiteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWebsites extends ListRecords
{
    protected static string $resource = WebsiteResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
