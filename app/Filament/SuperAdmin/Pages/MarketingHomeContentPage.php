<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Filament\SuperAdmin\Support\BaseSitePageEditor;
use App\Services\SitePageSchemas;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

/**
 * SuperAdmin editor for the public marketing home page.
 * Fields + i18n fallback keys live in {@see SitePageSchemas}.
 *
 * Plan cards on the home page render from the Plans resource and aren't
 * editable here — see SuperAdmin → Billing → Plans for that copy.
 */
class MarketingHomeContentPage extends BaseSitePageEditor
{
    protected string $view = 'filament.super-admin.pages.site-page-editor';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?string $navigationLabel = 'Home page content';

    protected static string|\UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?string $title = 'Marketing home page content';

    protected static ?string $slug = 'home-content';

    protected static ?int $navigationSort = 96;

    protected static function pageSlug(): string
    {
        return SitePageSchemas::PAGE_MARKETING_HOME;
    }

    protected function savedNotificationBody(): string
    {
        return 'The marketing home page is using the new copy.';
    }
}
