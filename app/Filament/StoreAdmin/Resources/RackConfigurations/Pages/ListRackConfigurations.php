<?php

namespace App\Filament\StoreAdmin\Resources\RackConfigurations\Pages;

use App\Filament\StoreAdmin\Resources\RackConfigurations\RackConfigurationResource;
use Filament\Resources\Pages\ListRecords;

class ListRackConfigurations extends ListRecords
{
    protected static string $resource = RackConfigurationResource::class;

    /** Nothing to create: racks are drawn in the configurator, not typed here. */
    protected function getHeaderActions(): array
    {
        return [];
    }
}
