<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Models\Tenant;
use Illuminate\Console\Command;

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
 *   · the catalogue gives no opening hours, no map and no photographs, so
 *     `hours`, `map_embed` and `images` are PRESERVED as the merchant left
 *     them rather than invented or blanked. That keeps the command idempotent
 *     while leaving the three fields it has no authority over alone.
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

    /** Velingrad, plus the VAT number a trade buyer needs before they can invoice. */
    private const ADDRESS = "България, гр. Велинград 4600\n"
        ."ул. „Даскал Георги Чолаков“ 17 А\n"
        .'ДДС № BG202597770';

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

        if ($this->option('dry-run')) {
            $this->line('contact: '.json_encode($contact, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            $this->line('about: '.json_encode($about, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $store->forceFill(['contact' => $contact, 'about' => $about])->save();

        $this->info("Wrote Sankevi's About and Contact content to store #{$store->id} ({$slug}).");
        $this->line('  contact: '.count(array_filter($contact, fn ($v) => $v !== '' && $v !== null)).' fields set');
        $this->line('  about:   '.count($about['milestones']).' production steps, '.count($about['stats']).' numbers');

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
                    'title' => 'Обработка на дървесината',
                    'text' => 'Суровите трупи минават през модерно бичево оборудване — за максимален добив '
                        .'от всеки труп и постоянно качество.',
                ],
                [
                    'year' => '02',
                    'title' => 'Производствен капацитет',
                    'text' => 'Модерна база и линии с висок капацитет — голям обем, произведен ефективно и '
                        .'с еднаква прецизност.',
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
            // No photographs came with the catalogue text — keep the merchant's.
            'images' => array_values(array_filter(
                array_map(fn ($p) => trim((string) $p), (array) ($current['images'] ?? []))
            )),
        ];
    }
}
