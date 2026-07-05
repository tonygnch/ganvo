<?php

namespace App\Filament\SuperAdmin\Resources\Websites\Pages;

use App\Filament\SuperAdmin\Resources\Websites\WebsiteResource;
use App\Models\Tenant;
use App\Models\Website;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CreateWebsite extends CreateRecord
{
    protected static string $resource = WebsiteResource::class;

    /** Create the client tenant behind the website, then the registry row. */
    protected function handleRecordCreation(array $data): Model
    {
        $state = $this->form->getRawState();
        $name = trim((string) ($state['client_name'] ?? 'Client'));

        $slug = Str::slug($name) ?: 'client';
        $base = $slug;
        for ($i = 2; Tenant::where('slug', $slug)->exists(); $i++) {
            $slug = "{$base}-{$i}";
        }

        $tenant = Tenant::create([
            'name' => $name,
            'slug' => $slug,
            'type' => Tenant::TYPE_WEBSITE,
            'business_type' => 'website',
            'contact_email' => $state['client_email'] ?? null,
            'status' => $state['client_status'] ?? Tenant::STATUS_ACTIVE,
            'onboarded_at' => now(),
        ]);

        return Website::create($data + ['tenant_id' => $tenant->id]);
    }
}
