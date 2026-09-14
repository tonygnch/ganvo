<?php

namespace Tests\Feature;

use App\Models\Store;
use App\Models\Tenant;
use App\Models\User;
use App\Themes\ThemeRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The storefront beside the Customize theme form, for every theme.
 *
 * The preview endpoint was built next to Sankevi and handed the home view an
 * empty $filters array. Every other theme's home page includes the catalogue
 * controls, which read $filters['q'] and friends directly — so the preview
 * returned a 500 for twelve of thirteen themes and nobody saw it until a Brick
 * shop opened the page.
 */
class ThemePreviewTest extends TestCase
{
    use RefreshDatabase;

    public static function everyTheme(): array
    {
        return array_combine(ThemeRegistry::ids(), array_map(fn ($id) => [$id], ThemeRegistry::ids()));
    }

    #[DataProvider('everyTheme')]
    public function test_the_customize_theme_preview_renders(string $theme): void
    {
        $tenant = Tenant::create([
            'name' => 'Preview Test',
            'slug' => 'preview-test',
            'status' => Tenant::STATUS_ACTIVE,
            'onboarding_step' => 'done',
        ]);

        Store::create([
            'tenant_id' => $tenant->id,
            'theme' => $theme,
            'currency' => 'EUR',
            'is_live' => true,
        ]);

        $owner = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Preview Owner',
            'email' => 'owner@preview-test.test',
            'password' => bcrypt(str()->random(32)),
        ]);

        $this->actingAs($owner)
            ->get('http://'.config('ganvo.central_domain').'/store/theme/preview')
            ->assertOk();
    }
}
