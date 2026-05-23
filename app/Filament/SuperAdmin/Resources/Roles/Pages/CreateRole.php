<?php

namespace App\Filament\SuperAdmin\Resources\Roles\Pages;

use App\Filament\SuperAdmin\Resources\Roles\RoleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    /** Captured before save so afterCreate can attach the chosen permissions. */
    protected array $pendingPermissions = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingPermissions = (array) ($data['permissions'] ?? []);
        unset($data['permissions']);
        // Spatie requires guard_name; default to web.
        $data['guard_name'] ??= 'web';
        return $data;
    }

    protected function afterCreate(): void
    {
        if (! empty($this->pendingPermissions)) {
            $this->record->syncPermissions($this->pendingPermissions);
        }
    }
}
