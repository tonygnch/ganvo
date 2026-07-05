<?php

namespace App\Filament\SuperAdmin\Resources\Websites\Pages;

use App\Filament\SuperAdmin\Resources\Websites\WebsiteResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditWebsite extends EditRecord
{
    protected static string $resource = WebsiteResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    /** Surface the client-tenant fields alongside the website's own. */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $tenant = $this->getRecord()->tenant;
        $data['client_name'] = $tenant?->name;
        $data['client_email'] = $tenant?->contact_email;
        $data['client_status'] = $tenant?->status;

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $state = $this->form->getRawState();
        $record->tenant?->update([
            'name' => trim((string) ($state['client_name'] ?? $record->tenant->name)),
            'contact_email' => $state['client_email'] ?? $record->tenant->contact_email,
            'status' => $state['client_status'] ?? $record->tenant->status,
        ]);
        $record->update($data);

        return $record;
    }
}
