<?php

namespace App\Filament\SuperAdmin\Resources\Tenants\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('business_type'),
                TextInput::make('contact_email')
                    ->email(),
                TextInput::make('contact_phone')
                    ->tel(),
                TextInput::make('subscription_plan')
                    ->required()
                    ->default('starter'),
                TextInput::make('stripe_account_id')
                    ->label('Stripe Connect account (payouts)')
                    ->helperText('For client → end-customer payments. Different from billing.'),
                TextInput::make('stripe_id')
                    ->label('Stripe customer (platform billing)')
                    ->helperText('Auto-populated when the tenant subscribes via Cashier.'),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                Textarea::make('onboarding_progress')
                    ->columnSpanFull(),
                DateTimePicker::make('onboarded_at'),

                /*
                 | PREVIEW LOCK — per storefront, so one client's site can be
                 | shown on its public subdomain while every other tenant stays
                 | open. Distinct from the platform-wide PREVIEW_LOCK env flag,
                 | which locks everything including these admin panels.
                 |
                 | The password is written straight to a bcrypt hash and is
                 | never loaded back into the form: the field is always blank on
                 | edit, and leaving it blank keeps whatever is already stored.
                 | That is why there is no "current password" to display — there
                 | isn't one, only a hash, and that is the point.
                 */
                Section::make('Preview lock')
                    ->description('Put THIS storefront behind an HTTP password. Other tenants are unaffected.')
                    ->relationship('store')
                    ->columns(2)
                    ->schema([
                        Toggle::make('preview_lock')
                            ->label('Require a password to view this storefront')
                            ->helperText('Visitors get a browser username/password prompt. Search engines are told not to index it.')
                            ->live()
                            ->columnSpanFull(),

                        TextInput::make('preview_user')
                            ->label('Username')
                            ->autocomplete('off')
                            // Required only once the lock is on, so a store can
                            // never be saved locked-but-unopenable.
                            ->required(fn ($get) => (bool) $get('preview_lock'))
                            ->visible(fn ($get) => (bool) $get('preview_lock')),

                        TextInput::make('preview_password_hash')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->autocomplete('new-password')
                            ->minLength(8)
                            ->helperText('Leave blank to keep the current password. Stored hashed — it cannot be read back.')
                            ->visible(fn ($get) => (bool) $get('preview_lock'))
                            // Required only when turning the lock on for a store
                            // that has no password yet.
                            //
                            // $record inside a ->relationship('store') section is
                            // the STORE, not the Tenant — reaching for
                            // $record->store here returned null, made the field
                            // required on EVERY edit, and so made "leave blank to
                            // keep the current password" impossible to obey.
                            // Both shapes are accepted so the rule survives the
                            // section being moved.
                            ->required(function ($get, $record) {
                                if (! (bool) $get('preview_lock')) {
                                    return false;
                                }
                                $store = $record instanceof \App\Models\Store
                                    ? $record
                                    : ($record?->store ?? null);

                                return blank($store?->preview_password_hash);
                            })
                            // Hash on the way in...
                            ->dehydrateStateUsing(fn (?string $state) => filled($state) ? Hash::make($state) : null)
                            // ...and skip the column entirely when left blank,
                            // so an edit that does not touch the password does
                            // not wipe the stored hash.
                            ->dehydrated(fn (?string $state) => filled($state))
                            // Never echo the stored hash into the input.
                            ->formatStateUsing(fn () => null),
                    ]),
            ]);
    }
}
