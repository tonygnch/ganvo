<?php

namespace Tests\Feature;

use App\Filament\StoreAdmin\Pages\CustomizeTheme;
use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Saving the customizer tells the page to reload its preview.
 *
 * Typed text is mirrored into the preview as it changes, but palette, font,
 * section toggles and images are rendered on the server — so until this event
 * existed a merchant pressed Save, saw nothing change beside the form, and had
 * to refresh the whole page to find out whether it had worked.
 */
class CustomizeThemeSaveTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_asks_the_page_to_reload_the_preview(): void
    {
        $tenant = Tenant::create([
            'name' => 'Customize Test',
            'slug' => 'customize-test',
            'status' => Tenant::STATUS_ACTIVE,
            'onboarding_step' => 'done',
        ]);

        Store::create(['tenant_id' => $tenant->id, 'theme' => 'wick', 'currency' => 'EUR', 'is_live' => true]);

        $owner = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Customize Owner',
            'email' => 'owner@customize-test.test',
            'password' => bcrypt(str()->random(32)),
        ]);

        $this->actingAs($owner);
        Filament::setCurrentPanel('store');

        Livewire::test(CustomizeTheme::class)
            ->set('data.palette', 'soot')
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertDispatched('gv-theme-saved');

        $this->assertSame('soot', data_get($tenant->store->fresh()->theme_settings, 'themes.wick.palette'));
    }
}
