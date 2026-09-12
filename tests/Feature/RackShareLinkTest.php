<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderRackItem;
use App\Models\RackConfiguration;
use App\Models\RackPart;
use App\Models\Store;
use App\Models\Tenant;
use App\Services\Rack\RackConfig;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A share link names one rack, and only its author may add to the record.
 *
 * The configurator deduplicates saves so that pressing „Добави към заявката"
 * twice makes one cart line of two rather than two lines of one. That dedupe
 * was written to match across the WHOLE TENANT and then to update() the row it
 * found — which quietly turned every saved configuration into a shared, public,
 * writable record. Anyone who built the same shape rewrote a stranger's row and
 * was handed their code.
 *
 * These tests pin the three properties that fix has to hold at once: a stranger
 * can never write to your row; a price change never edits what you were already
 * shown; and one browser saving one rack twice still gets one configuration.
 */
class RackShareLinkTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Store $store;

    private string $host;

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
        $this->host = 'http://sankevi-test.'.config('ganvo.central_domain');
    }

    /**
     * One browser, held across requests.
     *
     * A test request mints a fresh session id every time, which would make two
     * saves by the same customer look like two different people — and the whole
     * point of the fix is that those two cases now behave differently. So this
     * does what a browser does: send the session cookie back, and keep whatever
     * the response sets. Exempting that one cookie from encryption is what lets
     * the test read and replay it.
     *
     * @var array<string,string>
     */
    /** @var array<string,array{cookie:string,token:string}> */
    private array $browsers = [];

    /**
     * Open the configurator as a given person, so they have a session and a
     * CSRF token — exactly the two things a real browser picks up on the way in.
     */
    private function open(string $who): void
    {
        $name = config('session.cookie');
        EncryptCookies::except($name);

        $response = $this->get($this->host.'/configurator');
        $response->assertOk();

        $cookie = collect($response->headers->getCookies())
            ->first(fn ($c) => $c->getName() === $name && $c->getValue() !== '');

        $this->assertNotNull($cookie, 'the storefront must set a session cookie');

        $this->browsers[$who] = [
            'cookie' => $cookie->getValue(),
            'token' => $this->app['session']->token(),
        ];
    }

    private function asBrowser(string $who, string $path, array $body)
    {
        if (! isset($this->browsers[$who])) {
            $this->open($who);
        }

        $name = config('session.cookie');

        $response = $this
            ->withUnencryptedCookie($name, $this->browsers[$who]['cookie'])
            ->withHeader('X-CSRF-TOKEN', $this->browsers[$who]['token'])
            ->postJson($this->host.$path, $body);

        // The session may have rotated its token; keep up, as a browser does.
        $this->browsers[$who]['token'] = $this->app['session']->token();

        return $response;
    }

    private function save(string $who, array $config)
    {
        return $this->asBrowser($who, '/configurator/save', ['config' => $config]);
    }

    private const RACK = ['height' => 210, 'depth' => 60, 'levels' => 4, 'segments' => [100, 100, 80]];

    /* ------------------------------------------------------------------ */

    /** The reported bug: holding the link was enough to rewrite the record. */
    public function test_a_stranger_saving_the_same_rack_cannot_touch_my_configuration(): void
    {
        $mine = $this->save('customer-a', self::RACK);
        $mine->assertOk();
        $code = $mine->json('code');

        $before = RackConfiguration::where('code', $code)->firstOrFail()->getAttributes();

        // Somebody else builds the identical rack and saves it.
        $theirs = $this->save('customer-b', self::RACK);
        $theirs->assertOk();

        $after = RackConfiguration::where('code', $code)->firstOrFail()->getAttributes();

        $this->assertSame($before, $after, 'a stranger must not be able to write to my saved configuration');
        $this->assertNotSame($code, $theirs->json('code'), 'a stranger must not be handed my code');
        $this->assertSame(2, RackConfiguration::where('tenant_id', $this->tenant->id)->count());
    }

    /** What I was shown when I saved is what my link must keep showing. */
    public function test_a_price_change_writes_a_new_row_rather_than_editing_the_old_one(): void
    {
        $first = $this->save('customer-a', self::RACK);
        $first->assertOk();
        $code = $first->json('code');
        $before = RackConfiguration::where('code', $code)->firstOrFail()->getAttributes();

        RackPart::where('tenant_id', $this->tenant->id)
            ->where('kind', 'frame')
            ->update(['price_cents' => 9999]);

        $second = $this->save('customer-a', self::RACK);
        $second->assertOk();

        $after = RackConfiguration::where('code', $code)->firstOrFail()->getAttributes();

        $this->assertSame($before, $after, 'my saved quote must not be re-priced under me');
        $this->assertNotSame($code, $second->json('code'), 'a differently priced rack is a different record');
        $this->assertSame(2, RackConfiguration::where('tenant_id', $this->tenant->id)->count());
    }

    /**
     * And the behaviour the dedupe existed for in the first place: one person,
     * one rack, two presses — one configuration, so the basket shows one line
     * of two rather than two lines of one.
     *
     * Asked of reusableFor() rather than of two HTTP requests, because the test
     * harness mints a fresh session id on every request: over HTTP there is no
     * such thing here as the same browser twice. A real browser keeps its
     * session cookie, which is what makes this the same person.
     */
    public function test_the_same_person_saving_the_same_rack_reuses_their_configuration(): void
    {
        $this->save('customer-a', self::RACK)->assertOk();
        $row = RackConfiguration::where('tenant_id', $this->tenant->id)->firstOrFail();

        $config = RackConfig::of(210, 60, 4, [100, 100, 80]);
        $snapshot = [
            'subtotal_cents' => $row->subtotal_cents,
            'vat_cents' => $row->vat_cents,
            'total_cents' => $row->total_cents,
            'vat_rate_bp' => $row->vat_rate_bp,
            'currency' => $row->currency,
        ];

        $this->assertTrue(
            $row->is(RackConfiguration::reusableFor($this->tenant->id, $row->owner_token, $config, $snapshot)),
            'the same person building the same rack at the same price must get their own row back'
        );

        $this->assertNull(
            RackConfiguration::reusableFor($this->tenant->id, hash('sha256', 'somebody-else'), $config, $snapshot),
            'a stranger must not be handed this row'
        );

        $this->assertNull(
            RackConfiguration::reusableFor($this->tenant->id, $row->owner_token, $config,
                ['subtotal_cents' => 1, 'vat_cents' => 1, 'total_cents' => 2, 'vat_rate_bp' => 2000, 'currency' => 'EUR']),
            'a rack that now costs something else is a different record'
        );

        $this->assertNull(
            RackConfiguration::reusableFor($this->tenant->id, null, $config, $snapshot),
            'no session, no reuse'
        );
    }

    /** No row may ever be written twice. */
    public function test_a_saved_configuration_is_never_updated(): void
    {
        $this->save('customer-a', self::RACK)->assertOk();
        $row = RackConfiguration::where('tenant_id', $this->tenant->id)->firstOrFail();

        $this->save('customer-a', self::RACK)->assertOk();
        $this->save('customer-b', self::RACK)->assertOk();
        $this->save('customer-c', self::RACK)->assertOk();

        $this->assertEquals(
            $row->updated_at->toDateTimeString(),
            $row->fresh()->updated_at->toDateTimeString(),
            'the row was rewritten by a later save'
        );
    }

    /**
     * A link to a rack the yard no longer builds must not quietly show a
     * different rack under the same code.
     */
    public function test_a_retired_size_does_not_serve_a_different_rack_under_the_saved_code(): void
    {
        $saved = $this->save('customer-a', ['height' => 300, 'depth' => 60, 'levels' => 4, 'segments' => [100]]);
        $saved->assertOk();
        $code = $saved->json('code');

        // The merchant stops selling the 300 cm frame.
        RackPart::where('tenant_id', $this->tenant->id)
            ->where('kind', 'frame')
            ->where('height_cm', 300)
            ->update(['is_active' => false]);

        $page = $this->get($this->host.'/configurator/'.$code);
        $page->assertOk();

        $boot = $this->boot($page->getContent());

        $this->assertTrue($boot['stale'], 'the page must know the link could not be honoured');
        $this->assertNull($boot['savedCode'], 'a code must not be shown against a rack it does not describe');
        $this->assertNotSame(300, $boot['config']['height'] ?? null);
    }

    /** Pull the JSON the page boots its JavaScript from. */
    private function boot(string $html): array
    {
        $this->assertSame(1, preg_match("/data-boot='([^']+)'/", $html, $m), 'no boot payload on the page');

        return json_decode(html_entity_decode($m[1], ENT_QUOTES), true);
    }

    /**
     * Deleting a configuration must not blank the cutting list on an order
     * already built from it. The frozen rows are the record; the link to the
     * configuration is nullOnDelete and is not.
     */
    public function test_deleting_a_configuration_leaves_the_frozen_parts_list_readable(): void
    {
        $this->save('customer-a', self::RACK)->assertOk();
        $config = RackConfiguration::where('tenant_id', $this->tenant->id)->firstOrFail();

        $order = Order::create([
            'tenant_id' => $this->tenant->id,
            'order_number' => 'T-1',
            'status' => 'pending',
            'currency' => 'EUR',
            'total_cents' => $config->total_cents,
            'customer_email' => 'buyer@example.test',
        ]);
        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => null,
            'rack_configuration_id' => $config->id,
            'product_name' => 'Стелаж 210×60',
            'quantity' => 1,
            'unit_price_cents' => $config->total_cents,
            'subtotal_cents' => $config->total_cents,
        ]);
        OrderRackItem::create([
            'order_item_id' => $item->id,
            'kind' => 'frame',
            'label' => 'Рамка 210×60',
            'sku' => 'FRAME-210-60',
            'quantity' => 4,
            'unit_price_cents' => 2355,
            'subtotal_cents' => 9420,
            'sort_order' => 0,
        ]);

        $this->assertTrue($item->fresh()->isRack());

        $config->delete();

        $item = $item->fresh();
        $this->assertNull($item->rack_configuration_id, 'the link is expected to go');
        $this->assertTrue($item->isRack(), 'the frozen parts list must still be reachable');
        $this->assertCount(1, $item->rackItems);
    }
}
