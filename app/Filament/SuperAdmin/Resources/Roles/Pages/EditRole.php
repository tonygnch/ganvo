<?php

namespace App\Filament\SuperAdmin\Resources\Roles\Pages;

use App\Filament\SuperAdmin\Resources\Roles\RoleResource;
use App\Services\RoleMatrix;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected array $pendingPermissions = [];

    /** True when this role's `name` field is locked (i.e. it's a system role). */
    protected bool $pendingPermissionsTouched = false;

    protected function getHeaderActions(): array
    {
        return [
            // Reset-to-defaults: only for the 3 non-super system roles.
            // Super admin's permissions are fixed in code (always all).
            // Custom roles have no "defaults" to revert to.
            Action::make('resetDefaults')
                ->label('Reset to defaults')
                ->icon(Heroicon::OutlinedArrowPath)
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('Replace this role\'s permissions with the built-in defaults from RoleMatrix.')
                ->visible(fn () => isset(RoleMatrix::defaultRolePermissions()[$this->record->name]))
                ->action(function () {
                    $defaults = RoleMatrix::defaultRolePermissions()[$this->record->name] ?? [];
                    $this->record->syncPermissions($defaults);
                    Notification::make()
                        ->success()
                        ->title('Defaults restored')
                        ->body(count($defaults) . ' permission(s) attached.')
                        ->send();
                    $this->refreshFormData(['permissions']);
                }),
            DeleteAction::make()
                ->visible(fn () => ! RoleMatrix::isSystemRole($this->record->name)),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Capture permission selection BEFORE Filament drops it (the form
        // marks 'permissions' as dehydrated:false, but it's still in $data
        // here in the mutator hook).
        $this->pendingPermissions = (array) ($data['permissions'] ?? []);
        $this->pendingPermissionsTouched = array_key_exists('permissions', $data);
        unset($data['permissions']);

        // System role name is immutable — make sure it survives a save
        // even if some intermediate state tried to change it.
        if (RoleMatrix::isSystemRole($this->record->name)) {
            $data['name'] = $this->record->name;
        }
        return $data;
    }

    protected function afterSave(): void
    {
        // Super admin's permissions are managed by code — ignore the form.
        if ($this->record->name === RoleMatrix::SUPER_ADMIN) {
            return;
        }
        if ($this->pendingPermissionsTouched) {
            $this->record->syncPermissions($this->pendingPermissions);
        }
    }
}
