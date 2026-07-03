<?php

namespace App\Themes;

use App\Models\Store;
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

    private function __construct(
        private readonly ?Store $store,
        private readonly string $slug,
        private readonly array $manifest,
        private readonly array $settings,
    ) {
    }

    public static function for(?Store $store, string $slug): self
    {
        $key = ($store?->id ?? 0) . ':' . $slug;
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
                $out .= '<link href="' . e($font['link']) . '" rel="stylesheet">' . "\n";
            }
            foreach (($font['vars'] ?? []) as $var => $value) {
                $vars[$var] = $value;
            }
        }

        if ($vars !== []) {
            $css = '';
            foreach ($vars as $var => $value) {
                $css .= e($var) . ':' . str_replace(['<', '>', '{', '}'], '', $value) . ';';
            }
            $out .= "<style>:root{{$css}}</style>";
        }

        return new HtmlString($out);
    }

    public function manifest(): array
    {
        return $this->manifest;
    }
}
