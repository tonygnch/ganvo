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
        // Compact entry builder — every entry carries the full shape the
        // editor and SitePage::text() expect; 'group' names the collapsible
        // section it renders under in the admin form.
        $f = fn (string $key, string $fallback, string $label, string $help, string $type = 'text', ?int $max = 160, string $group = 'General') => [
            'key' => $key, 'fallback' => $fallback, 'label' => $label,
            'help' => $help, 'type' => $type, 'max' => $max, 'group' => $group,
        ];

        $fields = [
            // ---- SEO ----
            'page_title' => $f('page_title', 'site.marketing.title', 'Browser tab title', 'Shown in the browser tab and as the default social-share title.', 'text', 70, 'SEO'),
            'meta_description' => $f('meta_description', 'site.marketing.meta_description', 'Meta description', 'Used by search engines and social previews. Aim for 140-160 chars.', 'textarea', 200, 'SEO'),

            // ---- Site chrome ----
            'nav_services' => $f('nav_services', 'site.marketing.nav.services', 'Nav: Services link', 'Top-bar link that scrolls to the services section.', 'text', 32, 'Site chrome'),
            'nav_work' => $f('nav_work', 'site.marketing.nav.work', 'Nav: Work link', 'Top-bar link that scrolls to the work section.', 'text', 32, 'Site chrome'),
            'nav_contact' => $f('nav_contact', 'site.marketing.nav.contact', 'Nav: Contact link', 'Top-bar link that scrolls to the contact section.', 'text', 32, 'Site chrome'),
            'nav_book' => $f('nav_book', 'site.marketing.nav.book', 'Nav: Book-a-call button', 'The primary button in the top bar.', 'text', 32, 'Site chrome'),
            'loader_label' => $f('loader_label', 'site.marketing.loader', 'Loading screen label', 'Word next to the percentage on the loading screen.', 'text', 32, 'Site chrome'),
            'footer_tagline' => $f('footer_tagline', 'site.marketing.footer.tagline', 'Footer tagline', 'One line under the footer logo.', 'text', 200, 'Site chrome'),

            // ---- Hero ----
            'hero_headline' => $f('hero_headline', 'site.marketing.hero.headline', 'Headline', 'First part of the big headline.', 'text', 120, 'Hero'),
            'hero_headline_accent' => $f('hero_headline_accent', 'site.marketing.hero.headline_accent', 'Headline — accent', 'Second part, rendered in the highlighted colour.', 'text', 120, 'Hero'),
            'hero_sub' => $f('hero_sub', 'site.marketing.hero.sub', 'Subheading', 'One or two sentences under the headline (currently empty by design).', 'textarea', 400, 'Hero'),
            'hero_cta_primary' => $f('hero_cta_primary', 'site.marketing.hero.cta_primary', 'Primary button (unused)', 'Kept for when hero buttons return.', 'text', 32, 'Hero'),
            'hero_cta_secondary' => $f('hero_cta_secondary', 'site.marketing.hero.cta_secondary', 'Secondary button (unused)', 'Kept for when hero buttons return.', 'text', 32, 'Hero'),
            'hero_meta_primary' => $f('hero_meta_primary', 'site.marketing.hero.meta_primary', 'Meta strip — left', 'Bottom strip, first part (e.g. "EST 2024 · Web Studio").', 'text', 80, 'Hero'),
            'hero_meta_secondary' => $f('hero_meta_secondary', 'site.marketing.contact.assurances.0', 'Meta strip — right', 'Bottom strip, second part (defaults to the first assurance).', 'text', 120, 'Hero'),

            // ---- Who are we ----
            'about_eyebrow' => $f('about_eyebrow', 'site.marketing.about.eyebrow', 'About eyebrow', 'Small label above the statement ("Who are we?").', 'text', 80, 'Who are we'),
            'statement' => $f('statement', 'site.marketing.statement', 'Statement', 'The positioning statement, revealed line by line.', 'textarea', 400, 'Who are we'),

            // ---- Services ----
            'services_eyebrow' => $f('services_eyebrow', 'site.marketing.services.eyebrow', 'Eyebrow', 'Small label above the services heading.', 'text', 80, 'Services'),
            'services_heading' => $f('services_heading', 'site.marketing.services.heading', 'Heading', 'Heading for the services section.', 'text', 160, 'Services'),
        ];

        for ($i = 1; $i <= 5; $i++) {
            $fields["services_item_{$i}_title"] = $f("services_item_{$i}_title", 'site.marketing.services.items.' . ($i - 1) . '.title', "Service {$i} — title", '', 'text', 120, 'Services');
            $fields["services_item_{$i}_body"] = $f("services_item_{$i}_body", 'site.marketing.services.items.' . ($i - 1) . '.body', "Service {$i} — description", '', 'textarea', 400, 'Services');
        }

        $fields['process_eyebrow'] = $f('process_eyebrow', 'site.marketing.process.eyebrow', 'Eyebrow', 'Small label above the process heading.', 'text', 80, 'Process');
        $fields['process_heading'] = $f('process_heading', 'site.marketing.process.heading', 'Heading', 'Heading for the process timeline.', 'text', 160, 'Process');
        for ($i = 1; $i <= 7; $i++) {
            $fields["process_step_{$i}_title"] = $f("process_step_{$i}_title", 'site.marketing.process.steps.' . ($i - 1) . '.title', "Step {$i} — title", '', 'text', 120, 'Process');
            $fields["process_step_{$i}_body"] = $f("process_step_{$i}_body", 'site.marketing.process.steps.' . ($i - 1) . '.body', "Step {$i} — description", '', 'textarea', 300, 'Process');
        }

        $fields['why_eyebrow'] = $f('why_eyebrow', 'site.marketing.why.eyebrow', 'Why Ganvo eyebrow', 'For the currently hidden "Why Ganvo" section.', 'text', 80, 'Why Ganvo (hidden)');
        $fields['why_heading'] = $f('why_heading', 'site.marketing.why.heading', 'Why Ganvo heading', 'For the currently hidden "Why Ganvo" section.', 'text', 160, 'Why Ganvo (hidden)');

        $fields['work_eyebrow'] = $f('work_eyebrow', 'site.marketing.work.eyebrow', 'Eyebrow', 'Small label above the Work heading.', 'text', 80, 'Work');
        $fields['work_heading'] = $f('work_heading', 'site.marketing.work.heading', 'Heading', 'Heading for the selected-work section.', 'text', 160, 'Work');
        $fields['work_lead'] = $f('work_lead', 'site.marketing.work.lead', 'Lead paragraph', 'Short paragraph under the Work heading.', 'textarea', 400, 'Work');
        $fields['work_visit_label'] = $f('work_visit_label', 'site.marketing.work.visit', 'Visit-site label', 'Label on each project link ("View site").', 'text', 40, 'Work');
        for ($i = 1; $i <= 3; $i++) {
            $n = $i - 1;
            $fields["work_scene_{$i}_name"] = $f("work_scene_{$i}_name", "site.marketing.work.scenes.{$n}.name", "Project {$i} — name", '', 'text', 80, 'Work');
            $fields["work_scene_{$i}_type"] = $f("work_scene_{$i}_type", "site.marketing.work.scenes.{$n}.type", "Project {$i} — type", 'e.g. "Photography · Portfolio".', 'text', 80, 'Work');
            $fields["work_scene_{$i}_url"] = $f("work_scene_{$i}_url", "site.marketing.work.scenes.{$n}.url", "Project {$i} — URL", 'Full https:// link to the live site.', 'text', 200, 'Work');
        }

        $fields['contact_eyebrow'] = $f('contact_eyebrow', 'site.marketing.contact.eyebrow', 'Eyebrow', 'Small label above the contact heading.', 'text', 80, 'Contact');
        $fields['contact_heading'] = $f('contact_heading', 'site.marketing.contact.heading', 'Heading', 'Heading for the book-a-call section.', 'text', 160, 'Contact');
        $fields['contact_sub'] = $f('contact_sub', 'site.marketing.contact.sub', 'Subheading', 'Short line under the contact heading (hidden on phones).', 'textarea', 300, 'Contact');
        for ($i = 1; $i <= 3; $i++) {
            $fields["contact_assurance_{$i}"] = $f("contact_assurance_{$i}", 'site.marketing.contact.assurances.' . ($i - 1), "Assurance {$i}", 'Checkmark bullet beside the form.', 'text', 120, 'Contact');
        }
        $fields['contact_thanks'] = $f('contact_thanks', 'site.marketing.contact.thanks', 'Thank-you message', 'Shown after a successful inquiry.', 'text', 200, 'Contact');
        $fields['contact_email'] = $f('contact_email', 'site.marketing.contact.email', 'Contact email', 'Shown as a direct mailto link. Also where inquiry emails default to.', 'text', 120, 'Contact');
        $fields['contact_phone'] = $f('contact_phone', 'site.marketing.contact.phone', 'Contact phone', 'Shown as a tel link. Leave blank to hide.', 'text', 40, 'Contact');
        $fields['contact_instagram'] = $f('contact_instagram', 'site.marketing.contact.instagram', 'Instagram URL', 'Full link. Leave blank to hide.', 'text', 200, 'Contact');
        $fields['contact_facebook'] = $f('contact_facebook', 'site.marketing.contact.facebook', 'Facebook URL', 'Full link. Leave blank to hide.', 'text', 200, 'Contact');

        // ---- Contact form labels ----
        $formLabels = [
            'name' => 'Name field label', 'email' => 'Email field label', 'company' => 'Company field label',
            'project_type' => 'Project-type field label', 'message' => 'Message field label',
            'submit' => 'Submit button', 'sending' => 'Submit button while sending', 'choose' => 'Select placeholder',
        ];
        foreach ($formLabels as $key => $label) {
            $fields["form_{$key}"] = $f("form_{$key}", "site.marketing.contact.form.{$key}", $label, '', 'text', 80, 'Contact form');
        }
        foreach (['website', 'redesign', 'webapp', 'other'] as $type) {
            $fields["type_{$type}"] = $f("type_{$type}", "site.marketing.contact.types.{$type}", 'Project type: ' . $type, 'Option label in the "What do you need?" select.', 'text', 60, 'Contact form');
        }

        return $fields;
    }
}
