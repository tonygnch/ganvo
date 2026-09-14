<?php

namespace Tests\Feature;

use App\Filament\StoreAdmin\Pages\StoreSettings;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use App\Themes\ThemeRegistry;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Which themes a shop may choose, and the footer credit every theme carries.
 *
 * Ganvo builds shops for clients, so not every design is a template: Sankevi's
 * theme is Sankevi's brand, and the older, thinner themes are no longer offered
 * to anyone new. Visibility decides only what can be CHOSEN — onboarding, Store
 * Settings, the onboarding preview. It must never take a design away from a
 * shop that already has it, and it must hold on the server, not just in a list.
 *
 * The footer test pins a live bug: every layout built the "Powered by Ganvo"
 * link as http://<central domain>:8000, so real customers were sent to a port
 * that only exists on a developer's laptop.
 */
class ThemeVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private const PUBLIC_THEMES = ['posy', 'wick', 'forma', 'timber'];

    // The container runs with APP_ENV=local, so CSRF stays on in tests; post the
    // token the session holds, as RackCheckoutTest does.
    private const TOKEN = 'theme-test-token';

    private function central(): string
    {
        return 'http://'.config('ganvo.central_domain');
    }

    private function makeShop(string $slug, string $theme, string $step = 'theme'): User
    {
        $tenant = Tenant::create([
            'name' => $slug,
            'slug' => $slug,
            'status' => Tenant::STATUS_ACTIVE,
            'onboarding_step' => $step,
        ]);

        Store::create([
            'tenant_id' => $tenant->id,
            'theme' => $theme,
            'currency' => 'EUR',
            'is_live' => true,
        ]);

        return User::create([
            'tenant_id' => $tenant->id,
            'name' => "{$slug} owner",
            'email' => "owner@{$slug}.test",
            'password' => bcrypt(str()->random(32)),
        ]);
    }

    private function pickTheme(string $theme)
    {
        return $this->withSession(['_token' => self::TOKEN])
            ->post($this->central().'/onboarding/theme', ['_token' => self::TOKEN, 'theme' => $theme]);
    }

    public function test_the_registry_offers_public_themes_plus_a_tenants_own(): void
    {
        $acme = $this->makeShop('acme-test', 'default')->tenant;
        $sankevi = $this->makeShop('sankevi', 'sankevi')->tenant;

        $this->assertSame(self::PUBLIC_THEMES, ThemeRegistry::selectableIds($acme));
        $this->assertSame([...self::PUBLIC_THEMES, 'sankevi'], ThemeRegistry::selectableIds($sankevi));

        // A shop already on a hidden theme keeps it on the list.
        $this->assertContains('brick', ThemeRegistry::selectableIds($acme, 'brick'));
    }

    public function test_onboarding_lists_only_the_themes_this_shop_may_pick(): void
    {
        $this->actingAs($this->makeShop('acme-test', 'default'));

        $page = $this->get($this->central().'/onboarding/theme')->assertOk();

        foreach (self::PUBLIC_THEMES as $id) {
            $page->assertSee('value="'.$id.'"', false);
        }
        foreach (['default', 'brick', 'kiln', 'ember', 'tech', 'minimal', 'gallery', 'menu', 'sankevi'] as $id) {
            $page->assertDontSee('value="'.$id.'"', false);
        }
    }

    public function test_onboarding_refuses_a_hidden_or_someone_elses_theme(): void
    {
        $user = $this->makeShop('acme-test', 'default');
        $this->actingAs($user);

        foreach (['brick', 'sankevi'] as $refused) {
            $this->pickTheme($refused)->assertSessionHasErrors('theme');
        }
        $this->assertSame('default', $user->tenant->store->fresh()->theme);

        $this->pickTheme('wick')->assertSessionHasNoErrors();
        $this->assertSame('wick', $user->tenant->store->fresh()->theme);
    }

    public function test_sankevi_may_pick_its_own_theme(): void
    {
        $user = $this->makeShop('sankevi', 'default');
        $this->actingAs($user);

        $this->get($this->central().'/onboarding/theme')->assertSee('value="sankevi"', false);
        $this->pickTheme('sankevi')->assertSessionHasNoErrors();
        $this->assertSame('sankevi', $user->tenant->store->fresh()->theme);
    }

    public function test_another_clients_private_theme_cannot_be_previewed(): void
    {
        $this->actingAs($this->makeShop('acme-test', 'default'));

        $this->get($this->central().'/onboarding/theme/preview/sankevi')->assertNotFound();
        $this->get($this->central().'/onboarding/theme/preview/brick')->assertNotFound();
    }

    public function test_store_settings_keeps_a_hidden_theme_but_refuses_switching_to_one(): void
    {
        $user = $this->makeShop('relic-test', 'brick', step: 'done');
        $this->actingAs($user);
        Filament::setCurrentPanel('store');

        Livewire::test(StoreSettings::class)->assertSchemaStateSet(['theme' => 'brick']);

        // set() rather than fillForm(): the form drops a value its radio does not
        // offer, and the point is what the server does with a forged request.
        Livewire::test(StoreSettings::class)
            ->set('data.theme', 'kiln')
            ->call('save')
            ->assertHasFormErrors(['theme']);
        $this->assertSame('brick', $user->tenant->store->fresh()->theme);

        Livewire::test(StoreSettings::class)
            ->set('data.theme', 'wick')
            ->call('save')
            ->assertHasNoFormErrors();
        $this->assertSame('wick', $user->tenant->store->fresh()->theme);
    }

    public static function everyTheme(): array
    {
        return array_combine(ThemeRegistry::ids(), array_map(fn ($id) => [$id], ThemeRegistry::ids()));
    }

    #[DataProvider('everyTheme')]
    public function test_the_footer_credit_links_to_the_studio_without_the_dev_port(string $theme): void
    {
        $this->makeShop('footer-test', $theme, step: 'done');

        $central = config('ganvo.central_domain');

        $this->get('http://footer-test.'.$central.'/')
            ->assertOk()
            ->assertSee('href="http://'.$central.'"', false)
            ->assertDontSee($central.':8000', false);
    }
}
