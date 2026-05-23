<?php

namespace App\Filament\SuperAdmin\Resources\Admins\Pages;

use App\Filament\SuperAdmin\Resources\Admins\AdminResource;
use App\Services\RoleMatrix;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAdmin extends EditRecord
{
    protected static string $resource = AdminResource::class;

    protected ?string $pendingRole = null;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Same pattern as CreateAdmin — extract role, drop from $data.
        $this->pendingRole = $data['role'] ?? null;
        unset($data['role']);
        return $data;
    }

    protected function afterSave(): void
    {
        if (! $this->pendingRole) {
            return;
        }

        // Self-demote guard: a super_admin editing themselves can't drop
        // out of super_admin if they're the last one. Otherwise the panel
        // becomes unreachable to anyone.
        if (
            $this->record->id === auth()->id()
            && $this->record->hasRole(RoleMatrix::SUPER_ADMIN)
            && $this->pendingRole !== RoleMatrix::SUPER_ADMIN
        ) {
            $remaining = \App\Models\User::role(RoleMatrix::SUPER_ADMIN)
                ->where('id', '!=', $this->record->id)
                ->count();
            if ($remaining === 0) {
                \Filament\Notifications\Notification::make()
                    ->danger()
                    ->title("Can't change role — you're the last super admin")
                    ->body('Promote another admin to super admin before changing your own role.')
                    ->send();
                $this->record->refresh();
                return;
            }
        }

        $this->record->syncRoles([$this->pendingRole]);
    }
}
