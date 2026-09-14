<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * The "open my storefront" address the admin panels link to.
 *
 * Four admin links built it as http://<slug>.<central domain>:8000 — the local
 * Docker proxy's port — so on production every one of them pointed at a port
 * that does not answer. The address has to follow the request it is rendered
 * in, and a shop with its own verified domain has to be sent there instead.
 */
class TenantStorefrontUrlTest extends TestCase
{
    use RefreshDatabase;

    private function shop(array $store = []): Tenant
    {
        $tenant = Tenant::create([
            'name' => 'Url Test',
            'slug' => 'url-test',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        Store::create(['tenant_id' => $tenant->id, 'theme' => 'wick', 'currency' => 'EUR'] + $store);

        return $tenant->fresh();
    }

    private function servedFrom(string $url): void
    {
        $this->app->instance('request', Request::create($url));
    }

    public function test_it_keeps_the_dev_port_in_development(): void
    {
        $central = config('ganvo.central_domain');
        $this->servedFrom("http://{$central}:8000/store");

        $this->assertSame("http://url-test.{$central}:8000/", $this->shop()->storefrontUrl());
    }

    public function test_it_drops_the_port_on_a_standard_https_request(): void
    {
        $central = config('ganvo.central_domain');
        $this->servedFrom("https://{$central}/store");

        $this->assertSame("https://url-test.{$central}/configurator", $this->shop()->storefrontUrl('/configurator'));
    }

    public function test_a_verified_custom_domain_wins(): void
    {
        $this->servedFrom('http://'.config('ganvo.central_domain').':8000/store');

        $verified = $this->shop(['custom_domain' => 'shop.example', 'custom_domain_verified_at' => now()]);
        $this->assertSame('https://shop.example/', $verified->storefrontUrl());
    }

    public function test_an_unverified_custom_domain_is_not_used(): void
    {
        $central = config('ganvo.central_domain');
        $this->servedFrom("https://{$central}/store");

        $this->assertSame("https://url-test.{$central}/", $this->shop(['custom_domain' => 'shop.example'])->storefrontUrl());
    }
}
