<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Write Sankevi's REAL "За нас" and "Контакти" content into their store.
 *
 * Why a command and not a one-off tinker paste: the store already carried the
 * demo copy that `ganvo:provision-demo` warns about in its own docblock — an
 * invented founder, an invented 1974, invented milestone years and invented
 * numbers. That has to be replaced with something auditable, and re-runnable
 * after any future re-provision drops the placeholder back in.
 *
 *   php artisan ganvo:sankevi-pages --dry-run
 *   php artisan ganvo:sankevi-pages
 *
 * SOURCE OF EVERY FACT BELOW: the client's own printed catalogue (the ABOUT
 * US, PRODUCTION, and COMPANY INFORMATION pages) plus the street address the
 * owner gave separately. The owner has confirmed all of it is current. Nothing
 * here is inferred, rounded or filled in:
 *
 *   · there is NO founding year anywhere in the catalogue, only "more than 25
 *     years of experience" — so `founded_year` is deliberately left null and
 *     the page must never print an "От :year г." eyebrow;
 *   · the email is the one on the COMPANY INFORMATION page. Three other
 *     spellings appear in various page footers of the catalogue; they are
 *     typos and are ignored;
 *   · the catalogue gives no opening hours and no map, so `hours` and
 *     `map_embed` are PRESERVED as the merchant left them rather than invented
 *     or blanked — two fields this command has no authority over.
 *
 *     KNOWN CONSEQUENCE: they are therefore NOT reproducible. The opening
 *     hours currently on /contact came from demo provisioning, not from the
 *     client, so a store rebuilt from this command alone comes up without
 *     them. That is deliberate — inventing opening hours is exactly the kind
 *     of thing this command exists to undo — but it means a diff of a rebuilt
 *     store against the live one will always show `hours` missing, and that
 *     is expected rather than a bug.
 *   · it DOES carry photographs. They are seeded into `images` only when the
 *     merchant has none of their own, so a fresh deploy gets the workshop
 *     gallery and a merchant who has since uploaded their own keeps it.
 *
 * WHAT ELSE THIS WRITES, and why it is here rather than in a tinker one-off:
 * everything below lived only in the local database until now, which meant a
 * fresh deploy would have come up with the demo copy back in place. The
 * announcement tape and the two theme copy overrides are as much a part of
 * "Sankevi's real content" as the About page is, so they are provisioned the
 * same way and from the same source.
 *
 * Both arrays are written with exactly the key set StoreSettings::save() folds
 * out of its form, so a merchant who opens Settings after this runs edits the
 * same shape back — no key this command writes is one the form would drop.
 */
class SyncSankeviPages extends Command
{
    protected $signature = 'ganvo:sankevi-pages
                            {--slug=sankevi : the tenant slug to write to}
                            {--dry-run : print the resulting JSON and exit}';

    protected $description = "Write Sankevi's real About/Contact page content from the client catalogue";

    /*
     | Town and country only, at the client's instruction — no street, no postal
     | code. They sell to trade buyers who ring or email; the yard is not a shop
     | anyone walks into, so a street line invites visitors they do not want and
     | is one more thing to keep correct.
     |
     | The VAT number used to ride along in this field. It went with the street:
     | it is not an address, and it should not reappear here if this command is
     | re-run.
     */
    private const ADDRESS = 'Велинград, България';

    /**
     * The workshop gallery, in the order the process runs: the forest, the
     * haul, the log carriage, the kiln, the pallets. Five because the About
     * page spends the first on the page lead (Store::ABOUT_IMAGE_SLOTS), which
     * leaves four for the gallery grid — exactly one row of it.
     *
     * Paths are relative to the PUBLIC DISK, because that is what
     * Storage::url() resolves; the files ship in public/images/sankevi/
     * catalogue/ and are copied across on first run.
     */
    private const GALLERY = [
        'logs-forest.webp',
        'logging-truck.webp',
        'sawmill-log-carriage.webp',
        'drying-kiln.webp',
        'packed-pallets.webp',
    ];

    /*
     | Must match ImportSankeviCatalogue::DISK_DIR. The two commands publish
     | into the same folder from the same handoff directory, and when they
     | disagreed the About gallery pointed at catalogue/sankevi/ while the
     | product rows pointed at sankevi/catalogue/ — both worked, because both
     | folders had been filled by hand, and neither would have survived a
     | checkout that only ran one of the commands.
     */
    private const DISK_DIR = 'sankevi/catalogue';

    private const HANDOFF_DIR = 'images/sankevi/catalogue';

    /**
     * The hero photograph, supplied by the client after their August meeting.
     *
     * Source path in the repo => path on the public disk. It is not a catalogue
     * photo, so it does not sit in the catalogue folder. The theme reads it
     * through an images.hero_image override, and ThemeCustomizer::image()
     * resolves that with Storage::disk('public') — which is why it has to be
     * copied out of public/ rather than simply referenced there.
     */
    private const HERO_IMAGE = ['images/sankevi/hero.webp' => 'sankevi/hero.webp'];

    /**
     * The tape across the top of every page.
     *
     * It used to read „Семейна дъскорезница в Родопите · Собствен добив ·
     * Разкрой по размер“ — the demo positioning. „Собствен добив“ (our own
     * logging) is the part that mattered: the catalogue says the timber is
     * SOURCED from around Velingrad, and claiming to fell it is a claim about
     * the supply chain that nobody at Sankevi has made.
     */
    private const ANNOUNCEMENT = 'РАФТОВИ СИСТЕМИ ОТ МАСИВНО ДЪРВО · НАД 25 ГОДИНИ ОПИТ · FSC СЕРТИФИЦИРАНА ДЪРВЕСИНА';

    /**
     * Theme copy overrides, keyed as ThemeCustomizer::copy() reads them:
     * stores.theme_settings['themes']['sankevi']['content'][$key].
     *
     * The workshop band on /about is theme copy, not page content, so it is not
     * reachable from the About fields above. Left alone it renders the theme's
     * own default — which for these two slots is the invented „Едно семейство,
     * един и същи трион“ and a great-grandfather who put the first blade in a
     * sawmill on a meadow. None of that is Sankevi.
     */
    private const THEME_COPY = [
        /* The rotating tape carries the full legal name; everything else — the
           header wordmark, the copyright line, order emails — keeps the short
           trading name, which is why this is a slot and not a tenant rename. */
        'marquee_name' => 'САНКЕВИ ООД',
        'story_h2_html' => 'Естествено дърво, <em>умни рафтове</em>',
        'story_body' => 'Повече от 25 години обработваме дърво — от трупа до готовото изделие. '
            .'Дървесината идва от горите около Велинград, район с най-чистия въздух на планетата, '
            .'и е от FSC сертифицирани източници. Бичим, сушим, калибрираме и опаковаме в собствена база.',
    ];

    private const PHONE = '+359 897 810 020';   // Атанас Санкев, търговски мениджър

    private const EMAIL = 'asankev@gmail.com';

    public function handle(): int
    {
        $slug = (string) $this->option('slug');

        $tenant = Tenant::where('slug', $slug)->first();
        if (! $tenant) {
            $this->error("No tenant with slug '{$slug}'.");

            return self::FAILURE;
        }

        $store = $tenant->store;
        if (! $store) {
            $this->error("Tenant '{$slug}' has no store row.");

            return self::FAILURE;
        }

        // Read the RAW columns, not contactPage()/aboutPage(): the accessors
        // fall the email/phone back to the tenant account, and writing a
        // fallback back into the column would freeze it there for good.
        $contactRaw = (array) ($store->contact ?? []);
        $aboutRaw = (array) ($store->about ?? []);

        $contact = $this->contact($contactRaw);
        $about = $this->about($aboutRaw);
        $announcement = $this->announcement((array) ($store->announcement ?? []));
        $themeSettings = $this->themeSettings((array) ($store->theme_settings ?? []));

        if ($this->option('dry-run')) {
            $this->line('contact: '.json_encode($contact, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $this->line('about: '.json_encode($about, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $this->line('announcement: '.json_encode($announcement, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $this->line('theme copy: '.json_encode(self::THEME_COPY, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $this->line('gallery would be: '.json_encode($about['images'], JSON_UNESCAPED_SLASHES));

            return self::SUCCESS;
        }

        // Photographs before the paths that point at them, so the About page is
        // never briefly holding references to files that are not there yet.
        $copied = $this->publishGallery();

        $store->forceFill([
            'contact' => $contact,
            'about' => $about,
            'announcement' => $announcement,
            'theme_settings' => $themeSettings,
        ])->save();

        $this->info("Wrote Sankevi's content to store #{$store->id} ({$slug}).");
        $this->line('  contact: '.count(array_filter($contact, fn ($v) => $v !== '' && $v !== null)).' fields set');
        $this->line('  about:   '.count($about['milestones']).' production steps, '.count($about['stats']).' numbers');
        $this->line('  gallery: '.count($about['images']).' images'.($copied ? " ({$copied} copied onto the public disk)" : ''));
        $this->line('  tape:    '.$announcement['text']);
        $this->line('  theme:   '.count(self::THEME_COPY).' copy overrides');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $current
     * @return array<string, mixed>
     */
    private function contact(array $current): array
    {
        return [
            'enabled' => true,
            'show_form' => true,
            'heading' => 'Свържете се с нас',
            // The details panel renders ONE phone row, and it turns whatever is
            // in it into a single tel: link — two numbers in that field would
            // produce one unreachable 22-digit href. So the trade line stays in
            // `phone` and the owner's own line is named here, with both roles,
            // rather than being dropped for want of a second field.
            'intro' => 'Пишете ни за оферта, размери по спецификация или срокове — отговаряме лично. '
                .'Търговските запитвания поема Атанас Санкев, търговски мениджър, на +359 897 810 020. '
                .'За въпроси към управлението потърсете Петър Санкев, собственик, на +359 897 810 010.',
            'address' => self::ADDRESS,
            'phone' => self::PHONE,
            'email' => self::EMAIL,
            // Not in the catalogue — keep whatever the merchant has.
            'hours' => trim((string) ($current['hours'] ?? '')),
            'map_embed' => ($current['map_embed'] ?? null) ?: null,
        ];
    }

    /**
     * @param  array<string, mixed>  $current
     * @return array<string, mixed>
     */
    private function about(array $current): array
    {
        // Blank lines are paragraph breaks — about.blade.php splits on /\R{2,}/
        // and sets the FIRST paragraph large, in the display serif, so the
        // opening line has to be the one that carries the company.
        $story = implode("\n\n", [
            'Повече от 25 години обработваме дърво. През 25 от тях работим с нидерландския пазар.',

            'Дървесината идва от района на Велинград — район, известен с най-чистия въздух на планетата, '
                .'което е научно доказано. Тази природна среда дава на дървото неговата чистота.',

            'Работим с дървото от суровия труп до готовото изделие — бичене, сушене, калибриране и '
                .'опаковане минават през нашите цехове. Произвеждаме дървени рафтови системи и изделия '
                .'от масивна дървесина.',

            'Държим на отговорния добив и на устойчивото стопанисване на горите — материалите се подбират '
                .'от източници със сертификат FSC.',

            'Разчитаме на надежден контрол на качеството, практичен дизайн, здрава конструкция, ефективно '
                .'производство и опит в износа. Това ни позволява дългосрочни партньорства с клиенти в '
                .'цяла Европа.',

            'Клиентите ни се връщат заради едно и също: над 25 години опит, надеждно производство за износ, '
                .'естествени материали от масивна дървесина, модулни и практични рафтови решения, качество, '
                .'функционалност и сигурна доставка — и гъвкаво, надеждно партньорство.',

            'Накратко: правим рафтови системи, които са здрави, красиви и направени да служат дълго. С фокус '
                .'върху естественото дърво, умния дизайн и доволния клиент предлагаме решения за съхранение, '
                .'които създават стойност и издържат на времето.',
        ]);

        return [
            'enabled' => true,
            'heading' => 'Масивно дърво от Велинград',
            // The slogan, folded into the sentence that says what they make.
            'intro' => 'Български производител на рафтови системи и изделия от масивна дървесина. '
                .'Естествено дърво, умни рафтови системи, надеждно партньорство.',
            'story' => $story,
            // NO founding year exists in the catalogue. Null keeps the page
            // eyebrow on the theme's own „Работилницата“ instead of inventing
            // an "От 1974 г." the client never claimed.
            'founded_year' => null,
            // The five-step production process. `year` is free text and renders
            // in the margin column, so the step numbers stand where the years
            // would — the same timeline, reading down the process.
            'milestones' => [
                [
                    'year' => '01',
                    'title' => 'Добив на дървесината',
                    'text' => 'Дървесината идва от района на Велинград и от FSC® сертифицирани източници. '
                        .'Работим с модерна база и линии с висок капацитет.',
                ],
                [
                    'year' => '02',
                    'title' => 'Обработка на дървесината',
                    'text' => 'Суровите трупи минават през модерно бичево оборудване — за максимален добив '
                        .'от всеки труп и постоянно качество.',
                ],
                [
                    'year' => '03',
                    'title' => 'Сушене',
                    'text' => 'Сушенето в камери сваля влагата до оптимални нива. Така дървото става '
                        .'стабилно и не се изкривява, нито се напуква.',
                ],
                [
                    'year' => '04',
                    'title' => 'Калибриране',
                    'text' => 'Дървесината се рендосва и калибрира прецизно, до точния размер.',
                ],
                [
                    'year' => '05',
                    'title' => 'Опаковане',
                    'text' => 'Готовите изделия се опаковат внимателно, за да пътуват без повреди.',
                ],
            ],
            // Rendered under „В цифри“. Every value here is a figure the
            // catalogue states outright — the step count is the list above.
            'stats' => [
                ['value' => '25+', 'label' => 'години опит в дървообработването'],
                ['value' => '25', 'label' => 'години на нидерландския пазар'],
                ['value' => '5', 'label' => 'стъпки от трупа до готовия продукт'],
                ['value' => 'FSC', 'label' => 'сертификат CU-COC-879804'],
            ],
            // SEED, don't overwrite. A merchant who has uploaded their own
            // story pictures keeps them; a store that has none — which is every
            // fresh deploy — gets the catalogue's workshop sequence.
            'images' => $this->galleryFor($current),
        ];
    }

    /**
     * The story gallery: the merchant's if they have one, ours otherwise.
     *
     * "Theirs" means any path that is not one of ours — a merchant who has
     * uploaded even one picture is treated as having taken the page over, and
     * we do not mix our stock shots in among their own.
     *
     * @param  array<string, mixed>  $current
     * @return array<int, string>
     */
    private function galleryFor(array $current): array
    {
        $have = array_values(array_filter(
            array_map(fn ($p) => trim((string) $p), (array) ($current['images'] ?? []))
        ));

        $ours = $this->galleryPaths();
        $theirs = array_diff($have, $ours);

        if ($theirs !== []) {
            return $have;
        }

        // Never hand back more slots than the About page will render — the
        // extras would be silently dropped by Store::aboutPage() and the stored
        // value would stop matching the page.
        return array_slice($ours, 0, Store::ABOUT_IMAGE_SLOTS);
    }

    /** @return array<int, string> */
    private function galleryPaths(): array
    {
        return array_map(fn (string $file) => self::DISK_DIR.'/'.$file, self::GALLERY);
    }

    /**
     * Copy the gallery onto the public disk.
     *
     * The files are committed under public/images/sankevi/catalogue/, which is
     * NOT where Storage::url() looks — it always resolves /storage, i.e.
     * storage/app/public. That directory is gitignored, so on a fresh checkout
     * it is empty and every gallery image would 404 until something put them
     * there. This is that something.
     *
     * @return int how many files were actually written
     */
    private function publishGallery(): int
    {
        $diskDir = storage_path('app/public/'.self::DISK_DIR);
        $handoff = public_path(self::HANDOFF_DIR);
        $copied = 0;

        foreach (self::HERO_IMAGE as $from => $to) {
            $copied += $this->publishOne(public_path($from), storage_path('app/public/'.$to)) ? 1 : 0;
        }

        foreach (self::GALLERY as $file) {
            $src = "{$handoff}/{$file}";
            if (! is_file($src)) {
                $this->warn('  missing source image: public/'.self::HANDOFF_DIR."/{$file}");

                continue;
            }

            File::ensureDirectoryExists($diskDir);
            $dst = "{$diskDir}/{$file}";

            // Size, not mtime: a fresh checkout gives every file the checkout's
            // own timestamp, so an mtime comparison would either copy all of
            // them every run or none of them.
            if (is_file($dst) && filesize($dst) === filesize($src)) {
                continue;
            }

            File::copy($src, $dst);
            $copied++;
        }

        return $copied;
    }

    /** Copy one file onto the disk unless it is already there, same size. */
    private function publishOne(string $src, string $dst): bool
    {
        if (! is_file($src)) {
            $this->warn("  missing source image: {$src}");

            return false;
        }

        // Size, not mtime — see publishGallery().
        if (is_file($dst) && filesize($dst) === filesize($src)) {
            return false;
        }

        File::ensureDirectoryExists(dirname($dst));
        File::copy($src, $dst);

        return true;
    }

    /**
     * The announcement tape, keeping whatever display settings the merchant has
     * chosen — this command has an opinion about the WORDS, not about the speed
     * or the link.
     *
     * @param  array<string, mixed>  $current
     * @return array<string, mixed>
     */
    private function announcement(array $current): array
    {
        // Defaults UNDER the merchant's values, our text OVER them: a store
        // whose announcement column is empty — every fresh deploy — still gets
        // the full key set StoreSettings::save() expects, and one that already
        // has a speed or a link keeps it.
        return array_merge(
            ['link' => null, 'speed' => 'normal'],
            $current,
            ['enabled' => true, 'text' => self::ANNOUNCEMENT],
        );
    }

    /**
     * Merge the theme copy overrides into theme_settings without disturbing the
     * palette, fonts, section toggles or motifs the merchant has set.
     *
     * The nesting is not decorative: ThemeCustomizer::copy() reads
     * ['themes'][<slug>]['content'][<key>], and writing to ['content'] at the
     * top level — which is the obvious-looking place — puts the value somewhere
     * nothing ever reads.
     *
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function themeSettings(array $settings): array
    {
        $content = (array) data_get($settings, 'themes.sankevi.content', []);
        data_set($settings, 'themes.sankevi.content', array_merge($content, self::THEME_COPY));

        // images.<slot> is a SEPARATE bag from content.<slot> — ThemeCustomizer
        // reads the two with different resolvers, so a hero path written into
        // content is never looked at.
        $images = (array) data_get($settings, 'themes.sankevi.images', []);
        data_set($settings, 'themes.sankevi.images', array_merge($images, [
            'hero_image' => array_values(self::HERO_IMAGE)[0],
        ]));

        return $settings;
    }
}
