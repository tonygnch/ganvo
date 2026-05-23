<?php

namespace App\Filament\SuperAdmin\Resources\Admins\Schemas;

use App\Services\RoleMatrix;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

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
                            // For editing, hydrate from the user's current platform role.
                            ->formatStateUsing(function ($state, $record) {
                                if ($record) {
                                    foreach (RoleMatrix::PLATFORM_ROLES as $r) {
                                        if ($record->hasRole($r)) {
                                            return $r;
                                        }
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
     * Build the Select options with the human-readable role label, falling
     * back to the slug if the label map is incomplete.
     */
    private static function roleOptions(): array
    {
        $out = [];
        foreach (RoleMatrix::PLATFORM_ROLES as $role) {
            $out[$role] = RoleMatrix::ROLE_LABELS[$role] ?? $role;
        }
        return $out;
    }

    /** Inline summary of what each role does, rendered below the Select. */
    private static function roleHelpText(): string
    {
        $parts = [];
        foreach (RoleMatrix::PLATFORM_ROLES as $role) {
            $label = RoleMatrix::ROLE_LABELS[$role] ?? $role;
            $desc = RoleMatrix::ROLE_DESCRIPTIONS[$role] ?? '';
            $parts[] = "{$label} — {$desc}";
        }
        return implode("\n", $parts);
    }
}
