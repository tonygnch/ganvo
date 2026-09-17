<?php

namespace Tests\Feature;

use App\Filament\StoreAdmin\Pages\CustomizeTheme;
use App\Models\RackPart;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use App\Themes\ThemeCustomizer;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The landing page's way into the rack configurator.
 *
 * It must never point at a configurator the visitor cannot use — switched off,
 * or unable to price anything (which is a 404 there) — and the example price
 * it quotes must be the real one for the shop's default rack.
 */
class RackHomeBandTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private Tenant $tenant;

    private string $host;

    protected function setUp(): void
    {
        parent::setUp();

        // each test's store gets the same id, and the theme settings are cached per store id
        ThemeCustomizer::flush();

        $tenant = $this->tenant = Tenant::create(['name' => 'Sankevi Test', 'slug' => 'sankevi-test', 'status' => Tenant::STATUS_ACTIVE]);
        $this->store = Store::create([
            'tenant_id' => $tenant->id,
            'theme' => 'sankevi',
            'currency' => 'EUR',
            'is_live' => true,
            'order_flow' => Store::FLOW_ENQUIRY,
            'rack_configurator' => ['enabled' => true, 'vat_rate_bp' => 2000],
        ]);

        foreach ([150, 180, 210, 240, 300] as $h) {
            foreach ([30, 40, 50, 60] as $d) {
                RackPart::create(['tenant_id' => $tenant->id, 'kind' => 'frame', 'height_cm' => $h, 'depth_cm' => $d, 'price_cents' => 2355]);
            }
        }
        foreach ([77, 97, 117] as $w) {
            foreach ([29, 39, 49, 59] as $d) {
                RackPart::create(['tenant_id' => $tenant->id, 'kind' => 'shelf', 'width_cm' => $w, 'depth_cm' => $d, 'price_cents' => 2370]);
            }
        }
        foreach (['end_pin' => 38, 'extension_pin' => 36, 'cross_brace' => 710] as $kind => $cents) {
            RackPart::create(['tenant_id' => $tenant->id, 'kind' => $kind, 'price_cents' => $cents]);
        }

        app()->instance('current_tenant', $tenant);
        $this->host = 'http://sankevi-test.'.config('ganvo.central_domain');
    }

    public function test_the_home_page_leads_to_the_configurator(): void
    {
        $this->get($this->host.'/')
            ->assertOk()
            ->assertSee('class="rackband"', false)
            ->assertSee(__('site.storefront.sankevi.rack_band_cta'))
            ->assertSee('href="/configurator"', false)
            // the photograph the theme ships with, until the merchant uploads their own
            ->assertSee('/images/demo/sankevi/racks.webp', false);
    }

    public function test_the_merchant_can_put_their_own_photograph_in_the_band(): void
    {
        $this->store->update(['theme_settings' => ['themes' => ['sankevi' => ['images' => ['rack_band_image' => 'theme-images/racks-of-ours.webp']]]]]);

        $this->get($this->host.'/')
            ->assertOk()
            ->assertSee('theme-images/racks-of-ours.webp', false)
            ->assertDontSee('/images/demo/sankevi/racks.webp', false);
    }

    public function test_there_is_no_band_while_the_configurator_is_switched_off(): void
    {
        $this->store->update(['rack_configurator' => ['enabled' => false]]);

        $this->get($this->host.'/')->assertOk()->assertDontSee('class="rackband"', false);
    }

    public function test_there_is_no_band_when_nothing_can_be_priced(): void
    {
        RackPart::where('kind', 'frame')->update(['is_active' => false]);

        $this->get($this->host.'/')->assertOk()->assertDontSee('class="rackband"', false);
    }

    public function test_the_merchant_can_switch_the_band_off(): void
    {
        $this->store->update(['theme_settings' => ['themes' => ['sankevi' => ['sections' => ['rack_band' => false]]]]]);

        $this->get($this->host.'/')->assertOk()->assertDontSee('class="rackband"', false);
    }

    /**
     * And every part of it is the merchant's: the band's own switch, its
     * words and its photograph are fields on „Персонализирай темата“, and what
     * they save there is what the landing page shows.
     */
    public function test_the_band_is_editable_from_the_admin(): void
    {
        $owner = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Owner',
            'email' => 'owner@sankevi-test.test',
            'password' => bcrypt(str()->random(32)),
        ]);
        Filament::setCurrentPanel('store');
        $this->actingAs($owner);

        Livewire::test(CustomizeTheme::class, ['themeSlug' => 'sankevi'])
            ->assertFormFieldExists('section_rack_band')
            ->assertFormFieldExists('content_rack_band_h2_html')
            ->assertFormFieldExists('content_rack_band_lead')
            ->assertFormFieldExists('content_rack_band_1_h')
            ->assertFormFieldExists('content_rack_band_cta')
            ->assertFormFieldExists('image_rack_band_image')
            ->set('data.content_rack_band_h2_html', 'Сглоби <em>рафтовете си</em>')
            ->set('data.content_rack_band_cta', 'Към конфигуратора')
            ->set('data.image_rack_band_image', ['theme-images/our-racks.webp'])
            ->call('save')
            ->assertHasNoErrors();

        $saved = $this->store->fresh()->theme_settings;
        $this->assertSame('Сглоби <em>рафтовете си</em>', data_get($saved, 'themes.sankevi.content.rack_band_h2_html'));
        $this->assertSame('Към конфигуратора', data_get($saved, 'themes.sankevi.content.rack_band_cta'));
        $this->assertSame('theme-images/our-racks.webp', data_get($saved, 'themes.sankevi.images.rack_band_image'));

        ThemeCustomizer::flush();
        $this->get($this->host.'/')
            ->assertOk()
            ->assertSee('Сглоби <em>рафтовете си</em>', false)
            ->assertSee('Към конфигуратора')
            ->assertSee('theme-images/our-racks.webp', false);
    }
}
