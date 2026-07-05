<?php

namespace Database\Seeders;

use App\Models\Tenant;
use App\Models\Website;
use Illuminate\Database\Seeder;

/**
 * Registers the existing hand-built client websites in the hub. These sites
 * live in their own repos and hosting — Ganvo manages the client
 * relationship (registry, billing, status). Live URLs are left for the SA
 * to fill in from the admin (Websites → edit).
 *
 *   php artisan db:seed --class=Database\\Seeders\\WebsiteClientsSeeder
 */
class WebsiteClientsSeeder extends Seeder
{
    public function run(): void
    {
        $sites = [
            [
                'name' => 'Kass Photography',
                'slug' => 'kass',
                'repo_url' => 'https://github.com/tonygnch/kass',
                'stack' => 'Laravel + Blade',
                'notes' => 'Photography portfolio site. Local repo: ~/Projects/PHP/kass.',
            ],
            [
                'name' => 'Midi BG',
                'slug' => 'midibg',
                'repo_url' => 'https://github.com/tonygnch/midibg',
                'stack' => 'Laravel + Blade',
                'notes' => 'Local repo: ~/Projects/PHP/midibg.',
            ],
            [
                'name' => 'ASG',
                'slug' => 'asg',
                'repo_url' => 'https://github.com/tonygnch/asg',
                'stack' => 'Laravel + Blade',
                'notes' => 'Local repo: ~/Projects/PHP/asg.',
            ],
        ];

        foreach ($sites as $site) {
            $tenant = Tenant::firstOrCreate(
                ['slug' => $site['slug']],
                [
                    'name' => $site['name'],
                    'type' => Tenant::TYPE_WEBSITE,
                    'business_type' => 'website',
                    'status' => Tenant::STATUS_ACTIVE,
                    'onboarded_at' => now(),
                ]
            );
            Website::firstOrCreate(
                ['tenant_id' => $tenant->id],
                [
                    'repo_url' => $site['repo_url'],
                    'stack' => $site['stack'],
                    'notes' => $site['notes'],
                ]
            );
            $this->command->info("  registered: {$site['name']} ({$site['slug']})");
        }
    }
}
