<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RackConfiguration;
use App\Models\RackPart;
use App\Models\Store;
use App\Models\Tenant;
use App\Services\Cart;
use App\Services\Rack\RackCalculator;
use App\Services\Rack\RackConfig;
use App\Services\Rack\RackPresenter;
use App\Services\Rack\RackPriceBook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A configured rack, from the cart to the order.
 *
 * The money path is the reason this exists. A rack reaches the basket as a
 * code and nothing else, so every figure it shows has to be derived on the
 * server — and once it is ordered, the parts it was made of must be frozen
 * where re-pricing a frame next month cannot reach them (S26, S38).
 */
class RackCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'name' => 'Sankevi Test',
            'slug' => 'sankevi-test',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $this->store = Store::create([
            'tenant_id' => $this->tenant->id,
            'theme' => 'sankevi',
            'currency' => 'EUR',
            'is_live' => true,
            'order_flow' => Store::FLOW_ENQUIRY,
            'rack_configurator' => ['enabled' => true, 'vat_rate_bp' => 2000],
        ]);

        $rows = [];
        foreach ([150, 180, 210, 240, 300] as $h) {
            foreach ([30, 40, 50, 60] as $d) {
                $rows[] = ['kind' => 'frame', 'sku' => "FRAME-$h-$d", 'height_cm' => $h, 'depth_cm' => $d, 'price_cents' => 2355];
            }
        }
        foreach ([77, 97, 117] as $w) {
            foreach ([29, 39, 49, 59] as $d) {
                $rows[] = ['kind' => 'shelf', 'sku' => "SHELF-$w-$d", 'width_cm' => $w, 'depth_cm' => $d, 'price_cents' => 2370];
            }
        }
        $rows[] = ['kind' => 'end_pin', 'sku' => 'END-PIN-10', 'price_cents' => 38];
        $rows[] = ['kind' => 'extension_pin', 'sku' => 'EXT-PIN-10', 'price_cents' => 36];
        $rows[] = ['kind' => 'cross_brace', 'sku' => 'CROSS-BRACE', 'price_cents' => 710];

        foreach ($rows as $row) {
            RackPart::create($row + ['tenant_id' => $this->tenant->id]);
        }

        app()->instance('current_tenant', $this->tenant);
    }

    private function saveRack(array $segments = [100, 100, 100, 100, 100]): RackConfiguration
    {
        $limits = $this->store->rackConfigurator();
        $config = RackConfig::of(210, 60, 4, $segments);
        $quote = (new RackCalculator)->quote($config, RackPriceBook::forTenant($this->tenant->id), $limits);

        return RackConfiguration::create([
            'tenant_id' => $this->tenant->id,
            'height_cm' => 210, 'depth_cm' => 60, 'levels' => 4,
            'segments' => $segments,
            'bom' => RackPresenter::labelledLines($quote),
            'subtotal_cents' => $quote->subtotalCents,
            'vat_cents' => $quote->vatCents,
            'total_cents' => $quote->totalCents,
            'vat_rate_bp' => $quote->vatRateBp,
            'currency' => 'EUR',
        ]);
    }

    public function test_a_rack_enters_the_cart_as_one_line_priced_from_the_database(): void
    {
        $saved = $this->saveRack();

        $cart = new Cart($this->tenant);
        $cart->addRack($saved->code);

        $items = $cart->items();
        $this->assertCount(1, $items, 'a rack is one cart line, not a parts list');

        $row = $items->first();
        $this->assertSame('rack:'.$saved->code, $row['line_id']);
        $this->assertSame(1, $row['quantity']);
        $this->assertNotNull($row['rack']);
        $this->assertNull($row['product']->id, 'the carrier product is not persisted');
        // 654.20 ex-VAT + 20% = 785.04 incl, which is what the basket charges.
        $this->assertSame(78504, $row['unit_price_cents']);
        $this->assertSame(78504, $cart->subtotalCents());
        $this->assertSame(1, $cart->itemCount());
    }

    public function test_the_cart_follows_a_price_correction(): void
    {
        $saved = $this->saveRack();
        $cart = new Cart($this->tenant);
        $cart->addRack($saved->code);
        $this->assertSame(78504, $cart->subtotalCents());

        // The merchant puts the frame up; the basket must not keep quoting
        // yesterday's figure just because the configuration was saved then.
        RackPart::where('tenant_id', $this->tenant->id)
            ->where('sku', 'FRAME-210-60')
            ->update(['price_cents' => 2520]);

        $fresh = new Cart($this->tenant);
        $this->assertSame(79692, $fresh->subtotalCents(), '6 frames × €1.65 more, plus VAT');
    }

    public function test_a_rack_whose_part_is_retired_leaves_the_basket(): void
    {
        $saved = $this->saveRack();
        $cart = new Cart($this->tenant);
        $cart->addRack($saved->code);
        $this->assertSame(1, $cart->itemCount());

        RackPart::where('tenant_id', $this->tenant->id)
            ->where('sku', 'SHELF-97-59')
            ->update(['is_active' => false]);

        $fresh = new Cart($this->tenant);
        $this->assertTrue($fresh->isEmpty(), 'an unbuildable rack must not sit in the basket');
        // and it must be gone from the session, not merely hidden from the list
        $this->assertSame(0, (new Cart($this->tenant))->itemCount());
    }

    public function test_two_of_the_same_rack_are_one_line_of_two(): void
    {
        $saved = $this->saveRack();
        $cart = new Cart($this->tenant);
        $cart->addRack($saved->code);
        $cart->addRack($saved->code);

        $this->assertCount(1, $cart->items());
        $this->assertSame(2, $cart->itemCount());
        $this->assertSame(157008, $cart->subtotalCents());
    }

    public function test_two_different_racks_are_two_lines(): void
    {
        $a = $this->saveRack([100]);
        $b = $this->saveRack([120, 120]);

        $cart = new Cart($this->tenant);
        $cart->addRack($a->code);
        $cart->addRack($b->code);

        $this->assertCount(2, $cart->items());
    }

    public function test_the_order_records_the_whole_bill_of_materials(): void
    {
        $saved = $this->saveRack();

        $order = Order::create([
            'tenant_id' => $this->tenant->id,
            'order_number' => 'TEST-1',
            'customer_email' => 'x@example.test',
            'customer_name' => 'Test',
            'total_cents' => 78504,
            'currency' => 'EUR',
            'status' => 'pending',
            'order_flow' => Store::FLOW_ENQUIRY,
        ]);

        $cart = new Cart($this->tenant);
        $cart->addRack($saved->code);
        $row = $cart->items()->first();

        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => null,
            'rack_configuration_id' => $row['rack']->id,
            'product_name' => $row['product']->name,
            'unit_price_cents' => $row['unit_price_cents'],
            'quantity' => 1,
            'subtotal_cents' => $row['subtotal_cents'],
        ]);

        $sort = 0;
        foreach (RackPresenter::labelledLines($row['rack_bom']) as $line) {
            $item->rackItems()->create([
                'kind' => $line['kind'],
                'label' => $line['label'],
                'sku' => $line['sku'],
                'height_cm' => $line['height_cm'],
                'depth_cm' => $line['depth_cm'],
                'width_cm' => $line['width_cm'],
                'quantity' => $line['quantity'],
                'unit_price_cents' => $line['unit_price_cents'],
                'subtotal_cents' => $line['subtotal_cents'],
                'sort_order' => $sort++,
            ]);
        }

        $item->refresh();
        $this->assertTrue($item->isRack());

        $bom = $item->rackItems->keyBy('kind');
        $this->assertSame(6, $bom['frame']->quantity);
        $this->assertSame(20, $bom['shelf']->quantity);
        $this->assertSame(16, $bom['end_pin']->quantity);
        $this->assertSame(32, $bom['extension_pin']->quantity);
        $this->assertSame(3, $bom['cross_brace']->quantity);
        $this->assertSame('FRAME-210-60', $bom['frame']->sku);
        $this->assertSame(97, $bom['shelf']->width_cm);

        // ONE money line on the order, so the confirmation and the mail still
        // add up: the parts hang off it and must not be summed again.
        $this->assertCount(1, $order->fresh()->items);
        $this->assertSame(78504, (int) $order->fresh()->items->sum('subtotal_cents'));
    }

    public function test_the_frozen_bom_survives_a_later_price_rise(): void
    {
        $saved = $this->saveRack();
        $cart = new Cart($this->tenant);
        $cart->addRack($saved->code);
        $row = $cart->items()->first();

        $order = Order::create([
            'tenant_id' => $this->tenant->id, 'order_number' => 'TEST-2',
            'customer_email' => 'x@example.test', 'customer_name' => 'T',
            'total_cents' => $row['subtotal_cents'], 'currency' => 'EUR',
            'status' => 'pending', 'order_flow' => Store::FLOW_ENQUIRY,
        ]);
        $item = OrderItem::create([
            'order_id' => $order->id, 'rack_configuration_id' => $row['rack']->id,
            'product_name' => $row['product']->name, 'unit_price_cents' => $row['unit_price_cents'],
            'quantity' => 1, 'subtotal_cents' => $row['subtotal_cents'],
        ]);
        foreach (RackPresenter::labelledLines($row['rack_bom']) as $line) {
            $item->rackItems()->create([
                'kind' => $line['kind'], 'label' => $line['label'], 'sku' => $line['sku'],
                'height_cm' => $line['height_cm'], 'depth_cm' => $line['depth_cm'], 'width_cm' => $line['width_cm'],
                'quantity' => $line['quantity'], 'unit_price_cents' => $line['unit_price_cents'],
                'subtotal_cents' => $line['subtotal_cents'],
            ]);
        }

        RackPart::where('tenant_id', $this->tenant->id)->update(['price_cents' => 9999]);
        // and someone reopens the shared link and rebuilds it differently
        $saved->update(['segments' => [80], 'total_cents' => 1]);

        $frozen = $item->fresh()->rackItems->keyBy('kind');
        $this->assertSame(2355, $frozen['frame']->unit_price_cents, 'the order keeps the price it was placed at');
        $this->assertSame(6, $frozen['frame']->quantity, 'and the parts it was placed for');
        $this->assertSame(78504, (int) $item->fresh()->subtotal_cents);
    }

    /**
     * The real checkout, through the real controller.
     *
     * Everything above builds the order by hand; this one posts the form a
     * customer posts and asserts what comes out the other side — one priced
     * line, its parts frozen beneath it, and no payment taken.
     */
    /**
     * The quantity and remove controls on the cart page.
     *
     * Cart line ids are whitelisted by regex in routes/web.php — the pattern was
     * "{productId}:{variantId}" and a rack line would have 404'd on both of
     * these, leaving a basket nobody could change.
     */
    public function test_a_rack_line_can_be_changed_and_removed_from_the_cart(): void
    {
        $saved = $this->saveRack([100]);
        $host = 'http://sankevi-test.'.config('ganvo.central_domain');
        $line = 'rack:'.$saved->code;
        $session = [
            '_token' => 'rack-test-token',
            'cart' => ['tenant_'.$this->tenant->id => [$line => ['qty' => 1, 'measure' => null]]],
        ];

        $up = $this->withSession($session)
            ->patch($host.'/cart/'.$line, ['_token' => 'rack-test-token', 'quantity' => 3]);
        $this->assertNotSame(404, $up->status(), 'the route must accept a rack line id');
        $this->assertSame(3, (new Cart($this->tenant))->itemCount());

        $this->withSession($session)
            ->delete($host.'/cart/'.$line, ['_token' => 'rack-test-token']);
        $this->assertTrue((new Cart($this->tenant))->isEmpty());
    }

    /**
     * Building the same rack twice is one rack, twice.
     *
     * Every add-to-cart used to create its own configuration row with its own
     * code, and the basket keys a line on the code — so pressing the button
     * twice gave the customer two identical lines of one instead of one line
     * of two, and left a duplicate row behind each time.
     */
    public function test_the_same_configuration_is_reused_rather_than_duplicated(): void
    {
        $host = 'http://sankevi-test.'.config('ganvo.central_domain');
        $body = [
            '_token' => 'rack-test-token',
            'config' => ['height' => 210, 'depth' => 60, 'levels' => 4, 'segments' => [100, 100]],
        ];

        $first = $this->withSession(['_token' => 'rack-test-token'])->postJson($host.'/configurator/cart', $body);
        $first->assertOk();
        $code = $first->json('code');

        $second = $this->withSession(['_token' => 'rack-test-token'])->postJson($host.'/configurator/cart', $body);
        $second->assertOk();

        $this->assertSame($code, $second->json('code'), 'the same rack must come back as the same configuration');
        $this->assertSame(1, RackConfiguration::where('tenant_id', $this->tenant->id)->count());
    }

    /** A different rack is a different configuration, not a reused one. */
    public function test_a_different_configuration_gets_its_own_code(): void
    {
        $host = 'http://sankevi-test.'.config('ganvo.central_domain');
        $post = fn (array $cfg) => $this->withSession(['_token' => 'rack-test-token'])
            ->postJson($host.'/configurator/cart', ['_token' => 'rack-test-token', 'config' => $cfg]);

        $a = $post(['height' => 210, 'depth' => 60, 'levels' => 4, 'segments' => [100, 100]]);
        // same parts, different ORDER — a 120 at the near end is not the same rack
        $b = $post(['height' => 210, 'depth' => 60, 'levels' => 4, 'segments' => [120, 80]]);
        $c = $post(['height' => 210, 'depth' => 60, 'levels' => 4, 'segments' => [80, 120]]);

        $this->assertNotSame($a->json('code'), $b->json('code'));
        $this->assertNotSame($b->json('code'), $c->json('code'), 'order matters: 120+80 is not 80+120');
        $this->assertSame(3, RackConfiguration::where('tenant_id', $this->tenant->id)->count());
    }

    /** A share code is not a way into another yard's configurations. */
    public function test_a_configuration_from_another_tenant_cannot_be_added(): void
    {
        $other = Tenant::create(['name' => 'Other', 'slug' => 'other-yard', 'status' => Tenant::STATUS_ACTIVE]);
        $foreign = RackConfiguration::create([
            'tenant_id' => $other->id,
            'height_cm' => 210, 'depth_cm' => 60, 'levels' => 4,
            'segments' => [100], 'subtotal_cents' => 1, 'vat_cents' => 0,
            'total_cents' => 1, 'vat_rate_bp' => 0, 'currency' => 'EUR',
        ]);

        $cart = new Cart($this->tenant);
        $cart->addRack($foreign->code);

        $this->assertTrue($cart->isEmpty(), 'another tenant\'s rack must not resolve');
    }

    public function test_the_checkout_controller_writes_one_line_and_freezes_the_parts(): void
    {
        $this->store->update([
            'shipping_methods' => [[
                'id' => 'agreed',
                'label' => 'Доставка по договаряне',
                'description' => '',
                'price_cents' => 0,
                'free_threshold_cents' => null,
            ]],
            'checkout_mode' => Store::CHECKOUT_BOTH ?? 'both',
        ]);

        $saved = $this->saveRack();

        /*
         | Nested, not a literal dotted key: Session::put uses Arr::set, so the
         | cart really lives at ['cart']['tenant_N'].
         |
         | The _token pair is not ceremony — this app rewrites a CSRF failure
         | into a silent redirect back (bootstrap/app.php), so a POST without
         | one looks exactly like a validation failure with no errors. Posting
         | a real token keeps the whole middleware stack in the test.
         */
        $response = $this->withSession([
            '_token' => 'rack-test-token',
            'cart' => ['tenant_'.$this->tenant->id => ['rack:'.$saved->code => ['qty' => 2, 'measure' => null]]],
        ])
            ->post('http://sankevi-test.'.config('ganvo.central_domain').'/checkout', [
                '_token' => 'rack-test-token',
                'customer_email' => 'buyer@example.test',
                'customer_name' => 'Иван Тестов',
                'customer_phone' => '0888123456',
                'address_line' => 'ул. Тест 1',
                'city' => 'Велинград',
                'postal_code' => '4600',
                'country' => 'BG',
                'shipping_method' => 'agreed',
            ]);

        $response->assertSessionHasNoErrors();
        $order = Order::where('tenant_id', $this->tenant->id)->latest('id')->first();
        $this->assertNotNull($order, 'the checkout must have created an order; got '.$response->status());

        // An enquiry takes no money and must never look as though it did.
        $this->assertSame('enquiry', $order->order_flow);
        $this->assertNull($order->paid_at);
        $this->assertSame('pending', $order->status);

        $items = $order->items;
        $this->assertCount(1, $items, 'a rack is one line on the order, not a parts list');

        $item = $items->first();
        $this->assertNull($item->product_id);
        $this->assertSame($saved->id, $item->rack_configuration_id);
        $this->assertSame(2, $item->quantity);
        $this->assertSame(78504, $item->unit_price_cents);
        $this->assertSame(157008, $item->subtotal_cents);
        $this->assertSame(157008, (int) $order->items->sum('subtotal_cents'));

        // Two racks means twice the parts — six uprights each, twelve in all.
        $bom = $item->rackItems->keyBy('kind');
        $this->assertSame(12, $bom['frame']->quantity);
        $this->assertSame(40, $bom['shelf']->quantity);
        $this->assertSame(32, $bom['end_pin']->quantity);
        $this->assertSame(64, $bom['extension_pin']->quantity);
        $this->assertSame(6, $bom['cross_brace']->quantity);
        $this->assertSame(28260, $bom['frame']->subtotal_cents, '12 × €23.55');

        // And the basket is empty afterwards.
        $this->assertTrue((new Cart($this->tenant))->isEmpty());
    }

    public function test_deleting_a_configuration_does_not_take_the_order_with_it(): void
    {
        $saved = $this->saveRack();
        $order = Order::create([
            'tenant_id' => $this->tenant->id, 'order_number' => 'TEST-3',
            'customer_email' => 'x@example.test', 'customer_name' => 'T',
            'total_cents' => 78504, 'currency' => 'EUR', 'status' => 'pending',
        ]);
        $item = OrderItem::create([
            'order_id' => $order->id, 'rack_configuration_id' => $saved->id,
            'product_name' => 'Rack', 'unit_price_cents' => 78504,
            'quantity' => 1, 'subtotal_cents' => 78504,
        ]);
        $item->rackItems()->create([
            'kind' => 'frame', 'label' => 'Frame 210×60 cm', 'sku' => 'FRAME-210-60',
            'quantity' => 6, 'unit_price_cents' => 2355, 'subtotal_cents' => 14130,
        ]);

        $saved->delete();

        $item->refresh();
        $this->assertNull($item->rack_configuration_id);
        $this->assertCount(1, $item->rackItems, 'the frozen parts list outlives the configuration');
        $this->assertSame(78504, (int) $item->subtotal_cents);
    }
}
