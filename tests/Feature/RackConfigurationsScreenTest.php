<?php

namespace Tests\Feature;

use App\Filament\StoreAdmin\Resources\RackConfigurations\Pages\ListRackConfigurations;
use App\Filament\StoreAdmin\Resources\RackConfigurations\RackConfigurationResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RackConfiguration;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The merchant's view of what customers have drawn.
 *
 * Until this screen existed a saved rack was reachable only by its share code,
 * which the merchant never sees — so the interesting question this answers is
 * "who priced a rack and walked away". Two things therefore matter here and are
 * asserted: the list is scoped to one tenant (another yard's racks are another
 * yard's business), and the "ordered / saved only" state is real.
 */
class RackConfigurationsScreenTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = $this->makeTenant('sankevi-test', rackEnabled: true);
        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Sankevi Owner',
            'email' => 'owner@sankevi-test.test',
            'password' => bcrypt(str()->random(32)),
        ]);

        Filament::setCurrentPanel('store');
        $this->actingAs($this->user);
    }

    private function makeTenant(string $slug, bool $rackEnabled): Tenant
    {
        $tenant = Tenant::create([
            'name' => $slug,
            'slug' => $slug,
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        Store::create([
            'tenant_id' => $tenant->id,
            'theme' => 'sankevi',
            'currency' => 'EUR',
            'is_live' => true,
            'order_flow' => Store::FLOW_ENQUIRY,
            'rack_configurator' => ['enabled' => $rackEnabled, 'vat_rate_bp' => 2000],
        ]);

        return $tenant;
    }

    private function makeRack(Tenant $tenant, string $code, int $totalCents = 48557): RackConfiguration
    {
        return RackConfiguration::create([
            'tenant_id' => $tenant->id,
            'code' => $code,
            'height_cm' => 210,
            'depth_cm' => 60,
            'levels' => 4,
            'segments' => [100, 100, 80],
            'bom' => [],
            'subtotal_cents' => (int) round($totalCents / 1.2),
            'vat_cents' => $totalCents - (int) round($totalCents / 1.2),
            'total_cents' => $totalCents,
            'vat_rate_bp' => 2000,
            'currency' => 'EUR',
        ]);
    }

    public function test_it_lists_this_tenants_racks_and_nobody_elses(): void
    {
        $mine = $this->makeRack($this->tenant, 'MINE0001');
        $theirs = $this->makeRack($this->makeTenant('other-yard', rackEnabled: true), 'THEIRS01');

        Livewire::test(ListRackConfigurations::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$mine])
            ->assertCanNotSeeTableRecords([$theirs]);
    }

    public function test_it_renders_the_bays_the_customer_laid_out(): void
    {
        $rack = $this->makeRack($this->tenant, 'BAYS0001');

        Livewire::test(ListRackConfigurations::class)
            ->assertTableColumnStateSet('segments', '100 + 100 + 80', $rack);
    }

    public function test_a_rack_that_reached_an_order_reads_as_ordered(): void
    {
        $saved = $this->makeRack($this->tenant, 'SAVED001');
        $ordered = $this->makeRack($this->tenant, 'ORDER001');

        $order = Order::create([
            'tenant_id' => $this->tenant->id,
            'order_number' => 'T-1',
            'status' => 'pending',
            'currency' => 'EUR',
            'subtotal_cents' => $ordered->total_cents,
            'total_cents' => $ordered->total_cents,
            'customer_email' => 'buyer@example.test',
        ]);
        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => null,
            'rack_configuration_id' => $ordered->id,
            'product_name' => 'Стелаж',
            'quantity' => 1,
            'unit_price_cents' => $ordered->total_cents,
            'subtotal_cents' => $ordered->total_cents,
        ]);

        Livewire::test(ListRackConfigurations::class)
            ->assertTableColumnStateSet('order_items_count', __('admin.rack_configs.opt.ordered'), $ordered)
            ->assertTableColumnStateSet('order_items_count', __('admin.rack_configs.opt.saved_only'), $saved)
            ->filterTable('ordered', true)
            ->assertCanSeeTableRecords([$ordered])
            ->assertCanNotSeeTableRecords([$saved]);
    }

    public function test_the_open_action_points_at_the_share_link(): void
    {
        $rack = $this->makeRack($this->tenant, 'OPEN0001');

        $url = RackConfigurationResource::storefrontBase().'/configurator/OPEN0001';

        Livewire::test(ListRackConfigurations::class)
            ->assertTableActionHasUrl('open', $url, $rack);
    }

    public function test_the_screen_is_gone_when_the_configurator_is_off(): void
    {
        $store = $this->tenant->store;
        $store->rack_configurator = ['enabled' => false];
        $store->save();
        $this->tenant->refresh();

        $this->assertFalse(RackConfigurationResource::canViewAny());
        $this->assertFalse(RackConfigurationResource::shouldRegisterNavigation());
    }

    public function test_nobody_can_type_a_rack_here(): void
    {
        $rack = $this->makeRack($this->tenant, 'NOEDIT01');

        $this->assertFalse(RackConfigurationResource::canCreate());
        $this->assertFalse(RackConfigurationResource::canEdit($rack));
    }
}
