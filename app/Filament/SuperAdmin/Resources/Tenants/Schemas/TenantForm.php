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
                TextInput::make('stripe_account_id'),
                TextInput::make('stripe_customer_id'),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                Textarea::make('onboarding_progress')
                    ->columnSpanFull(),
                DateTimePicker::make('onboarded_at'),
            ]);
    }
}
