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
            // Browser tab icon for the storefront admin panel. Same source
            // as the SA panel — both panels are platform UI.
            ->favicon(asset('favicon.ico'))
            // Header logo. Resolved per-request (closures run at render time,
            // after auth) so a merchant who uploaded an admin logo in Store
            // Settings sees their own mark; everyone else gets the default
            // Ganvo lockup. The login screen has no auth context yet, so it
            // shows the Ganvo default.
            ->brandLogo(fn (): string => auth()->user()?->tenant?->store?->adminLogoUrl()
                ?? asset('images/brand/logo-full-black.png'))
            ->darkModeBrandLogo(fn (): string => auth()->user()?->tenant?->store?->adminLogoUrl()
                ?? asset('images/brand/logo-full-white.png'))
            ->brandLogoHeight('2rem')
            ->colors([
                'primary' => Color::Emerald,
            ])
            // Per-merchant accent: override Filament's primary palette with a
            // ramp generated from the merchant's chosen hex. Injected after
            // Filament's own stylesheet so it cascades over the Emerald default.
            ->renderHook(
                PanelsRenderHook::STYLES_AFTER,
                function (): string {
                    $hex = auth()->user()?->tenant?->store?->adminAccentColor();
                    if (! $hex) {
                        return '';
                    }

                    return '<style id="ganvo-admin-accent">'
                        . \App\Support\AccentPalette::css($hex)
                        . '</style>';
                }
            )
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
