<?php

namespace App\Services;

/**
 * Source of truth for which editable text fields each marketing page has.
 *
 * Used by:
 *   - SitePage::text() — to know which i18n catalog key backs each field
 *     when the DB override is blank.
 *   - The Filament edit page — to render the right inputs per page.
 *
 * Adding a new editable page is: register a new entry here, then point a
 * Filament page at it. No migration, no model changes.
 *
 * Each field entry shape:
 *   [
 *     'key'         => string  // matches the JSON key in site_pages.content
 *     'fallback'    => string  // i18n catalog key for the default value
 *     'label'       => string  // human label in the admin form
 *     'help'        => string  // short helper text shown under the input
 *     'type'        => 'text' | 'textarea'  // form input type
 *     'max'         => ?int    // max length (drives the column constraint hint)
 *   ]
 */
class SitePageSchemas
{
    public const PAGE_COMING_SOON = 'coming_soon';
    // Reserved for an upcoming slice — adding marketing-home editing later
    // is just filling in this schema and pointing a Filament page at it.
    public const PAGE_MARKETING_HOME = 'marketing_home';

    /**
     * @return array<string, array{key: string, fallback: string, label: string, help: string, type: string, max: ?int}>
     */
    public static function schemaFor(string $page): array
    {
        return match ($page) {
            self::PAGE_COMING_SOON => self::comingSoonSchema(),
            self::PAGE_MARKETING_HOME => self::marketingHomeSchema(),
            default => [],
        };
    }

    /** All pages with editable content, used by the SA navigation. */
    public static function supportedPages(): array
    {
        return [
            self::PAGE_COMING_SOON => 'Coming soon',
            // self::PAGE_MARKETING_HOME => 'Marketing home', // enable when ready
        ];
    }

    private static function comingSoonSchema(): array
    {
        return [
            'eyebrow' => [
                'key' => 'eyebrow', 'fallback' => 'site.marketing.coming_soon.eyebrow',
                'label' => 'Eyebrow', 'help' => 'Small uppercase label above the headline (e.g. "Launching soon").',
                'type' => 'text', 'max' => 64,
            ],
            'headline_1' => [
                'key' => 'headline_1', 'fallback' => 'site.marketing.coming_soon.headline_1',
                'label' => 'Headline — line 1', 'help' => 'First line of the big headline.',
                'type' => 'text', 'max' => 120,
            ],
            'headline_2' => [
                'key' => 'headline_2', 'fallback' => 'site.marketing.coming_soon.headline_2',
                'label' => 'Headline — line 2 (accent)', 'help' => 'Second line, rendered in the gradient accent.',
                'type' => 'text', 'max' => 120,
            ],
            'lead' => [
                'key' => 'lead', 'fallback' => 'site.marketing.coming_soon.lead',
                'label' => 'Lead paragraph', 'help' => 'Two or three sentences describing what\'s coming.',
                'type' => 'textarea', 'max' => 500,
            ],
            'email_placeholder' => [
                'key' => 'email_placeholder', 'fallback' => 'site.marketing.coming_soon.email_placeholder',
                'label' => 'Email field placeholder', 'help' => 'Greyed-out text inside the email input.',
                'type' => 'text', 'max' => 64,
            ],
            'notify_button' => [
                'key' => 'notify_button', 'fallback' => 'site.marketing.coming_soon.notify',
                'label' => 'Notify button label', 'help' => 'Text on the submit button.',
                'type' => 'text', 'max' => 32,
            ],
            'thanks_message' => [
                'key' => 'thanks_message', 'fallback' => 'site.marketing.coming_soon.thanks',
                'label' => 'Post-signup message', 'help' => 'Shown after a successful signup.',
                'type' => 'text', 'max' => 120,
            ],
            'helper_text' => [
                'key' => 'helper_text', 'fallback' => 'site.marketing.coming_soon.helper',
                'label' => 'Helper text', 'help' => 'Small line under the form (e.g. "No spam").',
                'type' => 'text', 'max' => 160,
            ],
            'page_title' => [
                'key' => 'page_title', 'fallback' => 'site.marketing.coming_soon.title',
                'label' => 'Browser tab title', 'help' => 'Shown in the browser tab; SEO matters here.',
                'type' => 'text', 'max' => 70,
            ],
            'meta_description' => [
                'key' => 'meta_description', 'fallback' => 'site.marketing.coming_soon.meta_description',
                'label' => 'Meta description', 'help' => 'Used by search engines and social previews.',
                'type' => 'textarea', 'max' => 200,
            ],
        ];
    }

    private static function marketingHomeSchema(): array
    {
        // Placeholder for the next slice. Wire up fields when ready.
        return [];
    }
}
