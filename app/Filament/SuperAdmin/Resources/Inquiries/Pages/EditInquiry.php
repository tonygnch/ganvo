<?php

namespace App\Filament\SuperAdmin\Resources\Inquiries\Pages;

use App\Filament\SuperAdmin\Resources\Inquiries\InquiryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInquiry extends EditRecord
{
    protected static string $resource = InquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
