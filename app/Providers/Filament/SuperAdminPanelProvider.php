<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\SuperAdmin\Widgets\InquiryStats;
use App\Filament\SuperAdmin\Widgets\PlatformStats;
use App\Filament\SuperAdmin\Widgets\RecentInquiries;
use App\Filament\SuperAdmin\Widgets\RecentOrders;
use App\Filament\SuperAdmin\Widgets\RecentTenants;
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

class SuperAdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('super')
            ->path('super')
            ->domain(config('ganvo.central_domain'))
            ->login()
            ->brandName('Ganvo Admin')
            // Browser tab icon for the admin panel. Reads from
            // public/favicon.ico; the asset() helper handles versioned
            // URL generation if asset hashing is in play.
            ->favicon(asset('favicon.ico'))
            // Header + login-screen logo. Same PNGs the coming-soon page
            // and storefronts use, so the brand is consistent everywhere.
            // Filament picks the dark-mode variant automatically when the
            // panel is in dark mode.
            ->brandLogo(asset('images/brand/logo-full-black.png'))
            ->darkModeBrandLogo(asset('images/brand/logo-full-white.png'))
            ->brandLogoHeight('2rem')
            ->colors([
                'primary' => Color::Indigo,
            ])
            ->discoverResources(in: app_path('Filament/SuperAdmin/Resources'), for: 'App\\Filament\\SuperAdmin\\Resources')
            ->discoverPages(in: app_path('Filament/SuperAdmin/Pages'), for: 'App\\Filament\\SuperAdmin\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/SuperAdmin/Widgets'), for: 'App\\Filament\\SuperAdmin\\Widgets')
            ->widgets([
                InquiryStats::class,
                RecentInquiries::class,
                AccountWidget::class,
                PlatformStats::class,
                RecentTenants::class,
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
            ])
            ->renderHook(
                PanelsRenderHook::BODY_START,
                fn (): string => Blade::render('@include(\'filament.impersonation-banner\')')
            );
    }
}
