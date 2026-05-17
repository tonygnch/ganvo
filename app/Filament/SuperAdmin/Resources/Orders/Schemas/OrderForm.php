<?php

namespace App\Filament\SuperAdmin\Resources\Orders\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->required(),
                TextInput::make('order_number')
                    ->required(),
                TextInput::make('customer_email')
                    ->email()
                    ->required(),
                TextInput::make('customer_name'),
                TextInput::make('total_cents')
                    ->required()
                    ->numeric(),
                TextInput::make('currency')
                    ->required()
                    ->default('USD'),
                TextInput::make('status')
                    ->required()
                    ->default('pending'),
                TextInput::make('stripe_payment_intent_id'),
                Textarea::make('shipping_address')
                    ->columnSpanFull(),
                DateTimePicker::make('paid_at'),
            ]);
    }
}
