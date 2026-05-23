<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Services\RoleMatrix;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Stripe\Stripe;
use Stripe\StripeClient;

/**
 * Read-only platform settings dashboard for the SuperAdmin. Shows which
 * environment-driven integrations are configured (Stripe billing, Stripe
 * Connect, mail) and lets the operator run a live ping to verify the
 * credentials actually work against the upstream service.
 *
 * Credentials themselves live in .env — keeping secrets out of the database
 * is intentional (smaller attack surface, simpler key rotation, no need to
 * handle backups + encryption). This page is the operator's check that
 * those env values are wired correctly without having to SSH onto the box.
 */
class PlatformSettings extends Page
{
    protected string $view = 'filament.super-admin.pages.platform-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Platform Settings';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?string $title = 'Platform Settings';

    protected static ?int $navigationSort = 100;

    public static function canAccess(): bool
    {
        return RoleMatrix::canSee(auth()->user(), RoleMatrix::SEC_PLATFORM_SETTINGS);
    }

    /** Most recent Stripe ping result — shown inline below the credentials card. */
    public ?array $stripePingResult = null;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pingStripe')
                ->label('Test Stripe connection')
                ->icon(Heroicon::OutlinedBolt)
                ->color('primary')
                ->action(fn () => $this->runStripePing()),
        ];
    }

    /**
     * Hit Stripe's /v1/account endpoint with the configured secret key. A 200
     * means the key is valid and points at a real account; anything else
     * tells the operator the secret is wrong or the network is blocked.
     */
    public function runStripePing(): void
    {
        $secret = config('cashier.secret');

        if (! $secret) {
            Notification::make()
                ->danger()
                ->title('No Stripe secret configured')
                ->body('Set STRIPE_SECRET in .env first.')
                ->send();
            return;
        }

        try {
            $client = new StripeClient($secret);
            $account = $client->accounts->retrieve();

            $this->stripePingResult = [
                'ok' => true,
                'account_id' => $account->id,
                'account_name' => $account->settings?->dashboard?->display_name ?? $account->business_profile?->name ?? '—',
                'country' => $account->country,
                'livemode' => str_starts_with($secret, 'sk_live_'),
                'at' => now()->toIso8601String(),
            ];

            Notification::make()
                ->success()
                ->title('Stripe credentials work')
                ->body('Connected to ' . $account->id . ($this->stripePingResult['livemode'] ? ' (live mode)' : ' (test mode)'))
                ->send();
        } catch (\Throwable $e) {
            $this->stripePingResult = [
                'ok' => false,
                'error' => $e->getMessage(),
                'at' => now()->toIso8601String(),
            ];

            Notification::make()
                ->danger()
                ->title('Stripe ping failed')
                ->body($e->getMessage())
                ->send();
        }
    }

    public function getViewData(): array
    {
        return [
            'stripe' => [
                'key' => $this->maskCredential((string) config('cashier.key')),
                'secret' => $this->maskCredential((string) config('cashier.secret')),
                'webhook_secret' => $this->maskCredential((string) config('cashier.webhook.secret')),
                'currency' => config('cashier.currency'),
                'has_all' => filled(config('cashier.key'))
                    && filled(config('cashier.secret'))
                    && filled(config('cashier.webhook.secret')),
                'livemode' => str_starts_with((string) config('cashier.secret'), 'sk_live_'),
                'webhook_url' => url(config('cashier.path', 'stripe') . '/webhook'),
            ],
            'mail' => [
                'mailer' => config('mail.default'),
                'from_address' => config('mail.from.address'),
            ],
            'app' => [
                'env' => app()->environment(),
                'url' => config('app.url'),
                'debug' => (bool) config('app.debug'),
            ],
            'ping' => $this->stripePingResult,
        ];
    }

    /**
     * Masks a credential for display — shows the first 7 chars (enough to
     * confirm prefix like 'sk_test_' or 'whsec_') then the last 4, hiding
     * everything in between. Returns an empty-state sentinel when the env
     * var isn't set so the UI can render a clear "missing" badge.
     */
    private function maskCredential(string $value): ?string
    {
        if ($value === '') {
            return null;
        }
        if (strlen($value) <= 12) {
            // Too short to meaningfully mask — show the type prefix only.
            return substr($value, 0, 7) . '••••';
        }
        return substr($value, 0, 7) . '••••' . substr($value, -4);
    }
}
