<?php

namespace App\Filament\SuperAdmin\Resources\Admins\Pages;

use App\Filament\SuperAdmin\Resources\Admins\AdminResource;
use App\Services\RoleMatrix;
use Filament\Resources\Pages\CreateRecord;

class CreateAdmin extends CreateRecord
{
    protected static string $resource = AdminResource::class;

    /**
     * Strip the form's pseudo-field 'role' before the user row is created —
     * it's not a column. We'll apply it as a real Spatie role in
     * afterCreate() once we have a saved User instance to attach it to.
     */
    protected ?string $pendingRole = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Capture role + remove from $data so it doesn't error on a
        // non-existent column. Also mark the email verified so the new
        // admin can log in immediately.
        $this->pendingRole = $data['role'] ?? RoleMatrix::SUPPORT_ADMIN;
        unset($data['role']);
        $data['email_verified_at'] ??= now();
        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->pendingRole) {
            $this->record->syncRoles([$this->pendingRole]);
        }
    }
}
