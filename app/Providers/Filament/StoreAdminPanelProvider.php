<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\StoreAdmin\Widgets\RecentOrders;
use App\Filament\StoreAdmin\Widgets\RevenueChart;
use App\Filament\StoreAdmin\Widgets\StoreStats;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Support\Facades\Blade;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class StoreAdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('store')
            ->path('store')
            ->domain(config('ganvo.central_domain'))
            ->login()
            // Registration is intentionally NOT enabled here — the onboarding
            // wizard (App\Http\Controllers\Onboarding\AuthController) owns
            // merchant signup so it can create the Tenant + Store and start
            // the wizard in one transaction.
            ->brandName('Ganvo Store')
            ->colors([
                'primary' => Color::Emerald,
            ])
            ->discoverResources(in: app_path('Filament/StoreAdmin/Resources'), for: 'App\\Filament\\StoreAdmin\\Resources')
            ->discoverPages(in: app_path('Filament/StoreAdmin/Pages'), for: 'App\\Filament\\StoreAdmin\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/StoreAdmin/Widgets'), for: 'App\\Filament\\StoreAdmin\\Widgets')
            ->widgets([
                AccountWidget::class,
                StoreStats::class,
                RevenueChart::class,
                RecentOrders::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                \App\Http\Middleware\EnsureOnboardingComplete::class,
            ])
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn (): string => Blade::render('@include(\'filament.impersonation-banner\')')
            );
    }
}
