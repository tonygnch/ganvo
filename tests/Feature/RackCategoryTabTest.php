<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\RackPart;
use App\Models\Store;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The „Сглоби стелаж в конфигуратора“ button beside „Още за категорията“
 * shows only on the category pages the merchant picked, and on their
 * subcategories — whether reached as /categories/… or as the shop filtered to
 * one — and nowhere else. The tab that used to sit on the edge of every page
 * is gone.
 */
class RackCategoryTabTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private string $host;

    private Category $racks;

    private Category $wine;

    private Category $decking;

    private const TAB = 'class="catmore-btn catmore-cfg"';

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::create(['name' => 'Sankevi Test', 'slug' => 'sankevi-test', 'status' => Tenant::STATUS_ACTIVE]);
        $this->store = Store::create([
            'tenant_id' => $tenant->id,
            'theme' => 'sankevi',
            'currency' => 'EUR',
            'is_live' => true,
            'order_flow' => Store::FLOW_ENQUIRY,
            'rack_configurator' => ['enabled' => true, 'vat_rate_bp' => 2000],
        ]);

        foreach ([210] as $h) {
            RackPart::create(['tenant_id' => $tenant->id, 'kind' => 'frame', 'height_cm' => $h, 'depth_cm' => 60, 'price_cents' => 2355]);
        }
        RackPart::create(['tenant_id' => $tenant->id, 'kind' => 'shelf', 'width_cm' => 97, 'depth_cm' => 59, 'price_cents' => 2370]);

        $this->racks = Category::create(['tenant_id' => $tenant->id, 'name' => 'Модулни и стелажни системи', 'slug' => 'modulni-i-stelazni-sistemi', 'is_active' => true]);
        $this->wine = Category::create(['tenant_id' => $tenant->id, 'name' => 'Стелаж за вино', 'slug' => 'stelazh-za-vino', 'parent_id' => $this->racks->id, 'is_active' => true]);
        $this->decking = Category::create(['tenant_id' => $tenant->id, 'name' => 'Декинг', 'slug' => 'dekingi', 'is_active' => true]);

        app()->instance('current_tenant', $tenant);
        $this->host = 'http://sankevi-test.'.config('ganvo.central_domain');
    }

    private function chooseRacks(): void
    {
        $this->store->update(['rack_configurator' => ['enabled' => true, 'vat_rate_bp' => 2000, 'tab_category_ids' => [$this->racks->id]]]);
    }

    public function test_the_tab_shows_on_the_chosen_category_and_its_subcategories_only(): void
    {
        $this->chooseRacks();

        $this->get($this->host.'/categories/modulni-i-stelazni-sistemi')->assertOk()->assertSee(self::TAB, false);
        $this->get($this->host.'/categories/stelazh-za-vino')->assertOk()->assertSee(self::TAB, false);
        $this->get($this->host.'/categories/dekingi')->assertOk()->assertDontSee(self::TAB, false);
        $this->get($this->host.'/')->assertOk()->assertDontSee(self::TAB, false)->assertDontSee('class="rack-tab"', false);
    }

    public function test_the_tab_shows_on_the_shop_filtered_to_the_chosen_category(): void
    {
        $this->chooseRacks();

        $this->get($this->host.'/shop?category=modulni-i-stelazni-sistemi')->assertOk()->assertSee(self::TAB, false);
        $this->get($this->host.'/shop?category=stelazh-za-vino')->assertOk()->assertSee(self::TAB, false);
        $this->get($this->host.'/shop?category=dekingi')->assertOk()->assertDontSee(self::TAB, false);
        $this->get($this->host.'/shop')->assertOk()->assertDontSee(self::TAB, false);
        $this->get($this->host.'/shop?category=no-such-category')->assertOk()->assertDontSee(self::TAB, false);
    }

    public function test_no_category_chosen_means_no_tab(): void
    {
        $this->get($this->host.'/categories/modulni-i-stelazni-sistemi')->assertOk()->assertDontSee(self::TAB, false);
    }

    public function test_no_tab_while_the_configurator_is_off(): void
    {
        $this->store->update(['rack_configurator' => ['enabled' => false, 'tab_category_ids' => [$this->racks->id]]]);

        $this->get($this->host.'/categories/modulni-i-stelazni-sistemi')->assertOk()->assertDontSee(self::TAB, false);
    }
}
