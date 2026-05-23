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
            self::PAGE_MARKETING_HOME => 'Marketing home',
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
        return [
            // ---- SEO ----
            'page_title' => [
                'key' => 'page_title', 'fallback' => 'site.marketing.title',
                'label' => 'Browser tab title', 'help' => 'Shown in the browser tab and as the default social-share title.',
                'type' => 'text', 'max' => 70,
            ],
            'meta_description' => [
                'key' => 'meta_description', 'fallback' => 'site.marketing.meta_description',
                'label' => 'Meta description', 'help' => 'Used by search engines and social previews. Aim for 140–160 chars.',
                'type' => 'textarea', 'max' => 200,
            ],

            // ---- Nav ----
            'nav_features' => [
                'key' => 'nav_features', 'fallback' => 'site.marketing.nav.features',
                'label' => 'Nav: Features link', 'help' => 'Top-bar link label that scrolls to the features section.',
                'type' => 'text', 'max' => 32,
            ],
            'nav_themes' => [
                'key' => 'nav_themes', 'fallback' => 'site.marketing.nav.themes',
                'label' => 'Nav: Themes link', 'help' => 'Top-bar link label that scrolls to the themes section.',
                'type' => 'text', 'max' => 32,
            ],
            'nav_pricing' => [
                'key' => 'nav_pricing', 'fallback' => 'site.marketing.nav.pricing',
                'label' => 'Nav: Pricing link', 'help' => 'Top-bar link label that scrolls to the pricing section.',
                'type' => 'text', 'max' => 32,
            ],

            // ---- Hero ----
            'hero_pill' => [
                'key' => 'hero_pill', 'fallback' => 'site.marketing.hero.pill',
                'label' => 'Hero badge', 'help' => 'Small pill above the hero headline (e.g. "Built for indie brands").',
                'type' => 'text', 'max' => 80,
            ],
            'hero_headline_1' => [
                'key' => 'hero_headline_1', 'fallback' => 'site.marketing.hero.headline_1',
                'label' => 'Hero headline — line 1', 'help' => 'First line of the big headline.',
                'type' => 'text', 'max' => 120,
            ],
            'hero_headline_2' => [
                'key' => 'hero_headline_2', 'fallback' => 'site.marketing.hero.headline_2',
                'label' => 'Hero headline — line 2 (accent)', 'help' => 'Second line, rendered in the gradient accent.',
                'type' => 'text', 'max' => 120,
            ],
            'hero_sub' => [
                'key' => 'hero_sub', 'fallback' => 'site.marketing.hero.sub',
                'label' => 'Hero subheading', 'help' => 'One or two sentences describing what Ganvo does.',
                'type' => 'textarea', 'max' => 400,
            ],
            'hero_cta_primary' => [
                'key' => 'hero_cta_primary', 'fallback' => 'site.marketing.hero.cta_primary',
                'label' => 'Hero primary button', 'help' => 'Main call-to-action — points to /onboarding/signup.',
                'type' => 'text', 'max' => 32,
            ],
            'hero_cta_secondary' => [
                'key' => 'hero_cta_secondary', 'fallback' => 'site.marketing.hero.cta_secondary',
                'label' => 'Hero secondary button', 'help' => 'Secondary call-to-action — scrolls to features.',
                'type' => 'text', 'max' => 32,
            ],

            // ---- Section headings ----
            'features_eyebrow' => [
                'key' => 'features_eyebrow', 'fallback' => 'site.marketing.features.eyebrow',
                'label' => 'Features eyebrow', 'help' => 'Small uppercase label above the Features H2.',
                'type' => 'text', 'max' => 80,
            ],
            'features_h2' => [
                'key' => 'features_h2', 'fallback' => 'site.marketing.features.h2',
                'label' => 'Features H2', 'help' => 'Heading for the features section.',
                'type' => 'text', 'max' => 160,
            ],
            'themes_eyebrow' => [
                'key' => 'themes_eyebrow', 'fallback' => 'site.marketing.themes.eyebrow',
                'label' => 'Themes eyebrow', 'help' => 'Small uppercase label above the Themes H2.',
                'type' => 'text', 'max' => 80,
            ],
            'themes_h2' => [
                'key' => 'themes_h2', 'fallback' => 'site.marketing.themes.h2',
                'label' => 'Themes H2', 'help' => 'Heading for the themes preview section.',
                'type' => 'text', 'max' => 160,
            ],
            'pricing_eyebrow' => [
                'key' => 'pricing_eyebrow', 'fallback' => 'site.marketing.pricing.eyebrow',
                'label' => 'Pricing eyebrow', 'help' => 'Small uppercase label above the Pricing H2.',
                'type' => 'text', 'max' => 80,
            ],
            'pricing_h2' => [
                'key' => 'pricing_h2', 'fallback' => 'site.marketing.pricing.h2',
                'label' => 'Pricing H2', 'help' => 'Heading for the pricing section. Plan cards themselves are edited via System → Plans.',
                'type' => 'text', 'max' => 160,
            ],

            // ---- Bottom CTA strip ----
            'cta_strip_h2' => [
                'key' => 'cta_strip_h2', 'fallback' => 'site.marketing.cta_strip.h2',
                'label' => 'Bottom CTA — heading', 'help' => 'Heading on the dark band near the bottom of the page.',
                'type' => 'text', 'max' => 160,
            ],
            'cta_strip_p' => [
                'key' => 'cta_strip_p', 'fallback' => 'site.marketing.cta_strip.p',
                'label' => 'Bottom CTA — paragraph', 'help' => 'Short paragraph under the CTA heading.',
                'type' => 'textarea', 'max' => 300,
            ],
            'cta_strip_btn' => [
                'key' => 'cta_strip_btn', 'fallback' => 'site.marketing.cta_strip.btn',
                'label' => 'Bottom CTA — button', 'help' => 'Button label on the bottom CTA strip.',
                'type' => 'text', 'max' => 32,
            ],
        ];
    }
}
