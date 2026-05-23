<?php

namespace App\Filament\SuperAdmin\Resources\Admins\Pages;

use App\Filament\SuperAdmin\Resources\Admins\AdminResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAdmins extends ListRecords
{
    protected static string $resource = AdminResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Add admin'),
        ];
    }
}
