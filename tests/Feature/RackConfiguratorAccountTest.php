<?php

namespace Tests\Feature;

use App\Filament\StoreAdmin\Resources\RackConfigurations\Pages\ListRackConfigurations;
use App\Models\Customer;
use App\Models\RackConfiguration;
use App\Models\RackPart;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Keeping a rack takes an account.
 *
 * Anybody may draw and price. Saving, sharing and adding to a request are
 * refused to a guest — on the server, because the page's sign-up window is a
 * courtesy and a hand-written fetch() skips it — and every rack that IS stored
 * carries the customer who made it, which is what lets the yard's list say
 * whose it is and the customer's account page list their own.
 */
class RackConfiguratorAccountTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private string $host;

    private const RACK = ['height' => 210, 'depth' => 60, 'levels' => 4, 'segments' => [100, 100]];

    private const TOKEN = 'rack-account-test-token';

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = $this->makeTenant('sankevi-test');
        app()->instance('current_tenant', $this->tenant);
        $this->host = 'http://sankevi-test.'.config('ganvo.central_domain');
    }

    private function makeTenant(string $slug): Tenant
    {
        $tenant = Tenant::create(['name' => $slug, 'slug' => $slug, 'status' => Tenant::STATUS_ACTIVE]);

        Store::create([
            'tenant_id' => $tenant->id,
            'theme' => 'sankevi',
            'currency' => 'EUR',
            'is_live' => true,
            'order_flow' => Store::FLOW_ENQUIRY,
            'checkout_mode' => Store::CHECKOUT_BOTH,
            'allow_registration' => true,
            // As on the real shop: the phone is asked for, and required.
            'signup_fields' => ['phone' => ['enabled' => true, 'required' => true]],
            'rack_configurator' => ['enabled' => true, 'vat_rate_bp' => 2000],
        ]);

        $rows = [
            ['kind' => 'frame', 'sku' => 'FRAME-210-60', 'height_cm' => 210, 'depth_cm' => 60, 'price_cents' => 2355],
            ['kind' => 'end_pin', 'sku' => 'END-PIN-10', 'price_cents' => 38],
            ['kind' => 'extension_pin', 'sku' => 'EXT-PIN-10', 'price_cents' => 36],
            ['kind' => 'cross_brace', 'sku' => 'CROSS-BRACE', 'price_cents' => 710],
        ];
        foreach ([77, 97, 117] as $w) {
            $rows[] = ['kind' => 'shelf', 'sku' => "SHELF-$w-59", 'width_cm' => $w, 'depth_cm' => 59, 'price_cents' => 2370];
        }
        foreach ($rows as $row) {
            RackPart::create($row + ['tenant_id' => $tenant->id]);
        }

        return $tenant;
    }

    /**
     * A JSON post the way the page makes one. The storefront checks CSRF even
     * under test, so the session carries a token and the header repeats it.
     */
    private function send(string $path, array $body = [])
    {
        return $this->withSession(['_token' => self::TOKEN])
            ->withHeader('X-CSRF-TOKEN', self::TOKEN)
            ->postJson($this->host.$path, $body);
    }

    private function customer(Tenant $tenant, string $email = 'ivan@example.test'): Customer
    {
        return Customer::create([
            'tenant_id' => $tenant->id,
            'name' => 'Иван Петров',
            'email' => $email,
            'phone' => '0888 123 456',
            'password' => 'correct-horse',
        ]);
    }

    private function rack(?Customer $customer, string $code, ?string $ownerToken = null): RackConfiguration
    {
        return RackConfiguration::create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $customer?->id,
            'owner_token' => $ownerToken,
            'code' => $code,
            'height_cm' => 210, 'depth_cm' => 60, 'levels' => 4,
            'segments' => [100, 100],
            'bom' => [],
            'subtotal_cents' => 10000, 'vat_cents' => 2000, 'total_cents' => 12000,
            'vat_rate_bp' => 2000, 'currency' => 'EUR',
        ]);
    }

    /* ---- the refusal ------------------------------------------------ */

    public function test_a_guest_can_still_price_a_rack(): void
    {
        $this->send('/configurator/quote', ['config' => self::RACK])
            ->assertOk()
            ->assertJson(['ok' => true]);
    }

    public function test_a_guest_cannot_save_a_rack(): void
    {
        $this->send('/configurator/save', ['config' => self::RACK])
            ->assertStatus(401)
            ->assertJson(['ok' => false, 'auth_required' => true]);

        $this->assertSame(0, RackConfiguration::count());
    }

    public function test_a_guest_cannot_add_a_rack_to_the_request(): void
    {
        $this->send('/configurator/cart', ['config' => self::RACK])
            ->assertStatus(401)
            ->assertJson(['auth_required' => true]);

        $this->assertSame(0, RackConfiguration::count(), 'a refused add must not leave a rack behind');
    }

    /** One shop's customer is nobody at another shop. */
    public function test_a_customer_of_another_shop_counts_as_a_guest(): void
    {
        $stranger = $this->customer($this->makeTenant('other-yard'));

        $this->actingAs($stranger, 'customer')
            ->send('/configurator/save', ['config' => self::RACK])
            ->assertStatus(401);

        $this->assertSame(0, RackConfiguration::count());
    }

    public function test_the_page_tells_its_script_whether_the_visitor_is_signed_in(): void
    {
        $this->assertFalse($this->boot($this->get($this->host.'/configurator'))['signedIn']);

        $this->actingAs($this->customer($this->tenant), 'customer');
        $this->assertTrue($this->boot($this->get($this->host.'/configurator'))['signedIn']);
    }

    /* ---- the owner -------------------------------------------------- */

    public function test_a_saved_rack_belongs_to_the_customer_who_saved_it(): void
    {
        $ivan = $this->customer($this->tenant);

        $code = $this->actingAs($ivan, 'customer')
            ->send('/configurator/save', ['config' => self::RACK])
            ->assertOk()
            ->json('code');

        $this->assertSame($ivan->id, RackConfiguration::where('code', $code)->value('customer_id'));

        $cartCode = $this->actingAs($ivan, 'customer')
            ->send('/configurator/cart', ['config' => ['segments' => [100]] + self::RACK])
            ->assertOk()
            ->json('code');

        $this->assertSame($ivan->id, RackConfiguration::where('code', $cartCode)->value('customer_id'));
    }

    /* ---- the quick sign-up ------------------------------------------ */

    public function test_the_quick_sign_up_creates_the_account_signs_in_and_hands_back_a_fresh_token(): void
    {
        $response = $this->send('/account/register', [
            'name' => 'Мария Иванова',
            'email' => 'Maria@Example.test',
            'phone' => '0877 000 111',
            // One password box in the window — no confirmation field.
            'password' => 'long-enough-1',
        ]);

        $response->assertOk()->assertJson(['ok' => true, 'name' => 'Мария Иванова']);
        $this->assertNotEmpty($response->json('token'), 'the page needs the rotated CSRF token to make its save');
        $this->assertNotSame(self::TOKEN, $response->json('token'), 'signing in must rotate the token');

        $maria = Customer::where('tenant_id', $this->tenant->id)->where('email', 'maria@example.test')->firstOrFail();
        $this->assertSame('0877 000 111', $maria->phone);
        $this->assertAuthenticatedAs($maria, 'customer');

        // …and the save it was opened for now goes through.
        $this->send('/configurator/save', ['config' => self::RACK])->assertOk();
        $this->assertSame($maria->id, RackConfiguration::value('customer_id'));
    }

    public function test_the_quick_sign_up_still_asks_for_what_the_shop_requires(): void
    {
        $this->send('/account/register', [
            'name' => 'Без Телефон',
            'email' => 'nophone@example.test',
            'password' => 'long-enough-1',
        ])->assertStatus(422)->assertJsonValidationErrors(['phone']);

        $this->assertGuest('customer');
    }

    public function test_an_existing_email_is_pointed_at_signing_in(): void
    {
        $this->customer($this->tenant, 'taken@example.test');

        $this->send('/account/register', [
            'name' => 'Втори', 'email' => 'taken@example.test', 'phone' => '1', 'password' => 'long-enough-1',
        ])->assertStatus(422)->assertJsonPath('errors.email.0', __('site.auth.email_taken'));
    }

    /** The full-page form keeps its second password box. */
    public function test_the_register_page_still_wants_the_password_confirmed(): void
    {
        $this->withSession(['_token' => self::TOKEN])
            ->post($this->host.'/account/register', [
                '_token' => self::TOKEN,
                'name' => 'Страница', 'email' => 'page@example.test', 'phone' => '1', 'password' => 'long-enough-1',
            ])->assertSessionHasErrors(['password']);

        $this->assertGuest('customer');
    }

    public function test_the_window_can_sign_in_an_existing_customer(): void
    {
        $ivan = $this->customer($this->tenant);

        $this->send('/account/login', ['email' => 'ivan@example.test', 'password' => 'correct-horse'])
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertAuthenticatedAs($ivan, 'customer');
    }

    public function test_a_wrong_password_is_a_422_the_window_can_show(): void
    {
        $this->customer($this->tenant);

        $this->send('/account/login', ['email' => 'ivan@example.test', 'password' => 'nope'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * A rack this browser saved as a guest, before accounts were required, is
     * handed to the account it signs into — and a rack that is somebody's
     * already stays theirs.
     *
     * "This browser, an earlier request" cannot be staged over HTTP here: under
     * the array session driver every test request gets a fresh session id,
     * cookie or no cookie. So the guest racks are written from INSIDE the
     * sign-up request, the moment the customer row is created — which is
     * after the session exists and before the guard logs in and rotates its
     * id. That is exactly the id the claim has to hash, so a claim that hashed
     * the session after logging in fails this test.
     */
    public function test_signing_in_claims_the_racks_this_browser_saved_as_a_guest(): void
    {
        $owner = $this->customer($this->tenant, 'owner@example.test');
        $someoneElses = $this->rack(null, 'OTHER001', hash('sha256', 'another-browser'));

        $racks = [];
        Customer::created(function () use (&$racks, $owner) {
            $thisBrowser = hash('sha256', session()->getId());
            $racks['mine'] = $this->rack(null, 'GUEST001', $thisBrowser);
            $racks['owned'] = $this->rack($owner, 'OWNED001', $thisBrowser);
        });

        $this->send('/account/register', [
            'name' => 'Нов', 'email' => 'new@example.test', 'phone' => '1', 'password' => 'long-enough-1',
        ])->assertOk();

        $new = Customer::where('email', 'new@example.test')->firstOrFail();

        $this->assertArrayHasKey('mine', $racks, 'the guest racks were never staged');
        $this->assertSame($new->id, $racks['mine']->fresh()->customer_id, 'my guest rack should follow me into my account');
        $this->assertNull($someoneElses->fresh()->customer_id, 'another browser\'s rack is not mine');
        $this->assertSame($owner->id, $racks['owned']->fresh()->customer_id, 'a rack with an owner is never reassigned');
    }

    /* ---- the two screens that read the owner ------------------------ */

    public function test_the_account_page_lists_my_racks_and_nobody_elses(): void
    {
        $ivan = $this->customer($this->tenant);
        $petar = $this->customer($this->tenant, 'petar@example.test');
        $this->rack($ivan, 'IVAN0001');
        $this->rack($petar, 'PETAR001');

        $this->actingAs($ivan, 'customer')
            ->get($this->host.'/account')
            ->assertOk()
            ->assertSee(__('site.account.racks'))
            ->assertSee('IVAN0001')
            ->assertSee('/configurator/IVAN0001', false)
            ->assertDontSee('PETAR001');
    }

    public function test_the_yard_sees_whose_rack_it_is_and_can_search_by_email(): void
    {
        $ivan = $this->customer($this->tenant);
        $ivansRack = $this->rack($ivan, 'IVAN0001');
        $guestRack = $this->rack(null, 'GUEST001');

        $owner = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Owner',
            'email' => 'owner@sankevi-test.test',
            'password' => bcrypt(str()->random(32)),
        ]);
        Filament::setCurrentPanel('store');
        $this->actingAs($owner);

        Livewire::test(ListRackConfigurations::class)
            ->assertTableColumnStateSet('customer.name', 'Иван Петров', $ivansRack)
            ->searchTable('ivan@example')
            ->assertCanSeeTableRecords([$ivansRack])
            ->assertCanNotSeeTableRecords([$guestRack]);
    }

    private function boot($response): array
    {
        $response->assertOk();
        $this->assertSame(1, preg_match("/data-boot='([^']+)'/", $response->getContent(), $m), 'no boot payload on the page');

        return json_decode(html_entity_decode($m[1], ENT_QUOTES), true);
    }
}
