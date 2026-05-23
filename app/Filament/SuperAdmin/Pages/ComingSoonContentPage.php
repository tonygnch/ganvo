<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Http\Middleware\SetLocale;
use App\Models\SitePage;
use App\Services\RoleMatrix;
use App\Services\SitePageSchemas;
use BackedEnum;
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
 * SuperAdmin editor for the coming-soon splash page text.
 *
 * The form has one tab per supported locale. Each tab renders the
 * fields defined in {@see SitePageSchemas::schemaFor()} for the
 * coming-soon page. Saving writes to the `site_pages` table (one row
 * per page+locale) and busts the read cache so the splash picks up
 * the new copy on the next request.
 *
 * Architecturally generic: when marketing-home editing arrives, this
 * page is the template — copy it, point at PAGE_MARKETING_HOME, done.
 */
class ComingSoonContentPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.super-admin.pages.coming-soon-content';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedMegaphone;

    protected static ?string $navigationLabel = 'Coming-soon content';

    protected static string|\UnitEnum|null $navigationGroup = 'Marketing';

    protected static ?string $title = 'Coming-soon page content';

    protected static ?string $slug = 'coming-soon-content';

    protected static ?int $navigationSort = 95;

    public static function canAccess(): bool
    {
        return RoleMatrix::canSee(auth()->user(), RoleMatrix::SEC_CONTENT);
    }

    public ?array $data = [];

    /** Page slug this editor manages. Hardcoded; future pages get their own class. */
    private const PAGE = SitePageSchemas::PAGE_COMING_SOON;

    public function mount(): void
    {
        // Load each locale's stored content into the form's nested array.
        // Form state shape: ['en' => ['eyebrow' => '...', ...], 'bg' => [...]]
        $state = [];
        foreach (SetLocale::SUPPORTED as $locale) {
            $row = SitePage::forPageLocale(self::PAGE, $locale);
            $state[$locale] = (array) ($row->content ?? []);
        }
        $this->form->fill($state);
    }

    public function form(Schema $schema): Schema
    {
        $schemaFields = SitePageSchemas::schemaFor(self::PAGE);

        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('locales')
                    ->tabs(array_map(
                        fn (string $locale) => self::tabForLocale($locale, $schemaFields),
                        SetLocale::SUPPORTED
                    )),
            ]);
    }

    private static function tabForLocale(string $locale, array $schemaFields): Tab
    {
        $localeLabel = strtoupper($locale);

        return Tab::make($localeLabel)
            ->icon(Heroicon::OutlinedLanguage)
            ->schema([
                Section::make("{$localeLabel} content")
                    ->description("Leave a field blank to fall back to the default translation for {$localeLabel}.")
                    ->schema(array_map(
                        fn (array $field) => self::fieldComponent($locale, $field),
                        array_values($schemaFields)
                    )),
            ]);
    }

    /**
     * Build the right form component for a single schema field. Uses
     * placeholder text to show the merchant what the default would render
     * if they leave the field blank — handy when re-syncing translations
     * after a copy edit.
     */
    private static function fieldComponent(string $locale, array $field)
    {
        // Translated default = the i18n catalog value in this locale.
        // Surfaced as placeholder so the editor can see what they're
        // overriding without having to look it up.
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
        $schemaFieldKeys = array_keys(SitePageSchemas::schemaFor(self::PAGE));

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

            $row = SitePage::forPageLocale(self::PAGE, $locale);
            $row->content = $clean;
            $row->save();

            SitePage::bustCache(self::PAGE, $locale);
        }

        Notification::make()
            ->success()
            ->title('Content saved')
            ->body('The coming-soon page is using the new copy.')
            ->send();
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
