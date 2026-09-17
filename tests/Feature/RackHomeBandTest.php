<?php

namespace Tests\Feature;

use App\Models\RackPart;
use App\Models\Store;
use App\Models\Tenant;
use App\Services\Money;
use App\Themes\ThemeCustomizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    private string $host;

    protected function setUp(): void
    {
        parent::setUp();

        // each test's store gets the same id, and the theme settings are cached per store id
        ThemeCustomizer::flush();

        $tenant = Tenant::create(['name' => 'Sankevi Test', 'slug' => 'sankevi-test', 'status' => Tenant::STATUS_ACTIVE]);
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

    public function test_the_home_page_leads_to_the_configurator_with_the_real_price_of_the_default_rack(): void
    {
        // The default rack: 210 × 60, one 100 cm section, four levels —
        // 2 frames, 4 shelves, 16 end pins and a brace, plus 20% VAT.
        $net = 2 * 2355 + 4 * 2370 + 16 * 38 + 710;
        $total = $net + (int) round($net * 0.2);

        $this->get($this->host.'/')
            ->assertOk()
            ->assertSee('class="rackband"', false)
            ->assertSee(__('site.storefront.sankevi.rack_band_cta'))
            ->assertSee(Money::display($total, 1.0, 'EUR'));
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
}
