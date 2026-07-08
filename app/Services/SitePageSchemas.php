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
            'nav_services' => [
                'key' => 'nav_services', 'fallback' => 'site.marketing.nav.services',
                'label' => 'Nav: Services link', 'help' => 'Top-bar link that scrolls to the services section.',
                'type' => 'text', 'max' => 32,
            ],
            'nav_work' => [
                'key' => 'nav_work', 'fallback' => 'site.marketing.nav.work',
                'label' => 'Nav: Work link', 'help' => 'Top-bar link that scrolls to the work section.',
                'type' => 'text', 'max' => 32,
            ],
            'nav_contact' => [
                'key' => 'nav_contact', 'fallback' => 'site.marketing.nav.contact',
                'label' => 'Nav: Contact link', 'help' => 'Top-bar link that scrolls to the contact section.',
                'type' => 'text', 'max' => 32,
            ],

            // ---- Hero ----
            'hero_kicker' => [
                'key' => 'hero_kicker', 'fallback' => 'site.marketing.hero.kicker',
                'label' => 'Hero kicker', 'help' => 'Small mono label above the hero headline.',
                'type' => 'text', 'max' => 80,
            ],
            'hero_headline' => [
                'key' => 'hero_headline', 'fallback' => 'site.marketing.hero.headline',
                'label' => 'Hero headline', 'help' => 'First part of the big headline.',
                'type' => 'text', 'max' => 120,
            ],
            'hero_headline_accent' => [
                'key' => 'hero_headline_accent', 'fallback' => 'site.marketing.hero.headline_accent',
                'label' => 'Hero headline — accent', 'help' => 'Second part, rendered in the highlighted colour.',
                'type' => 'text', 'max' => 120,
            ],
            'hero_sub' => [
                'key' => 'hero_sub', 'fallback' => 'site.marketing.hero.sub',
                'label' => 'Hero subheading', 'help' => 'One or two sentences describing the studio.',
                'type' => 'textarea', 'max' => 400,
            ],
            'hero_cta_primary' => [
                'key' => 'hero_cta_primary', 'fallback' => 'site.marketing.hero.cta_primary',
                'label' => 'Hero primary button', 'help' => 'Main call-to-action — scrolls to the contact form.',
                'type' => 'text', 'max' => 32,
            ],
            'hero_cta_secondary' => [
                'key' => 'hero_cta_secondary', 'fallback' => 'site.marketing.hero.cta_secondary',
                'label' => 'Hero secondary button', 'help' => 'Secondary call-to-action — scrolls to the work.',
                'type' => 'text', 'max' => 32,
            ],

            // ---- Positioning statement ----
            'statement' => [
                'key' => 'statement', 'fallback' => 'site.marketing.statement',
                'label' => 'Statement', 'help' => 'The short positioning statement under the hero (revealed line by line).',
                'type' => 'textarea', 'max' => 400,
            ],

            // ---- Section headings (the items in each section live in the language files) ----
            'services_eyebrow' => [
                'key' => 'services_eyebrow', 'fallback' => 'site.marketing.services.eyebrow',
                'label' => 'Services eyebrow', 'help' => 'Small label above the services heading.',
                'type' => 'text', 'max' => 80,
            ],
            'services_heading' => [
                'key' => 'services_heading', 'fallback' => 'site.marketing.services.heading',
                'label' => 'Services heading', 'help' => 'Heading for the services section.',
                'type' => 'text', 'max' => 160,
            ],
            'work_eyebrow' => [
                'key' => 'work_eyebrow', 'fallback' => 'site.marketing.work.eyebrow',
                'label' => 'Work eyebrow', 'help' => 'Small label above the Work heading. The case studies themselves live in the language files.',
                'type' => 'text', 'max' => 80,
            ],
            'work_heading' => [
                'key' => 'work_heading', 'fallback' => 'site.marketing.work.heading',
                'label' => 'Work heading', 'help' => 'Heading for the selected-work section.',
                'type' => 'text', 'max' => 160,
            ],
            'why_eyebrow' => [
                'key' => 'why_eyebrow', 'fallback' => 'site.marketing.why.eyebrow',
                'label' => 'Why Ganvo eyebrow', 'help' => 'Small label above the “why” heading.',
                'type' => 'text', 'max' => 80,
            ],
            'why_heading' => [
                'key' => 'why_heading', 'fallback' => 'site.marketing.why.heading',
                'label' => 'Why Ganvo heading', 'help' => 'Heading for the differentiators section.',
                'type' => 'text', 'max' => 160,
            ],
            'process_eyebrow' => [
                'key' => 'process_eyebrow', 'fallback' => 'site.marketing.process.eyebrow',
                'label' => 'Process eyebrow', 'help' => 'Small label above the process heading.',
                'type' => 'text', 'max' => 80,
            ],
            'process_heading' => [
                'key' => 'process_heading', 'fallback' => 'site.marketing.process.heading',
                'label' => 'Process heading', 'help' => 'Heading for the process section.',
                'type' => 'text', 'max' => 160,
            ],

            // ---- Contact ----
            'contact_eyebrow' => [
                'key' => 'contact_eyebrow', 'fallback' => 'site.marketing.contact.eyebrow',
                'label' => 'Contact eyebrow', 'help' => 'Small label above the contact heading.',
                'type' => 'text', 'max' => 80,
            ],
            'contact_heading' => [
                'key' => 'contact_heading', 'fallback' => 'site.marketing.contact.heading',
                'label' => 'Contact heading', 'help' => 'Heading for the “start a project” section.',
                'type' => 'text', 'max' => 160,
            ],
            'contact_sub' => [
                'key' => 'contact_sub', 'fallback' => 'site.marketing.contact.sub',
                'label' => 'Contact subheading', 'help' => 'Short line under the contact heading.',
                'type' => 'textarea', 'max' => 300,
            ],
            'contact_email' => [
                'key' => 'contact_email', 'fallback' => 'site.marketing.contact.email',
                'label' => 'Contact email', 'help' => 'Shown as a direct mailto link. Also where inquiry emails default to.',
                'type' => 'text', 'max' => 120,
            ],
            'contact_phone' => [
                'key' => 'contact_phone', 'fallback' => 'site.marketing.contact.phone',
                'label' => 'Contact phone', 'help' => 'Shown as a tel link. Leave blank to hide.',
                'type' => 'text', 'max' => 40,
            ],
            'contact_instagram' => [
                'key' => 'contact_instagram', 'fallback' => 'site.marketing.contact.instagram',
                'label' => 'Instagram URL', 'help' => 'Full link. Leave blank to hide.',
                'type' => 'text', 'max' => 200,
            ],
            'contact_facebook' => [
                'key' => 'contact_facebook', 'fallback' => 'site.marketing.contact.facebook',
                'label' => 'Facebook URL', 'help' => 'Full link. Leave blank to hide.',
                'type' => 'text', 'max' => 200,
            ],
        ];
    }
}
