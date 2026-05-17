<?php

namespace App\Filament\SuperAdmin\Resources\Tenants\Pages;

use App\Filament\SuperAdmin\Resources\Tenants\TenantResource;
use App\Models\Tenant;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class ViewTenant extends ViewRecord
{
    protected static string $resource = TenantResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Client')
                ->columns(3)
                ->schema([
                    TextEntry::make('name')->weight('bold'),
                    TextEntry::make('slug')->badge(),
                    TextEntry::make('status')
                        ->badge()
                        ->formatStateUsing(fn (string $state) => Tenant::STATUSES[$state] ?? $state)
                        ->color(fn (string $state): string => match ($state) {
                            'active' => 'success',
                            'pending' => 'warning',
                            'suspended' => 'danger',
                            default => 'gray',
                        }),
                    TextEntry::make('business_type')->placeholder('—'),
                    TextEntry::make('subscription_plan')
                        ->label('Plan')
                        ->badge()
                        ->formatStateUsing(fn (string $state) => Tenant::PLANS[$state] ?? $state)
                        ->color('info'),
                    TextEntry::make('created_at')->label('Joined')->dateTime(),
                ]),

            Section::make('Contact')
                ->columns(2)
                ->schema([
                    TextEntry::make('contact_email')->placeholder('—')->copyable(),
                    TextEntry::make('contact_phone')->placeholder('—')->copyable(),
                ]),

            Section::make('Storefront')
                ->columns(2)
                ->schema([
                    TextEntry::make('store.theme')->label('Theme')->placeholder('—'),
                    TextEntry::make('store.is_live')
                        ->label('Live')
                        ->badge()
                        ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                        ->color(fn ($state) => $state ? 'success' : 'gray'),
                    TextEntry::make('storefront_url')
                        ->label('Storefront URL')
                        ->state(fn (Tenant $r) => 'http://' . $r->slug . '.' . config('ganvo.central_domain') . ':8000')
                        ->url(fn (Tenant $r) => 'http://' . $r->slug . '.' . config('ganvo.central_domain') . ':8000')
                        ->openUrlInNewTab()
                        ->columnSpanFull(),
                ]),

            Section::make('Stripe')
                ->columns(2)
                ->visible(fn (Tenant $r) => $r->stripe_account_id || $r->stripe_customer_id)
                ->schema([
                    TextEntry::make('stripe_account_id')->placeholder('—')->copyable(),
                    TextEntry::make('stripe_customer_id')->placeholder('—')->copyable(),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        /** @var Tenant $tenant */
        $tenant = $this->record;

        return [
            Action::make('activate')
                ->label('Activate')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->visible(fn () => ! $tenant->isActive())
                ->requiresConfirmation()
                ->modalDescription('The storefront will become reachable.')
                ->action(function () use ($tenant) {
                    $tenant->update(['status' => Tenant::STATUS_ACTIVE]);
                    Notification::make()->success()->title('Client activated')->send();
                }),

            Action::make('suspend')
                ->label('Suspend')
                ->icon(Heroicon::OutlinedNoSymbol)
                ->color('danger')
                ->visible(fn () => $tenant->isActive())
                ->requiresConfirmation()
                ->modalDescription('The storefront will return 404 until reactivated. Existing orders are unaffected.')
                ->action(function () use ($tenant) {
                    $tenant->update(['status' => Tenant::STATUS_SUSPENDED]);
                    Notification::make()->warning()->title('Client suspended')->send();
                }),

            Action::make('changePlan')
                ->label('Change plan')
                ->icon(Heroicon::OutlinedSquares2x2)
                ->color('info')
                ->fillForm(fn () => ['subscription_plan' => $tenant->subscription_plan])
                ->schema([
                    Select::make('subscription_plan')
                        ->label('Subscription plan')
                        ->options(Tenant::PLANS)
                        ->required(),
                ])
                ->action(function (array $data) use ($tenant) {
                    $tenant->update(['subscription_plan' => $data['subscription_plan']]);
                    Notification::make()
                        ->success()
                        ->title('Plan changed')
                        ->body('Now on the ' . Tenant::PLANS[$data['subscription_plan']] . ' plan.')
                        ->send();
                }),

            Action::make('impersonate')
                ->label('Impersonate')
                ->icon(Heroicon::OutlinedUserCircle)
                ->color('warning')
                ->visible(fn () => $tenant->users()
                    ->whereHas('roles', fn ($q) => $q->where('name', 'store_admin'))
                    ->exists())
                ->requiresConfirmation()
                ->modalDescription("You'll be logged in as this client's store admin. Use the banner at the top to return.")
                ->action(function () use ($tenant) {
                    $storeAdmin = $tenant->users()
                        ->whereHas('roles', fn ($q) => $q->where('name', 'store_admin'))
                        ->first();

                    if (! $storeAdmin) {
                        Notification::make()->danger()->title('No store admin user found for this tenant')->send();
                        return;
                    }

                    Session::put('impersonator_id', Auth::id());
                    Auth::login($storeAdmin);

                    return redirect()->to(config('app.url') . '/store');
                }),
        ];
    }
}
