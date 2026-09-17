<?php

namespace Tests\Feature;

use App\Filament\StoreAdmin\Pages\RackConfigurator;
use App\Models\RackPart;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The admin price screen's Save, walked the way the merchant uses it.
 *
 * The screen holds every price of every rack type at once, and a Save writes
 * all of them back. So the one thing it must never do is change a price
 * nobody touched — a blank that was not typed is a size taken off sale.
 */
class RackPriceScreenTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create(['name' => 'Sankevi Test', 'slug' => 'sankevi-test', 'status' => Tenant::STATUS_ACTIVE]);
        $this->store = Store::create([
            'tenant_id' => $this->tenant->id,
            'theme' => 'sankevi',
            'currency' => 'EUR',
            'is_live' => true,
            'order_flow' => Store::FLOW_ENQUIRY,
            'rack_configurator' => ['enabled' => true, 'vat_rate_bp' => 2000],
        ]);

        // Every type fully priced, each at its own figure, as the migration leaves a shop.
        foreach (['single' => 2000, 'office' => 3000, 'wine' => 4000] as $type => $base) {
            foreach ([150, 180, 210, 240, 300] as $h) {
                foreach ([30, 40, 50, 60] as $d) {
                    RackPart::create(['tenant_id' => $this->tenant->id, 'kind' => 'frame', 'rack_type' => $type, 'height_cm' => $h, 'depth_cm' => $d, 'price_cents' => $base + $h + $d]);
                }
            }
            $board = $type === 'wine' ? 'wine_tray' : 'shelf';
            foreach ([77, 97, 117] as $w) {
                foreach ([29, 39, 49, 59] as $d) {
                    RackPart::create(['tenant_id' => $this->tenant->id, 'kind' => $board, 'rack_type' => $type, 'width_cm' => $w, 'depth_cm' => $d, 'price_cents' => $base + $w + $d]);
                }
            }
        }
        foreach (['end_pin' => 38, 'extension_pin' => 36, 'cross_brace' => 710] as $kind => $cents) {
            RackPart::create(['tenant_id' => $this->tenant->id, 'kind' => $kind, 'price_cents' => $cents]);
        }

        app()->instance('current_tenant', $this->tenant);
        $owner = User::create(['tenant_id' => $this->tenant->id, 'name' => 'Owner', 'email' => 'owner@sankevi-test.test', 'password' => bcrypt(str()->random(32))]);
        Filament::setCurrentPanel('store');
        $this->actingAs($owner);
    }

    /** kind:type:a:b => [price, active], for comparing the whole book */
    private function book(): array
    {
        return RackPart::where('tenant_id', $this->tenant->id)->get()
            ->mapWithKeys(fn (RackPart $p) => [$p->lookupKey() => [$p->price_cents, (bool) $p->is_active]])
            ->sortKeys()->all();
    }

    public function test_the_screen_shows_every_types_prices(): void
    {
        Livewire::test(RackConfigurator::class)
            ->assertSet('data.prices.frame.single.210x60', '22.70')
            ->assertSet('data.prices.frame.office.210x60', '32.70')
            ->assertSet('data.prices.frame.wine.210x60', '42.70')
            ->assertSet('data.prices.shelf.office.97x59', '31.56')
            ->assertSet('data.prices.wine_tray.wine.97x59', '41.56')
            ->assertSet('data.part_cross_brace', '7.10');
    }

    public function test_saving_without_changes_changes_nothing(): void
    {
        $before = $this->book();

        Livewire::test(RackConfigurator::class)->call('save')->assertHasNoErrors();

        $this->assertSame($before, $this->book());
    }

    public function test_saving_twice_changes_nothing(): void
    {
        $before = $this->book();

        Livewire::test(RackConfigurator::class)->call('save')->assertHasNoErrors()->call('save')->assertHasNoErrors();

        $this->assertSame($before, $this->book());
    }

    public function test_one_changed_cell_changes_one_price(): void
    {
        $before = $this->book();

        Livewire::test(RackConfigurator::class)
            ->set('data.prices.frame.wine.210x60', '50.00')
            ->call('save')
            ->assertHasNoErrors();

        $expected = $before;
        $expected['frame:wine:210:60'] = [5000, true];
        $this->assertSame($expected, $this->book());
    }

    public function test_a_blanked_cell_takes_only_that_size_off_sale(): void
    {
        $before = $this->book();

        Livewire::test(RackConfigurator::class)
            ->set('data.prices.frame.office.300x60', '')
            ->call('save')
            ->assertHasNoErrors();

        $expected = $before;
        $expected['frame:office:300:60'] = [0, false];
        $this->assertSame($expected, $this->book());
    }

    public function test_a_changed_trim_moves_every_board_with_its_price(): void
    {
        Livewire::test(RackConfigurator::class)
            ->set('data.shelf_width_trim_cm', 4)
            ->call('save')
            ->assertHasNoErrors()
            // the screen redraws at the new board sizes, prices intact
            ->assertSet('data.prices.shelf.single.96x59', number_format((2000 + 97 + 59) / 100, 2, '.', ''))
            ->assertSet('data.prices.wine_tray.wine.96x59', number_format((4000 + 97 + 59) / 100, 2, '.', ''));

        $this->assertSame(2000 + 97 + 59, RackPart::where(['kind' => 'shelf', 'rack_type' => 'single', 'width_cm' => 96, 'depth_cm' => 59])->value('price_cents'));
        $this->assertSame(3000 + 97 + 59, RackPart::where(['kind' => 'shelf', 'rack_type' => 'office', 'width_cm' => 96, 'depth_cm' => 59])->value('price_cents'));
        $this->assertFalse(RackPart::where(['kind' => 'shelf', 'width_cm' => 97])->exists(), 'no board left behind at the old size');
    }

    public function test_a_price_typed_in_the_same_save_as_a_trim_change_is_kept(): void
    {
        Livewire::test(RackConfigurator::class)
            ->set('data.prices.shelf.single.97x59', '99.00')
            ->set('data.shelf_width_trim_cm', 4)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(9900, RackPart::where(['kind' => 'shelf', 'rack_type' => 'single', 'width_cm' => 96, 'depth_cm' => 59])->value('price_cents'));
    }

    public function test_a_size_added_gets_a_row_and_is_priced_on_the_next_save(): void
    {
        $page = Livewire::test(RackConfigurator::class)
            ->set('data.heights', ['150', '180', '210', '240', '270', '300'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('data.prices.frame.single.270x60', null);

        $this->assertContains(270, $this->store->fresh()->rackConfigurator()['heights']);

        $page->set('data.prices.frame.single.270x60', '25.00')->call('save')->assertHasNoErrors();

        $this->assertSame(2500, RackPart::where(['kind' => 'frame', 'rack_type' => 'single', 'height_cm' => 270, 'depth_cm' => 60])->value('price_cents'));
    }

    public function test_a_size_removed_and_saved_does_not_blank_the_rest(): void
    {
        $before = $this->book();

        Livewire::test(RackConfigurator::class)
            ->set('data.depths', ['30', '40', '60'])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame([30, 40, 60], $this->store->fresh()->rackConfigurator()['depths']);
        $this->assertSame($before, $this->book(), 'the 50 cm prices are kept for if it comes back');
    }

    public function test_a_price_that_is_not_a_number_saves_nothing(): void
    {
        $before = $this->book();

        Livewire::test(RackConfigurator::class)
            ->set('data.prices.frame.single.210x60', 'abc')
            ->set('data.vat_rate', 21)
            ->call('save');

        $this->assertSame($before, $this->book());
        $this->assertSame(2000, $this->store->fresh()->rackConfigurator()['vat_rate_bp'], 'nor the settings');
    }
}
