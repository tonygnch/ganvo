<?php

namespace App\Filament\SuperAdmin\Resources\Admins\Schemas;

use App\Services\RoleMatrix;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Account')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(120),
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->unique(table: 'users', column: 'email', ignoreRecord: true)
                            ->maxLength(160),

                        // Password is required on create, optional on edit
                        // (leaving blank keeps the current password). The
                        // hashing happens via the cast on User::password.
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->minLength(8)
                            ->maxLength(255)
                            ->required(fn (string $operation) => $operation === 'create')
                            ->dehydrateStateUsing(fn (?string $state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn (?string $state) => filled($state))
                            ->helperText('Minimum 8 characters. Leave blank when editing to keep current.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Access')
                    ->description('A new admin gets exactly one role. To change scope later, edit and pick a different role.')
                    ->schema([
                        Select::make('role')
                            ->label('Role')
                            ->options(self::roleOptions())
                            ->required()
                            ->native(false)
                            ->helperText(self::roleHelpText())
                            // For editing, hydrate from the user's current
                            // role (first non-store_admin role they have —
                            // each admin only holds one platform role).
                            ->formatStateUsing(function ($state, $record) {
                                if ($record) {
                                    $first = $record->roles()
                                        ->where('name', '!=', 'store_admin')
                                        ->orderBy('name')
                                        ->first();
                                    if ($first) {
                                        return $first->name;
                                    }
                                }
                                return $state ?? RoleMatrix::SUPPORT_ADMIN;
                            })
                            // Don't save 'role' as a column on users — it's
                            // applied via syncRoles() in the page's
                            // afterCreate/afterSave hooks instead.
                            ->dehydrated(false),
                    ]),
            ]);
    }

    /**
     * Build the Select options from roles in the DB. System roles get
     * their friendly label from RoleMatrix; custom roles use their name
     * humanized. store_admin is excluded — it's a tenant-scoped role
     * that doesn't grant platform access.
     */
    private static function roleOptions(): array
    {
        $out = [];
        foreach (Role::query()->where('name', '!=', 'store_admin')->orderBy('name')->get() as $role) {
            $out[$role->name] = RoleMatrix::ROLE_LABELS[$role->name] ?? \Str::headline($role->name);
        }
        return $out;
    }

    /** Inline summary of what the system roles do; custom roles aren't documented here. */
    private static function roleHelpText(): string
    {
        $parts = [];
        foreach (RoleMatrix::SYSTEM_ROLES as $role) {
            $label = RoleMatrix::ROLE_LABELS[$role] ?? $role;
            $desc = RoleMatrix::ROLE_DESCRIPTIONS[$role] ?? '';
            $parts[] = "{$label} — {$desc}";
        }
        $parts[] = 'Custom roles use whatever permissions you assigned to them in SuperAdmin → System → Roles.';
        return implode("\n", $parts);
    }
}
