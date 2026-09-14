<?php

namespace Tests\Feature;

use App\Filament\StoreAdmin\Pages\RackConfigurator;
use App\Models\Customer;
use App\Models\RackConfiguration;
use App\Models\RackPart;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Rack\RackConfig;
use App\Services\Rack\RackPresenter;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The rack models: the plain rack, the office rack and the wine rack.
 *
 * They share frames, pins and braces and differ in their boards. A wine rack
 * swaps every shelf for a tray, which has prices of its own; an office rack
 * gives one level of each section to a desk — a deeper shelf the customer
 * chooses, priced from the ordinary shelf table. What these tests hold on to:
 * a model that cannot be priced is never offered, the model and its desk are
 * part of what a saved rack IS, and the admin screen is where tray prices
 * come from.
 */
class RackModelsTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private string $host;

    private const TOKEN = 'rack-models-test-token';

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Sankevi Test', 'slug' => 'sankevi-test', 'status' => Tenant::STATUS_ACTIVE]);
        Store::create([
            'tenant_id' => $this->tenant->id,
            'theme' => 'sankevi',
            'currency' => 'EUR',
            'is_live' => true,
            'order_flow' => Store::FLOW_ENQUIRY,
            'rack_configurator' => ['enabled' => true, 'vat_rate_bp' => 2000],
        ]);

        // The plain rack's price book — frames, shelves, fasteners — and no wine trays.
        foreach ([150, 180, 210, 240, 300] as $h) {
            foreach ([30, 40, 50, 60] as $d) {
                RackPart::create(['tenant_id' => $this->tenant->id, 'kind' => 'frame', 'height_cm' => $h, 'depth_cm' => $d, 'price_cents' => 2355]);
            }
        }
        foreach ($this->boardSizes() as [$w, $d]) {
            // a price that depends on the depth, so a desk priced at the wrong depth shows
            RackPart::create(['tenant_id' => $this->tenant->id, 'kind' => 'shelf', 'width_cm' => $w, 'depth_cm' => $d, 'price_cents' => 1000 + $d * 10]);
        }
        foreach (['end_pin' => 38, 'extension_pin' => 36, 'cross_brace' => 710] as $kind => $cents) {
            RackPart::create(['tenant_id' => $this->tenant->id, 'kind' => $kind, 'price_cents' => $cents]);
        }

        app()->instance('current_tenant', $this->tenant);
        $this->host = 'http://sankevi-test.'.config('ganvo.central_domain');
    }

    /** Every real board size the default bays and depths call for. */
    private function boardSizes(): array
    {
        $out = [];
        foreach ([77, 97, 117] as $w) {
            foreach ([29, 39, 49, 59] as $d) {
                $out[] = [$w, $d];
            }
        }

        return $out;
    }

    private function priceWineTrays(): void
    {
        foreach ($this->boardSizes() as [$w, $d]) {
            RackPart::create(['tenant_id' => $this->tenant->id, 'kind' => 'wine_tray', 'width_cm' => $w, 'depth_cm' => $d, 'price_cents' => 3100]);
        }
    }

    private function send(string $path, array $body = [])
    {
        return $this->withSession(['_token' => self::TOKEN])
            ->withHeader('X-CSRF-TOKEN', self::TOKEN)
            ->postJson($this->host.$path, $body);
    }

    private function boot($response): array
    {
        $response->assertOk();
        $this->assertSame(1, preg_match("/data-boot='([^']+)'/", $response->getContent(), $m), 'no boot payload on the page');

        return json_decode(html_entity_decode($m[1], ENT_QUOTES), true);
    }

    /* ---- what is offered ------------------------------------------- */

    public function test_the_office_rack_is_always_offered_and_the_wine_rack_once_its_trays_are_priced(): void
    {
        $page = $this->get($this->host.'/configurator');
        $this->assertSame(['single', 'office'], $this->boot($page)['limits']['types']);
        $page->assertSee('data-cfg-type="office"', false)->assertDontSee('data-cfg-type="wine"', false);
        $this->assertSame(40, $this->boot($page)['limits']['modelDepth'], 'the office and wine racks open 40 cm deep');

        $this->priceWineTrays();
        $page = $this->get($this->host.'/configurator');
        $this->assertSame(['single', 'office', 'wine'], $this->boot($page)['limits']['types']);
        $page->assertSee('data-cfg-type="wine"', false)->assertSee(__('site.storefront.sankevi.cfg_model_wine'));
    }

    /* ---- pricing ---------------------------------------------------- */

    public function test_an_office_desk_is_priced_as_the_deeper_shelf_the_customer_chose(): void
    {
        $quote = $this->send('/configurator/quote', ['config' => [
            'type' => 'office', 'height' => 210, 'depth' => 40, 'desk_depth' => 60, 'levels' => 4, 'segments' => [100, 100],
        ]])->assertOk();

        $lines = collect($quote->json('lines'));
        $desk = $lines->firstWhere('kind', 'desk_top');
        $this->assertSame(2, $desk['quantity']);
        $this->assertSame(59, $desk['depth_cm']);
        $this->assertSame(1590, $desk['unit_price_cents'], 'the 97×59 shelf price, not the 97×39 one');
        $this->assertSame(6, $lines->firstWhere('kind', 'shelf')['quantity']);
        $this->assertSame(1390, $lines->firstWhere('kind', 'shelf')['unit_price_cents'], 'the rack\'s own shelves stay 97×39');
    }

    public function test_a_wine_rack_nobody_has_priced_is_refused_with_its_own_reason(): void
    {
        $this->send('/configurator/quote', ['config' => ['type' => 'wine', 'height' => 210, 'depth' => 60, 'levels' => 4, 'segments' => [100]]])
            ->assertStatus(422)
            ->assertJson(['ok' => false, 'reason' => __('site.storefront.sankevi.cfg_err_no_model_price')]);
    }

    /* ---- the model is part of the rack ------------------------------ */

    public function test_the_model_and_the_desk_are_saved_and_each_change_is_a_different_rack(): void
    {
        $customer = Customer::create(['tenant_id' => $this->tenant->id, 'name' => 'Иван', 'email' => 'ivan@example.test', 'password' => str()->random(32)]);
        $this->actingAs($customer, 'customer');

        $rack = fn (string $type, ?int $desk = null) => ['config' => array_filter([
            'type' => $type, 'height' => 210, 'depth' => 40, 'desk_depth' => $desk, 'levels' => 4, 'segments' => [100],
        ], fn ($v) => $v !== null)];

        $plain = $this->send('/configurator/save', $rack('single'))->assertOk()->json('code');
        $office60 = $this->send('/configurator/save', $rack('office', 60))->assertOk()->json('code');
        $office50 = $this->send('/configurator/save', $rack('office', 50))->assertOk()->json('code');
        $office60Again = $this->send('/configurator/save', $rack('office', 60))->assertOk()->json('code');

        $this->assertCount(3, array_unique([$plain, $office60, $office50]), 'a different model or desk is a different rack');
        $this->assertSame($office60, $office60Again, 'the same office rack saved twice is one rack');
        $this->assertSame('single', RackConfiguration::where('code', $plain)->value('type'));
        $this->assertNull(RackConfiguration::where('code', $plain)->value('desk_depth_cm'), 'only an office rack has a desk');
        $this->assertSame('office', RackConfiguration::where('code', $office60)->value('type'));
        $this->assertSame(60, RackConfiguration::where('code', $office60)->value('desk_depth_cm'));

        // …and its link reopens the office rack with its desk
        $boot = $this->boot($this->get($this->host.'/configurator/'.$office60));
        $this->assertSame('office', $boot['config']['type']);
        $this->assertSame(60, $boot['config']['desk_depth']);
    }

    public function test_a_rack_is_named_by_its_model(): void
    {
        $this->assertStringStartsWith(
            __('site.storefront.sankevi.cfg_model_office'),
            RackPresenter::rackName(RackConfig::of(210, 40, 4, [100], 'office', 60))
        );
    }

    /* ---- where the tray prices come from ---------------------------- */

    public function test_the_admin_saves_wine_tray_prices_and_a_blank_size_stays_unsold(): void
    {
        $owner = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Owner',
            'email' => 'owner@sankevi-test.test',
            'password' => bcrypt(str()->random(32)),
        ]);
        Filament::setCurrentPanel('store');
        $this->actingAs($owner);

        Livewire::test(RackConfigurator::class)
            ->set('data.wine_tray_97_59', '31.00')
            ->set('data.model_depth_cm', 50)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(50, Store::where('tenant_id', $this->tenant->id)->first()->rackConfigurator()['model_depth_cm']);

        $this->assertSame(3100, RackPart::where(['tenant_id' => $this->tenant->id, 'kind' => 'wine_tray', 'width_cm' => 97, 'depth_cm' => 59])->value('price_cents'));
        $this->assertFalse(
            RackPart::where(['tenant_id' => $this->tenant->id, 'kind' => 'wine_tray', 'width_cm' => 77, 'depth_cm' => 29])->exists(),
            'a size left blank is not sold, and no row is invented for it'
        );
    }

    public function test_the_desk_sections_are_saved_and_each_choice_is_a_different_rack(): void
    {
        $customer = Customer::create(['tenant_id' => $this->tenant->id, 'name' => 'Иван', 'email' => 'ivan@example.test', 'password' => str()->random(32)]);
        $this->actingAs($customer, 'customer');

        $rack = fn (array $desks) => ['config' => [
            'type' => 'office', 'height' => 210, 'depth' => 40, 'desk_depth' => 60, 'levels' => 4, 'segments' => [100, 100], 'desk_sections' => $desks,
        ]];

        $quote = $this->send('/configurator/quote', $rack([1]))->assertOk();
        $this->assertSame(1, collect($quote->json('lines'))->firstWhere('kind', 'desk_top')['quantity']);

        $first = $this->send('/configurator/save', $rack([0]))->assertOk()->json('code');
        $second = $this->send('/configurator/save', $rack([1]))->assertOk()->json('code');
        $both = $this->send('/configurator/save', $rack([0, 1]))->assertOk()->json('code');

        $this->assertCount(3, array_unique([$first, $second, $both]), 'the desk on a different section is a different rack');
        $this->assertSame([1], RackConfiguration::where('code', $second)->first()->deskSectionIndexes());
        $this->assertSame([1], $this->boot($this->get($this->host.'/configurator/'.$second))['config']['desk_sections']);

        $this->send('/configurator/quote', $rack([]))->assertStatus(422);
    }

    /* ---- braces added by hand ---------------------------------------- */

    public function test_a_brace_added_by_hand_is_saved_priced_and_reopened(): void
    {
        $customer = Customer::create(['tenant_id' => $this->tenant->id, 'name' => 'Иван', 'email' => 'ivan@example.test', 'password' => str()->random(32)]);
        $this->actingAs($customer, 'customer');

        $rack = ['height' => 210, 'depth' => 60, 'levels' => 4, 'segments' => [100, 100]];

        $standard = $this->send('/configurator/quote', ['config' => $rack])->assertOk();
        $braced = $this->send('/configurator/quote', ['config' => $rack + ['extra_braces' => [1]]])->assertOk();
        $this->assertSame(1, collect($standard->json('lines'))->firstWhere('kind', 'cross_brace')['quantity']);
        $this->assertSame(2, collect($braced->json('lines'))->firstWhere('kind', 'cross_brace')['quantity']);

        $plain = $this->send('/configurator/save', ['config' => $rack])->assertOk()->json('code');
        $extra = $this->send('/configurator/save', ['config' => $rack + ['extra_braces' => [1]]])->assertOk()->json('code');

        $this->assertNotSame($plain, $extra, 'a brace added by hand makes it a different rack');
        $this->assertSame([1], RackConfiguration::where('code', $extra)->first()->extraBraceIndexes());
        $this->assertSame([1], $this->boot($this->get($this->host.'/configurator/'.$extra))['config']['extra_braces']);
    }
}
