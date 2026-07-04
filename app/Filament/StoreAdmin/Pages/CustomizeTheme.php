<?php

namespace App\Filament\StoreAdmin\Pages;

use App\Models\Store;
use App\Themes\ThemeRegistry;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
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

    protected static ?string $navigationLabel = 'Customize Theme';

    protected static ?string $title = 'Customize Theme';

    public ?array $data = [];

    public string $themeSlug = '';

    public string $themeName = '';

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
        foreach (($manifest['content'] ?? []) as $key => $field) {
            $data["content_{$key}"] = (string) data_get($saved, "content.{$key}", '');
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
                Section::make('No customization available yet')
                    ->description("The \"{$this->themeName}\" theme doesn't expose customization options yet. Colors, logo and chrome are still editable under Store Settings."),
            ]);
        }

        $tabs = [];

        // — Appearance: palette preset + font pairing —
        $appearance = [];
        if (! empty($manifest['palettes'])) {
            $appearance[] = Radio::make('palette')
                ->label('Palette preset')
                ->options(collect($manifest['palettes'])->map(fn ($p) => $p['name'])->all());
        }
        if (! empty($manifest['fonts'])) {
            $appearance[] = Radio::make('font')
                ->label('Display font pairing')
                ->options(collect($manifest['fonts'])->map(fn ($f) => $f['name'])->all());
        }
        if ($appearance !== []) {
            $tabs[] = Tab::make('Appearance')->schema($appearance);
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
                    ->helperText('Leave empty to use the theme default.')
                    ->visible(fn ($get) => (bool) $get("motif_{$id}"));
            }
        }
        if ($toggles !== []) {
            $tabs[] = Tab::make('Sections')->schema($toggles);
        }

        // — Content: merchant copy with theme defaults as placeholders —
        $contentFields = [];
        foreach (($manifest['content'] ?? []) as $key => $field) {
            $placeholder = isset($field['default_lang']) ? __($field['default_lang']) : ($field['default'] ?? '');
            $contentFields[] = ($field['type'] ?? 'text') === 'textarea'
                ? Textarea::make("content_{$key}")->label($field['label'] ?? $key)->placeholder($placeholder)->rows(3)->helperText('Leave empty to use the theme default.')
                : TextInput::make("content_{$key}")->label($field['label'] ?? $key)->placeholder($placeholder)->helperText('Leave empty to use the theme default.');
        }
        if ($contentFields !== []) {
            $tabs[] = Tab::make('Content')->schema($contentFields);
        }

        // — Images: merchant photos for the theme's image slots —
        $imageFields = [];
        foreach (($manifest['images'] ?? []) as $slot => $field) {
            $help = trim(($field['hint'] ?? '') . (isset($field['size']) ? " Recommended: {$field['size']}." : ''));
            $imageFields[] = FileUpload::make("image_{$slot}")
                ->label($field['label'] ?? $slot)
                ->image()
                ->disk('public')
                ->directory('theme-images')
                ->maxSize(4096)
                ->helperText($help !== '' ? $help : 'Leave empty to use the theme default.');
        }
        if ($imageFields !== []) {
            $tabs[] = Tab::make('Images')->schema($imageFields);
        }

        return $schema->statePath('data')->components([
            Tabs::make('customize')->tabs($tabs)->persistTabInQueryString(),
        ]);
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
        foreach (array_keys($manifest['content'] ?? []) as $key) {
            $text = trim((string) ($state["content_{$key}"] ?? ''));
            if ($text !== '') {
                $settings['content'][$key] = $text;
            }
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

        Notification::make()->success()->title('Theme customization saved')->send();
    }

    protected function getStore(): Store
    {
        $tenant = auth()->user()->tenant;

        return $tenant->store ?? $tenant->store()->create([]);
    }
}
