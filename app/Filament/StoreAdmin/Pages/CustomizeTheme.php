<?php

namespace App\Filament\StoreAdmin\Pages;

use App\Models\Store;
use App\Support\ThemeCopyHtml;
use App\Themes\ThemeCustomizer;
use App\Themes\ThemeRegistry;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

/**
 * Schema-driven theme customizer. The form is generated from the active
 * theme's manifest.php (palette presets, font pairings, section + motif
 * toggles, content fields), so every theme that ships a manifest gets a
 * full editor with no admin code. Settings persist per theme slug under
 * stores.theme_settings['themes'], so switching themes keeps each theme's
 * customizations intact.
 */
class CustomizeTheme extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.store-admin.pages.customize-theme';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaintBrush;

    public ?array $data = [];

    public string $themeSlug = '';

    public string $themeName = '';

    /*
     | Grouped rather than one flat list of eleven. getNavigationGroup() and not
     | the static $navigationGroup the SuperAdmin panel uses: a static property
     | initialiser cannot call __(), so that one is stuck in English forever.
     */
    public static function getNavigationGroup(): ?string
    {
        return __('admin.nav.group.shop');
    }

    protected static ?int $navigationSort = 20;

    public static function getNavigationLabel(): string
    {
        return __('admin.theme.nav.label');
    }

    public function getTitle(): string|Htmlable
    {
        return __('admin.theme.nav.label');
    }

    public function mount(): void
    {
        $store = $this->getStore();
        $this->themeSlug = $store->theme ?: 'default';
        $this->themeName = ThemeRegistry::get($this->themeSlug)['name'] ?? $this->themeSlug;

        $manifest = ThemeRegistry::manifest($this->themeSlug);
        $saved = (array) data_get($store->theme_settings, "themes.{$this->themeSlug}", []);

        $data = [
            'palette' => data_get($saved, 'palette', array_key_first($manifest['palettes'] ?? []) ?? ''),
            'font' => data_get($saved, 'font', array_key_first($manifest['fonts'] ?? []) ?? ''),
        ];
        foreach (($manifest['sections'] ?? []) as $id => $section) {
            $data["section_{$id}"] = (bool) data_get($saved, "sections.{$id}", $section['default'] ?? true);
        }
        foreach (($manifest['motifs'] ?? []) as $id => $motif) {
            $data["motif_{$id}"] = (bool) data_get($saved, "motifs.{$id}.enabled", $motif['default'] ?? true);
            if (isset($motif['text_label'])) {
                $data["motif_text_{$id}"] = (string) data_get($saved, "motifs.{$id}.text", '');
            }
        }
        /*
         | PREFILL WITH WHAT THE SITE ACTUALLY SAYS.
         |
         | This used to fill from the saved override alone, so every field a
         | merchant had never touched came up EMPTY while the page rendered the
         | theme's default underneath. Handing that panel to a shop owner means
         | handing them a form of blank boxes and no way to discover what any of
         | them currently control — and the first instinct, typing into one to
         | find out, silently replaces copy that was already right.
         |
         | ThemeCustomizer::copy() resolves override-then-default, which is
         | exactly what the storefront renders, so the field now shows the live
         | text. save() below drops anything still equal to the default, so
         | opening the page and saving it does not silently freeze every default
         | into an override.
         */
        $resolver = ThemeCustomizer::for($store, $this->themeSlug);
        foreach (array_keys($manifest['content'] ?? []) as $key) {
            $data["content_{$key}"] = $resolver->copy($key);
        }
        foreach (array_keys($manifest['images'] ?? []) as $slot) {
            $path = data_get($saved, "images.{$slot}");
            $data["image_{$slot}"] = $path ? [$path] : [];
        }

        $this->form->fill($data);
    }

    public function form(Schema $schema): Schema
    {
        $manifest = ThemeRegistry::manifest($this->themeSlug);

        if ($manifest === []) {
            return $schema->statePath('data')->components([
                Section::make(__('admin.theme.section.no_options'))
                    ->description(__('admin.theme.section_help.no_options', ['theme' => $this->themeName])),
            ]);
        }

        $tabs = [];

        // — Appearance: palette preset + font pairing —
        // Both option lists are authored in English inside the theme's own
        // manifest.php; ThemeRegistry::manifestText() swaps in the translation
        // when one exists and leaves the English standing when it doesn't, so
        // an untranslated theme keeps working exactly as before.
        $appearance = [];
        if (! empty($manifest['palettes'])) {
            $appearance[] = Radio::make('palette')
                ->label(__('admin.theme.field.palette'))
                ->options(collect($manifest['palettes'])
                    ->map(fn ($p, $id) => ThemeRegistry::manifestText($this->themeSlug, 'palette', $id, $p['name'] ?? $id))
                    ->all());
        }
        if (! empty($manifest['fonts'])) {
            $appearance[] = Radio::make('font')
                ->label(__('admin.theme.field.font'))
                ->options(collect($manifest['fonts'])
                    ->map(fn ($f, $id) => ThemeRegistry::manifestText($this->themeSlug, 'font', $id, $f['name'] ?? $id))
                    ->all());
        }
        if ($appearance !== []) {
            $tabs[] = Tab::make(__('admin.theme.section.appearance'))->schema($appearance);
        }

        // — Sections & motifs: toggles + editable motif labels —
        $toggles = [];
        foreach (($manifest['sections'] ?? []) as $id => $section) {
            $toggles[] = Toggle::make("section_{$id}")->label($section['label'] ?? $id);
        }
        foreach (($manifest['motifs'] ?? []) as $id => $motif) {
            $toggles[] = Toggle::make("motif_{$id}")->label($motif['label'] ?? $id)->live();
            if (isset($motif['text_label'])) {
                $toggles[] = TextInput::make("motif_text_{$id}")
                    ->label($motif['text_label'])
                    ->placeholder(isset($motif['text_default_lang']) ? __($motif['text_default_lang']) : ($motif['text_default'] ?? ''))
                    ->helperText(__('admin.theme.help.leave_empty'))
                    ->visible(fn ($get) => (bool) $get("motif_{$id}"));
            }
        }
        if ($toggles !== []) {
            $tabs[] = Tab::make(__('admin.theme.section.sections'))->schema($toggles);
        }

        // — Content: merchant copy with theme defaults as placeholders —
        $contentFields = [];
        foreach (($manifest['content'] ?? []) as $key => $field) {
            $placeholder = isset($field['default_lang']) ? __($field['default_lang']) : ($field['default'] ?? '');
            $contentFields[] = ($field['type'] ?? 'text') === 'textarea'
                ? Textarea::make("content_{$key}")->label($field['label'] ?? $key)->placeholder($placeholder)->rows(3)->helperText(__('admin.theme.help.leave_empty'))
                : TextInput::make("content_{$key}")->label($field['label'] ?? $key)->placeholder($placeholder)->helperText(__('admin.theme.help.leave_empty'));
        }
        if ($contentFields !== []) {
            $tabs[] = Tab::make(__('admin.theme.section.content'))->schema($contentFields);
        }

        // — Images: merchant photos for the theme's image slots —
        $imageFields = [];
        foreach (($manifest['images'] ?? []) as $slot => $field) {
            $help = trim(($field['hint'] ?? '').(isset($field['size']) ? ' '.__('admin.theme.help.recommended_size', ['size' => $field['size']]) : ''));
            $imageFields[] = FileUpload::make("image_{$slot}")
                ->label($field['label'] ?? $slot)
                ->image()
                ->disk('public')
                ->directory('theme-images')
                ->maxSize(4096)
                ->helperText($help !== '' ? $help : __('admin.theme.help.leave_empty'));
        }
        if ($imageFields !== []) {
            $tabs[] = Tab::make(__('admin.theme.section.images'))->schema($imageFields);
        }

        return $schema->statePath('data')->components([
            Tabs::make('customize')->tabs($tabs)->persistTabInQueryString(),
        ]);
    }

    /**
     * The value a content slot shows when the store has no override — the same
     * fallback ThemeCustomizer::copy() applies, so "is this still the default?"
     * is answered the same way in both places.
     *
     * @param  array<string, mixed>  $field
     */
    private static function defaultFor(array $field): string
    {
        if (isset($field['default_lang'])) {
            return (string) __($field['default_lang']);
        }

        return (string) ($field['default'] ?? '');
    }

    public function save(): void
    {
        $store = $this->getStore();
        $manifest = ThemeRegistry::manifest($this->themeSlug);
        $state = $this->form->getState();

        $settings = [];
        if (($state['palette'] ?? '') !== '' && isset($manifest['palettes'][$state['palette']])) {
            $settings['palette'] = $state['palette'];
        }
        if (($state['font'] ?? '') !== '' && isset($manifest['fonts'][$state['font']])) {
            $settings['font'] = $state['font'];
        }
        foreach (array_keys($manifest['sections'] ?? []) as $id) {
            $settings['sections'][$id] = (bool) ($state["section_{$id}"] ?? true);
        }
        foreach (($manifest['motifs'] ?? []) as $id => $motif) {
            $settings['motifs'][$id]['enabled'] = (bool) ($state["motif_{$id}"] ?? true);
            if (isset($motif['text_label'])) {
                $text = trim((string) ($state["motif_text_{$id}"] ?? ''));
                if ($text !== '') {
                    $settings['motifs'][$id]['text'] = $text;
                }
            }
        }
        /*
         | Store only what DIFFERS from the theme default. The fields arrive
         | prefilled with the resolved text, so writing them back wholesale
         | would turn every untouched default into a frozen override — and the
         | store would then stop following any later correction to the theme's
         | own copy. Clearing a field still means "give me the default back".
         */
        foreach (($manifest['content'] ?? []) as $key => $field) {
            $text = trim((string) ($state["content_{$key}"] ?? ''));

            // Slots ending in _html are echoed unescaped by the theme, so what
            // a merchant types goes into the page as markup. Allowlist it.
            if (ThemeCopyHtml::isHtmlSlot($key)) {
                $text = ThemeCopyHtml::sanitize($text);
            }

            if ($text === '' || $text === trim(self::defaultFor($field))) {
                continue;
            }
            $settings['content'][$key] = $text;
        }
        foreach (array_keys($manifest['images'] ?? []) as $slot) {
            $path = $state["image_{$slot}"] ?? null;
            // FileUpload state may be a string or a single-item array.
            if (is_array($path)) {
                $path = array_values($path)[0] ?? null;
            }
            if (is_string($path) && $path !== '') {
                $settings['images'][$slot] = $path;
            }
        }

        $all = $store->theme_settings ?? [];
        $all['themes'][$this->themeSlug] = $settings;
        $store->update(['theme_settings' => $all]);

        Notification::make()->success()->title(__('admin.theme.notify.saved'))->send();
    }

    protected function getStore(): Store
    {
        $tenant = auth()->user()->tenant;

        return $tenant->store ?? $tenant->store()->create([]);
    }
}
