<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Plan;
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

        // Subscription plans — now DB-driven, configurable from the SA panel.
        // Yearly price is roughly 10× monthly (2 months free) — convention,
        // not enforced.
        Plan::firstOrCreate(['slug' => 'starter'], [
            'name' => 'Starter',
            'tagline' => 'Get your store online — no card required.',
            'features' => [
                'Up to 25 products',
                '1 storefront theme',
                'Standard storefront on a *.ganvo.io subdomain',
                'Email support',
            ],
            'translations' => [
                [
                    'locale' => 'bg',
                    'name' => 'Starter',
                    'tagline' => 'Пусни магазина онлайн — без нужда от карта.',
                    'features' => [
                        'До 25 продукта',
                        '1 тема за витрината',
                        'Витрина на *.ganvo.io поддомейн',
                        'Поддръжка по имейл',
                    ],
                ],
            ],
            'currency' => 'EUR',
            'price_monthly_cents' => 0,
            'price_yearly_cents'  => 0,
            'is_popular' => false,
            'is_active'  => true,
            'sort_order' => 10,
        ]);
        Plan::firstOrCreate(['slug' => 'pro'], [
            'name' => 'Pro',
            'tagline' => 'Grow without limits.',
            'features' => [
                'Unlimited products',
                'All themes + customization',
                'Custom domain',
                'Multi-currency display',
                'Priority email support',
            ],
            'translations' => [
                [
                    'locale' => 'bg',
                    'name' => 'Pro',
                    'tagline' => 'Расти без ограничения.',
                    'features' => [
                        'Неограничен брой продукти',
                        'Всички теми + персонализация',
                        'Собствен домейн',
                        'Показване в няколко валути',
                        'Приоритетна поддръжка по имейл',
                    ],
                ],
            ],
            'currency' => 'EUR',
            'price_monthly_cents' => 2900,
            'price_yearly_cents'  => 29000,
            'is_popular' => true,
            'is_active'  => true,
            'sort_order' => 20,
        ]);
        Plan::firstOrCreate(['slug' => 'business'], [
            'name' => 'Business',
            'tagline' => 'For established stores ready to scale.',
            'features' => [
                'Everything in Pro',
                'Advanced analytics',
                'Lower transaction fees',
                'Priority phone support',
                'Onboarding concierge',
            ],
            'translations' => [
                [
                    'locale' => 'bg',
                    'name' => 'Business',
                    'tagline' => 'За утвърдени магазини, готови за растеж.',
                    'features' => [
                        'Всичко от Pro',
                        'Разширена аналитика',
                        'По-ниски такси по транзакция',
                        'Приоритетна поддръжка по телефон',
                        'Личен консултант при стартиране',
                    ],
                ],
            ],
            'currency' => 'EUR',
            'price_monthly_cents' => 9900,
            'price_yearly_cents'  => 99000,
            'is_popular' => false,
            'is_active'  => true,
            'sort_order' => 30,
        ]);

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
                'currency' => 'EUR',
                // BGN auto-derives from EUR via the fixed peg, so we don't need
                // an explicit BGN rate here.
                'display_currencies' => ['EUR', 'USD', 'GBP', 'BGN'],
                'fx_rates' => ['USD' => 1.09, 'GBP' => 0.86],
                // Storefront chrome — demo the editable announcement,
                // nav menu, and hero banner.
                'announcement' => [
                    'enabled' => true,
                    'text'    => 'Free shipping on orders over €50 — easy returns within 30 days.',
                    'link'    => null,
                ],
                'nav_menu' => [
                    ['label' => 'Shop',     'url' => '/',          'sort_order' => 10],
                    ['label' => 'Featured', 'url' => '/#featured', 'sort_order' => 20],
                    ['label' => 'About',    'url' => '/#about',    'sort_order' => 30],
                ],
                'hero_banner' => [
                    'enabled'    => true,
                    'title'      => 'Acme Spring Edit',
                    'subtitle'   => 'Bright pieces for sunny days. Designed and shipped from Boston.',
                    'image_path' => null,
                    'cta_label'  => 'Shop the edit',
                    'cta_url'    => '/#shop',
                ],
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
                'currency' => 'EUR',
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
                'currency' => 'EUR',
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
                    'currency' => 'EUR',
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
                // Aurora is a boutique brand based in Sofia — prices in EUR
                // (post Bulgaria-to-Euro switch), offers USD/GBP and BGN.
                // BGN selection triggers the EUR-primary + BGN-secondary dual
                // display required during the transition.
                'currency' => 'EUR',
                'display_currencies' => ['EUR', 'USD', 'GBP', 'BGN'],
                'fx_rates' => ['USD' => 1.09, 'GBP' => 0.86],
                'announcement' => [
                    'enabled' => true,
                    'text'    => 'Complimentary atelier consultations every Saturday.',
                    'link'    => null,
                ],
                'nav_menu' => [
                    ['label' => 'Atelier', 'url' => '/',          'sort_order' => 10],
                    ['label' => 'Lookbook','url' => '/#shop',     'sort_order' => 20],
                ],
                'hero_banner' => [
                    'enabled'    => true,
                    'title'      => 'Made in small batches.',
                    'subtitle'   => 'Editorial pieces, finished by hand in our Sofia studio.',
                    'image_path' => null,
                    'cta_label'  => null,
                    'cta_url'    => null,
                ],
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
