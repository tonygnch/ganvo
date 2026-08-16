<?php

namespace App\Themes;

use App\Models\Store;
use App\Support\ThemeCopyHtml;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

/**
 * Per-store theme customization, injected into every `themes.*` view as $theme.
 *
 * Reads the active theme's manifest (resources/views/themes/{slug}/manifest.php)
 * and the merchant's overrides (stores.theme_settings['themes'][{slug}]) and
 * answers the three questions templates ask:
 *
 *   $theme->copy('craft_body')   — merchant text, else the theme's default
 *   $theme->on('craft_band')     — is this section/motif enabled?
 *   $theme->label('roast_pips')  — a motif's merchant-editable label text
 *
 * plus head extras (palette preset + font pairing as :root overrides) via
 * $theme->headExtras(). Themes without a manifest behave exactly as before —
 * every method returns its default.
 *
 * Settings are keyed by theme slug, so switching themes never discards the
 * customizations made for another theme.
 */
class ThemeCustomizer
{
    /** @var array<string, self> request-scope cache */
    private static array $instances = [];

    /**
     * Whether copy is being rendered INTO THE ADMIN'S PREVIEW rather than to a
     * visitor. Set once per request by the preview controller; never true on a
     * public storefront.
     */
    private static bool $editMode = false;

    public static function enableEditMode(): void
    {
        self::$editMode = true;
    }

    public static function editModeEnabled(): bool
    {
        return self::$editMode;
    }

    private function __construct(
        private readonly ?Store $store,
        private readonly string $slug,
        private readonly array $manifest,
        private readonly array $settings,
    ) {}

    public static function for(?Store $store, string $slug): self
    {
        $key = ($store?->id ?? 0).':'.$slug;
        if (! isset(self::$instances[$key])) {
            $manifest = ThemeRegistry::manifest($slug);
            $settings = $store
                ? (array) data_get($store->theme_settings, "themes.{$slug}", [])
                : [];
            self::$instances[$key] = new self($store, $slug, $manifest, $settings);
        }

        return self::$instances[$key];
    }

    /** Merchant copy for a content field, falling back to the theme default. */
    public function copy(string $key): string
    {
        $override = trim((string) data_get($this->settings, "content.{$key}", ''));
        if ($override !== '') {
            return $override;
        }
        $field = $this->manifest['content'][$key] ?? [];
        if (isset($field['default_lang'])) {
            return __($field['default_lang']);
        }

        return (string) ($field['default'] ?? '');
    }

    /**
     * A copy slot rendered for the page, tagged so the admin preview can find
     * it — the difference between a form of 54 unlabelled boxes and clicking
     * the sentence you want to change.
     *
     * ALSO THE ONE PLACE ESCAPING IS DECIDED. Call sites used to choose:
     * {{ }} for prose, {!! !!} for the headings that carry <em>. That put the
     * decision next to 30 different strings and made "which of these is
     * unescaped?" a question you answered by grepping. Here, a slot whose name
     * ends in _html gets the same allowlist the merchant's input is sanitised
     * against on save, and everything else is escaped. Call sites are all
     * {!! $theme->editable('slot') !!} and no longer have a choice to get wrong.
     */
    public function editable(string $key): HtmlString
    {
        $value = $this->copy($key);

        $html = ThemeCopyHtml::isHtmlSlot($key)
            ? ThemeCopyHtml::sanitize($value)
            : e($value);

        if (! self::$editMode) {
            return new HtmlString($html);
        }

        // The wrapper is inline so it cannot disturb the layout it sits in;
        // everything visual about it lives in the preview's injected CSS.
        return new HtmlString(
            '<span class="gv-edit" data-gv-slot="'.e($key).'">'.$html.'</span>'
        );
    }

    /**
     * The slot tag on its own, for text that is assembled in PHP before it is
     * echoed — the capability cells, the reasons, the counters. Those are built
     * into arrays first (so the list can renumber when one is cleared), so they
     * cannot go through editable(); the attribute goes on the element instead.
     *
     * Empty outside the preview, so slot names never appear in public HTML.
     */
    public function slotAttr(string $key): HtmlString
    {
        return new HtmlString(
            self::$editMode ? ' data-gv-slot="'.e($key).'"' : ''
        );
    }

    /** Is a section or motif enabled? Unknown ids default to on. */
    public function on(string $id): bool
    {
        $saved = data_get($this->settings, "sections.{$id}");
        if ($saved === null) {
            $saved = data_get($this->settings, "motifs.{$id}.enabled");
        }
        if ($saved !== null) {
            return (bool) $saved;
        }
        $def = $this->manifest['sections'][$id]['default']
            ?? $this->manifest['motifs'][$id]['default']
            ?? true;

        return (bool) $def;
    }

    /**
     * A themed image slot's URL: merchant upload → theme demo default → null.
     * Templates keep their designed placeholder as the final fallback:
     *
     *   @if ($url = $theme->image('story_band')) <img src="{{ $url }}"> @else …placeholder… @endif
     */
    public function image(string $slot): ?string
    {
        $override = trim((string) data_get($this->settings, "images.{$slot}", ''));
        if ($override !== '') {
            return Storage::disk('public')->url($override);
        }
        $default = $this->manifest['images'][$slot]['default'] ?? null;

        return $default ? asset($default) : null;
    }

    /**
     * The same image slot in its DAYLIGHT colourway, when we ship one.
     *
     * A theme's own mark is drawn for the dark ground it normally sits on, so
     * on the pale mode it needs its counterpart or it goes invisible against
     * the card it is printed on. The pairs are ours by filename; anything a
     * merchant uploaded comes back untouched, because we cannot recolour
     * someone else's artwork and showing it twice beats showing nothing.
     *
     * Lives here rather than in the templates because it was in the templates:
     * the layout learned about the .png pair and the two auth pages did not,
     * so the login card kept printing a cream mark on cream.
     */
    public function imageDaylight(string $slot): ?string
    {
        $url = $this->image($slot);

        if ($url === null) {
            return null;
        }

        foreach (self::DAYLIGHT_PAIRS as $night => $day) {
            if (str_contains($url, $night)) {
                return str_replace($night, $day, $url);
            }
        }

        return $url;
    }

    /** Night-ground filename => its pale-ground counterpart. */
    private const DAYLIGHT_PAIRS = [
        'mark-cream.png' => 'mark-forest.png',
        'mark-cream.svg' => 'mark.svg',
        'lockup-cream.png' => 'lockup-forest.png',
        'fsc-cream.png' => 'fsc-forest.png',
    ];

    /** A motif's text label (e.g. what the roast pips or BATCH stamp say). */
    public function label(string $motifId): string
    {
        $override = trim((string) data_get($this->settings, "motifs.{$motifId}.text", ''));
        if ($override !== '') {
            return $override;
        }
        $motif = $this->manifest['motifs'][$motifId] ?? [];
        if (isset($motif['text_default_lang'])) {
            return __($motif['text_default_lang']);
        }

        return (string) ($motif['text_default'] ?? '');
    }

    /**
     * Extra <head> markup: the selected font pairing's stylesheet link plus a
     * :root override block for the palette preset + font vars. Rendered AFTER
     * the theme's own <style>, so the overrides win.
     */
    public function headExtras(): HtmlString
    {
        $out = '';
        $vars = [];

        $palette = $this->manifest['palettes'][data_get($this->settings, 'palette', '')] ?? null;
        foreach (($palette['vars'] ?? []) as $var => $value) {
            $vars[$var] = $value;
        }

        $font = $this->manifest['fonts'][data_get($this->settings, 'font', '')] ?? null;
        if ($font) {
            if (! empty($font['link'])) {
                $out .= '<link href="'.e($font['link']).'" rel="stylesheet">'."\n";
            }
            foreach (($font['vars'] ?? []) as $var => $value) {
                $vars[$var] = $value;
            }
        }

        $css = '';
        foreach ($vars as $var => $value) {
            $css .= e($var).':'.str_replace(['<', '>', '{', '}'], '', $value).';';
        }
        if ($css !== '') {
            $css = ":root{{$css}}";
        }
        // Alternate color-mode token map (visitor sun/moon toggle).
        $css .= $this->modeCss();
        if ($css !== '') {
            $out .= "<style>{$css}</style>";
        }

        return new HtmlString($out);
    }

    /**
     * The theme's alternate color mode ('light' or 'dark'), if its manifest
     * declares one under 'modes'. The theme's own :root is its native mode;
     * the visitor toggle switches <html data-mode="…"> to the alternate.
     */
    public function alternateMode(): ?string
    {
        $modes = array_keys($this->manifest['modes'] ?? []);

        return $modes[0] ?? null;
    }

    /** CSS for the alternate mode: html[data-mode="X"] { --token: value; } */
    public function modeCss(): string
    {
        $out = '';
        foreach (($this->manifest['modes'] ?? []) as $mode => $def) {
            $css = '';
            foreach (($def['vars'] ?? []) as $var => $value) {
                $css .= e($var).':'.str_replace(['<', '>', '{', '}'], '', $value).';';
            }
            if ($css !== '') {
                $out .= 'html[data-mode="'.e($mode).'"]{'.$css.'}';
            }
        }

        return $out;
    }

    public function manifest(): array
    {
        return $this->manifest;
    }
}
