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
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
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
            // Per-merchant admin accent, done the only way that actually works
            // in Filament v5:
            //
            //   * We must NOT use ->colors(closure): Panel::boot() eagerly
            //     evaluates panel colours while *registering* them, and boot
            //     runs in the SetUpPanel middleware before Authenticate — so
            //     auth() is null and the merchant's colour is never read.
            //   * FilamentColor::register(Closure) keeps the closure and only
            //     evaluates it in ColorManager::getColors(), which runs at
            //     @filamentStyles render time — AFTER Authenticate. Good.
            //   * BUT registration order matters: getColors() is last-write-
            //     wins per colour name. If we registered our closure in the
            //     provider's register(), the panel's own boot-time colour
            //     registration would land later and overwrite it. So we
            //     register from bootUsing(), which runs at the END of
            //     Panel::boot() — after the panel's own colour registration —
            //     and fold the Emerald fallback into this single closure so
            //     nothing can override it.
            //
            // Returning a real palette via Color::hex() (not a CSS-variable
            // override) is also what keeps button text legible: Filament runs
            // its WCAG contrast check against the actual colour to pick the
            // text shade. Unusable accents (near-black/white/grey, per
            // Store::adminAccentColor()) fall back to Emerald.
            ->bootUsing(function (): void {
                FilamentColor::register(function (): array {
                    $hex = auth()->user()?->tenant?->store?->adminAccentColor();

                    return [
                        'primary' => $hex ? Color::hex($hex) : Color::Emerald,
                    ];
                });
            })
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
            /*
             | GROUP ORDER. Declaring the groups here is what fixes their
             | sequence — otherwise Filament orders them by whichever item it
             | happened to register first, which is alphabetical by class and
             | means nothing to a shop owner.
             |
             | The order is the working day: what came in from customers, then
             | what you sell, then how the shop looks, then the money side with
             | Ganvo. Dashboard stays outside any group, pinned at the top.
             |
             | Closure labels, because a panel definition is cacheable and a
             | string baked in at cache time would freeze the language.
             */
            ->navigationGroups([
                NavigationGroup::make(fn (): string => __('admin.nav.group.sales')),
                NavigationGroup::make(fn (): string => __('admin.nav.group.catalog')),
                NavigationGroup::make(fn (): string => __('admin.nav.group.shop')),
                NavigationGroup::make(fn (): string => __('admin.nav.group.account')),
            ])

            /*
             | LANGUAGE SWITCHER, in the user menu behind the avatar.
             |
             | Each language names ITSELF — "Български", never "Bulgarian" —
             | because someone who has landed in a language they cannot read
             | needs to recognise their own in the list, and a translated
             | language name is exactly the thing they would not recognise.
             | The one in use is ticked rather than hidden, so the menu shows
             | what is selected instead of only what is not.
             |
             | Closures throughout: panel definitions are cacheable
             | (filament:cache-components), and a value baked in at cache time
             | would pin every merchant to whoever warmed the cache.
             */
            ->userMenuItems(collect(\App\Support\PanelLocale::SUPPORTED)
                ->map(fn (string $native, string $code): MenuItem => MenuItem::make()
                    ->label(fn (): string => $native)
                    ->icon(fn (): string => \App\Support\PanelLocale::forUser(auth()->user()) === $code
                        ? 'heroicon-m-check'
                        : 'heroicon-m-language')
                    ->url(fn (): string => route('panel.language', $code))
                    ->sort(90))
                ->values()
                ->all())
            ->middleware([
                // The preview lock is prepended to the `web` group, which a
                // Filament panel does not use — it builds its own stack. Without
                // this the admin login pages stayed publicly reachable while the
                // rest of the site was behind the gate.
                \App\Http\Middleware\PreviewGate::class,
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                // After the session, so it can read the signed-in merchant's
                // saved language — and in this stack rather than authMiddleware
                // so the login screen is translated too, where there is no user
                // yet and the server default applies.
                \App\Http\Middleware\SetPanelLocale::class,
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
