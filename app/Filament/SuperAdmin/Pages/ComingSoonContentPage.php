<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Filament\SuperAdmin\Support\BaseSitePageEditor;
use App\Services\SitePageSchemas;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

/**
 * SuperAdmin editor for the coming-soon splash page.
 * Fields + i18n fallback keys live in {@see SitePageSchemas}.
 */
class ComingSoonContentPage extends BaseSitePageEditor
{
    protected string $view = 'filament.super-admin.pages.site-page-editor';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?string $navigationLabel = 'Coming-soon content';

    protected static string|\UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?string $title = 'Coming-soon page content';

    protected static ?string $slug = 'coming-soon-content';

    protected static ?int $navigationSort = 95;

    protected static function pageSlug(): string
    {
        return SitePageSchemas::PAGE_COMING_SOON;
    }

    protected function savedNotificationBody(): string
    {
        return 'The coming-soon page is using the new copy.';
    }
}
