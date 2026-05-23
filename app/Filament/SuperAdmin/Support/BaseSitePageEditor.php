<?php

namespace App\Filament\SuperAdmin\Support;

use App\Http\Middleware\SetLocale;
use App\Models\SitePage;
use App\Services\RoleMatrix;
use App\Services\SitePageSchemas;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
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
 * Shared logic for every SuperAdmin "page content editor" — one tab per
 * locale, fields driven by SitePageSchemas, save through SitePage with
 * cache busting.
 *
 * To add a new editable page:
 *   1. Add the page slug + schema in App\Services\SitePageSchemas.
 *   2. Subclass this base inside App\Filament\SuperAdmin\Pages\, set
 *      $pageSlug + $view + $title + $slug + $navigationLabel.
 *
 * Lives under Support/ rather than Pages/ so Filament's discoverPages()
 * doesn't try to instantiate the abstract.
 */
abstract class BaseSitePageEditor extends Page implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    /**
     * The SitePage::PAGE_* slug this editor manages. Subclasses set this
     * to one of {@see SitePageSchemas} constants.
     */
    abstract protected static function pageSlug(): string;

    /** Gate: who can edit content. Defaults to the manage-content section. */
    public static function canAccess(): bool
    {
        return RoleMatrix::canSee(auth()->user(), RoleMatrix::SEC_CONTENT);
    }

    public function mount(): void
    {
        // Load each locale's stored content into the form's nested array.
        // Form state shape: ['en' => ['field' => '...', ...], 'bg' => [...]]
        $state = [];
        foreach (SetLocale::SUPPORTED as $locale) {
            $row = SitePage::forPageLocale(static::pageSlug(), $locale);
            $state[$locale] = (array) ($row->content ?? []);
        }
        $this->form->fill($state);
    }

    public function form(Schema $schema): Schema
    {
        $schemaFields = SitePageSchemas::schemaFor(static::pageSlug());

        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('locales')
                    ->tabs(array_map(
                        fn (string $locale) => $this->tabForLocale($locale, $schemaFields),
                        SetLocale::SUPPORTED
                    )),
            ]);
    }

    private function tabForLocale(string $locale, array $schemaFields): Tab
    {
        $localeLabel = strtoupper($locale);

        return Tab::make($localeLabel)
            ->icon(Heroicon::OutlinedLanguage)
            ->schema([
                Section::make("{$localeLabel} content")
                    ->description("Leave a field blank to fall back to the default translation for {$localeLabel}.")
                    ->schema(array_map(
                        fn (array $field) => $this->fieldComponent($locale, $field),
                        array_values($schemaFields)
                    )),
            ]);
    }

    /**
     * Build the right form component for a single schema field. Uses
     * placeholder text to show the operator what the default would render
     * if they leave the field blank — handy when re-syncing translations
     * after a copy edit.
     */
    private function fieldComponent(string $locale, array $field)
    {
        // Translated default = the i18n catalog value in this locale.
        // Surfaced as placeholder so the editor sees what they're overriding.
        $default = (string) __($field['fallback'], [], $locale);

        $component = $field['type'] === 'textarea'
            ? Textarea::make("{$locale}.{$field['key']}")->rows(3)
            : TextInput::make("{$locale}.{$field['key']}");

        return $component
            ->label($field['label'])
            ->helperText($field['help'])
            ->placeholder($default)
            ->maxLength($field['max'] ?? null)
            ->columnSpanFull();
    }

    public function save(): void
    {
        $state = $this->form->getState();
        $schemaFieldKeys = array_keys(SitePageSchemas::schemaFor(static::pageSlug()));

        foreach (SetLocale::SUPPORTED as $locale) {
            $localeData = (array) ($state[$locale] ?? []);

            // Keep only known fields; trim strings; drop empty values so
            // the row's content blob stays small + clean. Blank field =
            // fall back to the i18n catalog.
            $clean = [];
            foreach ($schemaFieldKeys as $key) {
                $value = $localeData[$key] ?? null;
                if (is_string($value)) {
                    $value = trim($value);
                }
                if ($value !== null && $value !== '') {
                    $clean[$key] = $value;
                }
            }

            $row = SitePage::forPageLocale(static::pageSlug(), $locale);
            $row->content = $clean;
            $row->save();

            SitePage::bustCache(static::pageSlug(), $locale);
        }

        Notification::make()
            ->success()
            ->title('Content saved')
            ->body($this->savedNotificationBody())
            ->send();
    }

    /** Subclasses may override to customize the success notification body. */
    protected function savedNotificationBody(): string
    {
        return 'Changes are live on the next page load.';
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save changes')
                ->submit('save'),
        ];
    }
}
