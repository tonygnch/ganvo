<?php

namespace App\Filament\SuperAdmin\Resources\Tenants\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

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
            ]);
    }
}
