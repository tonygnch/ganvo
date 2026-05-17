<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::firstOrCreate(['name' => 'super_admin']);
        $storeAdminRole = Role::firstOrCreate(['name' => 'store_admin']);

        $superAdmin = User::firstOrCreate(
            ['email' => 'super@ganvo.test'],
            [
                'name' => 'Ganvo Super Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->assignRole($superAdminRole);

        $tenant = Tenant::firstOrCreate(
            ['slug' => 'acme'],
            [
                'name' => 'Acme Co',
                'business_type' => 'retail',
                'contact_email' => 'owner@acme.test',
                'subscription_plan' => 'starter',
                'status' => 'active',
                'onboarded_at' => now(),
            ]
        );

        Store::firstOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'theme' => 'default',
                'primary_color' => '#10B981',
                'is_live' => true,
            ]
        );

        $storeAdmin = User::firstOrCreate(
            ['email' => 'owner@acme.test'],
            [
                'tenant_id' => $tenant->id,
                'name' => 'Acme Owner',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $storeAdmin->assignRole($storeAdminRole);

        Customer::firstOrCreate(
            ['tenant_id' => $tenant->id, 'email' => 'alice@example.com'],
            [
                'name' => 'Alice Chen',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );

        $widgetImg = $this->writeSampleImage(
            'products/sample-widget.svg',
            $this->sampleSvg('#10B981', '#065F46', 'WIDGET')
        );
        $bundleImg = $this->writeSampleImage(
            'products/sample-bundle.svg',
            $this->sampleSvg('#6366F1', '#312E81', 'BUNDLE')
        );

        $product = Product::firstOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'starter-widget'],
            [
                'name' => 'Starter Widget',
                'description' => 'A demo product for the Acme storefront.',
                'price_cents' => 1999,
                'currency' => 'USD',
                'stock_quantity' => 50,
                'is_active' => true,
                'image_path' => $widgetImg,
            ]
        );

        $premium = Product::firstOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'premium-bundle'],
            [
                'name' => 'Premium Bundle',
                'description' => 'Three widgets and a fancy box.',
                'price_cents' => 4999,
                'currency' => 'USD',
                'stock_quantity' => 25,
                'is_active' => true,
                'image_path' => $bundleImg,
            ]
        );

        // Seed a spread of orders across the last 14 days so the chart looks alive.
        if (Order::where('tenant_id', $tenant->id)->count() === 0) {
            $customers = [
                ['email' => 'alice@example.com', 'name' => 'Alice Chen'],
                ['email' => 'bob@example.com',   'name' => 'Bob Diaz'],
                ['email' => 'carol@example.com', 'name' => 'Carol Singh'],
                ['email' => 'dan@example.com',   'name' => 'Dan Eriksen'],
            ];

            for ($i = 0; $i < 12; $i++) {
                $createdAt = now()->subDays(rand(0, 13))->subHours(rand(0, 23));
                $customer = $customers[$i % count($customers)];
                $line = $i % 3 === 0 ? $premium : $product;
                $qty = rand(1, 3);
                $total = $line->price_cents * $qty;
                $status = $i < 9 ? 'paid' : 'pending';

                $order = Order::create([
                    'tenant_id' => $tenant->id,
                    'order_number' => 'ACME-' . strtoupper(Str::random(6)),
                    'customer_email' => $customer['email'],
                    'customer_name' => $customer['name'],
                    'total_cents' => $total,
                    'currency' => 'USD',
                    'status' => $status,
                    'paid_at' => $status === 'paid' ? $createdAt : null,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $line->id,
                    'product_name' => $line->name,
                    'unit_price_cents' => $line->price_cents,
                    'quantity' => $qty,
                    'subtotal_cents' => $total,
                ]);
            }
        }

        // Second tenant — different plan, different theme, to make Super Admin management interesting.
        $aurora = Tenant::firstOrCreate(
            ['slug' => 'aurora'],
            [
                'name' => 'Aurora Apparel',
                'business_type' => 'fashion',
                'contact_email' => 'owner@aurora.test',
                'contact_phone' => '+1 555-0142',
                'subscription_plan' => Tenant::PLAN_PRO,
                'status' => Tenant::STATUS_ACTIVE,
                'onboarded_at' => now()->subDays(45),
            ]
        );

        Store::firstOrCreate(
            ['tenant_id' => $aurora->id],
            [
                'theme' => 'minimal',
                'primary_color' => '#7C3AED',
                'secondary_color' => '#1F2937',
                'font_family' => 'Playfair Display',
                'is_live' => true,
            ]
        );

        $auroraOwner = User::firstOrCreate(
            ['email' => 'owner@aurora.test'],
            [
                'tenant_id' => $aurora->id,
                'name' => 'Aurora Owner',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $auroraOwner->assignRole($storeAdminRole);

        // Third tenant — suspended, to demo the activate flow
        $relic = Tenant::firstOrCreate(
            ['slug' => 'relic'],
            [
                'name' => 'Relic Records',
                'business_type' => 'media',
                'contact_email' => 'owner@relic.test',
                'subscription_plan' => Tenant::PLAN_STARTER,
                'status' => Tenant::STATUS_SUSPENDED,
                'onboarded_at' => now()->subDays(120),
            ]
        );

        Store::firstOrCreate(
            ['tenant_id' => $relic->id],
            [
                'theme' => 'default',
                'is_live' => true,
            ]
        );

        $relicOwner = User::firstOrCreate(
            ['email' => 'owner@relic.test'],
            [
                'tenant_id' => $relic->id,
                'name' => 'Relic Owner',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $relicOwner->assignRole($storeAdminRole);
    }

    private function writeSampleImage(string $path, string $contents): string
    {
        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            $disk->put($path, $contents);
        }
        return $path;
    }

    private function sampleSvg(string $bg, string $fg, string $label): string
    {
        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 400">
    <defs>
        <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="{$bg}"/>
            <stop offset="100%" stop-color="{$fg}"/>
        </linearGradient>
    </defs>
    <rect width="400" height="400" fill="url(#g)"/>
    <text x="200" y="215" text-anchor="middle" font-family="system-ui, sans-serif"
          font-size="40" font-weight="700" fill="white" letter-spacing="6">{$label}</text>
</svg>
SVG;
    }
}
